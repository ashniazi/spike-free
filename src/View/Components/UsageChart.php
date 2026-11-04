<?php

namespace Opcodes\Spike\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Opcodes\Spike\CreditTransaction;
use Opcodes\Spike\CreditType;
use Opcodes\Spike\Facades\Spike;

class UsageChart extends Component
{
    public function render(): View
    {
        $hasUsage = $this->getUsageQuery()->exists();

        return view('spike::components.usage-chart', [
            'hasUsage' => $hasUsage,
            'dailyUsageChartOptions' => $hasUsage ? $this->getDailyUsageChartOptions() : null,
        ]);
    }

    /**
     * The finished ApexCharts options for the daily usage chart.
     *
     * This used to go through arukompas/livewire-charts, a Livewire component that only ever drew this one static
     * chart and pinned Spike to Livewire 3. The options are now built here exactly as that package built them: its
     * model defaults, the mapping in its multiColumnChart.js, and its deepmerge of the JSON config (lists are
     * concatenated, objects merged). The chart is drawn by ApexCharts 3.41.0, the same build it bundled.
     */
    protected function getDailyUsageChartOptions(): array
    {
        $colors = [
            config('spike.theme.color'),
            '#36A2EB',
            '#FFCE56',
            '#FF6384',
            '#4BC0C0',
            '#9966FF',
            '#C9CBCF',
            '#FF9F40',
            '#FFCD56',
            '#4BC0C0',
        ];

        $creditUsageGroupedByType = $this->creditUsageGrouped();
        $defaultJsonConfig = $this->defaultChartJsonConfig();
        $isStacked = $creditUsageGroupedByType->count() > 1;

        if ($isStacked) {
            $jsonConfig = array_merge($defaultJsonConfig, [
                'legend' => [
                    'offsetY' => 5,
                ],
                'grid' => [
                    'padding' => [
                        'bottom' => 10,
                        'top' => -30,
                    ],
                ],
            ]);
        } else {
            $jsonConfig = $defaultJsonConfig;
        }

        /** @var array<string, array<int, array{title: string, value: int|float}>> $seriesColumns */
        $seriesColumns = [];

        foreach ($creditUsageGroupedByType as $type => $creditUsageGrouped) {
            $creditType = CreditType::make($type);

            [$currentDay, $today] = $this->dateRange();

            while($currentDay->lte($today)) {
                /** @var Collection $creditUsageGrouped */
                $creditsUsed = $creditUsageGrouped->get($currentDay->toDateString());

                $seriesColumns[$creditType->name()][] = [
                    'title' => $currentDay->toDateString(),
                    'value' => $creditsUsed ?? 0,
                ];

                $currentDay = $currentDay->addDay();
            }
        }

        $series = [];

        foreach ($seriesColumns as $seriesName => $columns) {
            $series[] = [
                'name' => $seriesName,
                'data' => array_column($columns, 'value'),
            ];
        }

        $options = [
            'series' => $series,
            'chart' => [
                'type' => 'bar',
                'height' => '100%',
                'stacked' => $isStacked,
                'sparkline' => ['enabled' => false],
                'toolbar' => ['show' => false],
                'animations' => ['enabled' => false],
            ],
            'legend' => [
                'show' => $isStacked,
                'position' => 'bottom',
                'horizontalAlign' => 'center',
            ],
            'grid' => [
                'show' => false,
                'position' => 'back',
                'xaxis' => ['lines' => ['show' => true]],
                'yaxis' => ['lines' => ['show' => true]],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '70%',
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'xaxis' => [
                'categories' => array_column(reset($seriesColumns) ?: [], 'title'),
            ],
            'yaxis' => [
                'title' => ['text' => __('spike::translations.daily_credit_usage')],
            ],
            'fill' => ['opacity' => 0.75],
            'theme' => ['mode' => 'light', 'palette' => 'palette1'],
            'colors' => $colors,
        ];

        return $this->deepMerge($options, $jsonConfig);
    }

    /**
     * A port of npm `deepmerge` 2.2.1, the build livewire-charts bundled to apply the JSON config. A list and an
     * object never merge (the source wins). Objects merge key by key, and the source replaces any target value
     * that is missing or falsy. Lists merge index by index: a missing index takes the source item, an array item
     * merges into the target item, and a scalar the target list does not already contain is appended.
     */
    protected function deepMerge(array $target, array $source): array
    {
        if (array_is_list($target) !== array_is_list($source)) {
            return $source;
        }

        if (array_is_list($source)) {
            $destination = $target;

            foreach ($source as $index => $item) {
                if (! array_key_exists($index, $destination)) {
                    $destination[$index] = $item;
                } elseif (is_array($item)) {
                    $destination[$index] = is_array($target[$index]) ? $this->deepMerge($target[$index], $item) : $item;
                } elseif (! in_array($item, $target, true)) {
                    $destination[] = $item;
                }
            }

            return $destination;
        }

        foreach ($source as $key => $value) {
            $targetValue = $target[$key] ?? null;
            $targetIsFalsy = ! is_array($targetValue) && ! $targetValue;

            $target[$key] = ! is_array($value) || $targetIsFalsy
                ? $value
                : $this->deepMerge($targetValue, $value);
        }

        return $target;
    }

    protected function creditUsageGrouped(): Collection
    {
        return $this->getUsageQuery()->get()
            ->groupBy(fn (CreditTransaction $tr) => $tr->credit_type->type)
            ->map(function (Collection $transactions) {
                return $transactions->groupBy(fn (CreditTransaction $tr) => $tr->created_at->toDateString())
                    ->map(fn (Collection $transactions) => abs($transactions->sum('credits') ?? 0));
            });
    }

    protected function getUsageQuery(): Builder
    {
        $dateRange = $this->dateRange();

        return CreditTransaction::query()
            ->whereBillable(Spike::resolve())
            ->onlyUsages()
            ->whereBetween('created_at', $dateRange);
    }

    protected function dateRange(): array
    {
        return [
            Carbon::today()->subMonth()->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    protected function dateRangeLabels(): array
    {
        [$start, $end] = $this->dateRange();
        $labels = [];

        $currentDay = $start->copy();
        while($currentDay->lte($end)) {
            $labels[] = $currentDay->toDateString();
            $currentDay = $currentDay->addDay();
        }

        return $labels;
    }

    protected function defaultChartJsonConfig(): array
    {
        return [
            'grid' => [
                'padding' => [
                    'bottom' => 20,
                    'top' => 0,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 0,
                ],
            ],
            'xaxis' => [
                'type' => 'datetime',
                'categories' => $this->dateRangeLabels(),
                'labels' => [
                    'rotate' => 90,
                ],
            ],
            'yaxis' => [
                'show' => false,
                'labels' => [
                    'show' => false,
                ],
            ],
        ];
    }
}
