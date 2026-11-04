<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\View\View;
use Opcodes\Spike\Facades\Spike;
use Opcodes\Spike\View\Components\UsageChart;
use function Spatie\PestPluginTestTime\testTime;

uses(RefreshDatabase::class);

/**
 * The credits a series shows on a date, read from the ApexCharts options the way the chart reads them:
 * a series' data lines up with the x-axis categories by index.
 */
function usageOn(array $options, string $seriesName, string $date): int|float|null
{
    $series = collect($options['series'])->firstWhere('name', $seriesName);
    $index = array_search($date, $options['xaxis']['categories'], true);

    if ($series === null || $index === false) {
        return null;
    }

    return $series['data'][$index] ?? null;
}

beforeEach(function () {
    $this->user = createBillable();
    Spike::resolve(fn () => $this->user);
});

test('usage view contains no usage by default', closure: function () {
    tap((new UsageChart())->render(), function (View $view) {
        $view->assertViewIs('spike::components.usage-chart');
        $view->assertViewHas('hasUsage', false);
        $view->assertViewHas('dailyUsageChartOptions', null);
    });
});

test('usage view contains daily usage data', function () {
    $this->user->credits()->add(500);
    $firstDay = now();
    $this->user->credits()->spend(50);
    testTime()->addDay();
    $secondDay = now();
    $this->user->credits()->spend(100);

    tap((new UsageChart())->render(), function (View $view) use ($firstDay, $secondDay) {
        $view->assertViewHas('hasUsage', true)
            ->assertViewHas('dailyUsageChartOptions', function (array $options) use ($firstDay, $secondDay) {
                return usageOn($options, 'credits', $firstDay->toDateString()) === 50
                    && usageOn($options, 'credits', $secondDay->toDateString()) === 100;
            });
    });
});

test('usage view groups multiple usages on the same day', function () {
    $this->user->credits()->add(500);
    $this->user->credits()->spend(100);
    $this->user->credits()->currentUsageTransaction()->expire();
    $this->user->credits()->spend(60);
    expect($this->user->credits()->balance())->toBe(500 - 160);

    tap((new UsageChart())->render(), function (View $view) {
        $view->assertViewHas('hasUsage', true)
            ->assertViewHas('dailyUsageChartOptions', function (array $options) {
                return usageOn($options, 'credits', now()->toDateString()) === 160;
            });
    });
});

test('usage view can show multiple types of credits', function () {
    $this->user = createBillable();
    $this->user->credits()->add(500);
    $this->user->credits()->type('sms')->add(400);
    $this->user->credits()->spend(100);
    $this->user->credits()->type('sms')->spend(80);

    tap((new UsageChart())->render(), function (View $view) {
        $view->assertViewHas('hasUsage', true)
            ->assertViewHas('dailyUsageChartOptions', function (array $options) {
                return usageOn($options, 'credits', now()->toDateString()) === 100
                    && usageOn($options, 'sms', now()->toDateString()) === 80
                    && $options['chart']['stacked'] === true
                    && $options['legend']['show'] === true;
            });
    });
});

test('a single credit type is not stacked and has no legend', function () {
    $this->user->credits()->add(500);
    $this->user->credits()->spend(100);

    tap((new UsageChart())->render(), function (View $view) {
        $view->assertViewHas('dailyUsageChartOptions', function (array $options) {
            return $options['chart']['stacked'] === false
                && $options['legend']['show'] === false
                && $options['xaxis']['type'] === 'datetime'
                && $options['yaxis']['show'] === false;
        });
    });
});
