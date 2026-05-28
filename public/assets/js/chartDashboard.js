(() => {
    const chartId = "dashboard-sales-chart";

    function parseJsonAttribute(element, attribute) {
        try {
            return JSON.parse(element.getAttribute(attribute) || "[]");
        } catch (error) {
            return [];
        }
    }

    function formatRupiahAxis(value) {
        if (value >= 1000000) {
            return `${(value / 1000000).toLocaleString("id-ID", {
                maximumFractionDigits: 1,
            })}JT`;
        }

        if (value >= 1000) {
            return `${(value / 1000).toLocaleString("id-ID", {
                maximumFractionDigits: 1,
            })}K`;
        }

        return Number(value || 0).toLocaleString("id-ID");
    }

    function initDashboardSalesChart() {
        const element = document.getElementById(chartId);

        if (!element || typeof ApexCharts === "undefined") {
            return;
        }

        if (element._apexChart) {
            element._apexChart.destroy();
            element.innerHTML = "";
        }

        const data = parseJsonAttribute(element, "data-series").map((value) => Number(value || 0));
        const categories = parseJsonAttribute(element, "data-categories");

        const chart = new ApexCharts(element, {
            series: [
                {
                    name: "Total Omset",
                    data,
                },
            ],
            chart: {
                id: "dashboard-sales-apexchart",
                height: 364,
                type: "line",
                toolbar: { show: true },
                zoom: { enabled: true },
            },
            colors: ["#487FFF"],
            dataLabels: { enabled: false },
            stroke: {
                curve: "smooth",
                width: 3,
            },
            markers: {
                size: 4,
                strokeWidth: 2,
                hover: { size: 7 },
            },
            tooltip: {
                y: {
                    formatter: (value) => `Rp${Number(value || 0).toLocaleString("id-ID")}`,
                },
            },
            grid: {
                borderColor: "#D1D5DB",
                strokeDashArray: 3,
            },
            yaxis: {
                labels: {
                    formatter: formatRupiahAxis,
                    style: { fontSize: "11px" },
                },
            },
            xaxis: {
                categories,
                tooltip: { enabled: false },
                labels: {
                    style: { fontSize: "11px" },
                },
                axisBorder: { show: false },
                crosshairs: {
                    show: true,
                    width: 20,
                    stroke: { width: 0 },
                    fill: { type: "solid", color: "#487FFF40" },
                },
            },
            noData: {
                text: "Belum ada data penjualan",
            },
        });

        element._apexChart = chart;
        chart.render();
    }

    window.initDashboardSalesChart = initDashboardSalesChart;
    initDashboardSalesChart();

    if (!window.dashboardSalesChartListenerBound) {
        document.addEventListener("livewire:navigated", initDashboardSalesChart);
        window.dashboardSalesChartListenerBound = true;
    }
})();
