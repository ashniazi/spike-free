/*
 * Spike's daily usage chart: an Alpine component around ApexCharts (window.ApexCharts, loaded before this file).
 *
 * One chart per element: init() clears any earlier instance first, and destroy() (called by Alpine when the element
 * is removed, including on Livewire navigation or morphs) tears it down, so a page never holds duplicate charts.
 * The draw waits a tick, as livewire-charts did, so the container has its final size before ApexCharts measures it.
 */
window.spikeUsageChart = function (options) {
    return {
        chart: null,
        drawTimer: null,

        init() {
            this.teardown();

            this.drawTimer = setTimeout(() => {
                this.drawTimer = null;
                this.chart = new window.ApexCharts(this.$refs.container, options);
                this.chart.render();
            }, 0);
        },

        destroy() {
            this.teardown();
        },

        teardown() {
            if (this.drawTimer !== null) {
                clearTimeout(this.drawTimer);
                this.drawTimer = null;
            }

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
    };
};
