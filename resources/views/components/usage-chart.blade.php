<div {{ $attributes->merge(['style' => 'height: 160px; ']) }}>
    @if($hasUsage && $dailyUsageChartOptions)
    <div style="width: 100%; height: 100%;" x-data="spikeUsageChart(@js($dailyUsageChartOptions))">
        <div wire:ignore x-ref="container"></div>
    </div>
    @else
    <div class="w-full h-full flex items-center justify-center text-gray-600">
        {{ __('spike::translations.daily_credit_usage_empty') }}
    </div>
    @endif
</div>
