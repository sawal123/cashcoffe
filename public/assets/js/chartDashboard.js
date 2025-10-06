document.addEventListener("livewire:navigated", () => {
    function createWidgetChart(chartId, chartColor) {
        let currentYear = new Date().getFullYear();

        var options = {
            series: [
                {
                    name: "series1",
                    data: [35, 45, 38, 41, 36, 43, 37, 55, 40],
                },
            ],
            chart: {
                type: "area",
                width: 100,
                height: 42,
                sparkline: {
                    enabled: true, // Remove whitespace
                },

                toolbar: {
                    show: false,
                },
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0,
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: "smooth",
                width: 2,
                colors: [chartColor],
                lineCap: "round",
            },
            grid: {
                show: true,
                borderColor: "transparent",
                strokeDashArray: 0,
                position: "back",
                xaxis: {
                    lines: {
                        show: false,
                    },
                },
                yaxis: {
                    lines: {
                        show: false,
                    },
                },
                row: {
                    colors: undefined,
                    opacity: 0.5,
                },
                column: {
                    colors: undefined,
                    opacity: 0.5,
                },
                padding: {
                    top: -3,
                    right: 0,
                    bottom: 0,
                    left: 0,
                },
            },
            fill: {
                type: "gradient",
                colors: [chartColor], // Set the starting color (top color) here
                gradient: {
                    shade: "light", // Gradient shading type
                    type: "vertical", // Gradient direction (vertical)
                    shadeIntensity: 0.5, // Intensity of the gradient shading
                    gradientToColors: [`${chartColor}00`], // Bottom gradient color (with transparency)
                    inverseColors: false, // Do not invert colors
                    opacityFrom: 0.75, // Starting opacity
                    opacityTo: 0.3, // Ending opacity
                    stops: [0, 100],
                },
            },
            // Customize the circle marker color on hover
            markers: {
                colors: [chartColor],
                strokeWidth: 2,
                size: 0,
                hover: {
                    size: 8,
                },
            },
            xaxis: {
                labels: {
                    show: false,
                },
                categories: [
                    `s ${currentYear}`,
                    `Feb ${currentYear}`,
                    `Mar ${currentYear}`,
                    `Apr ${currentYear}`,
                    `May ${currentYear}`,
                    `Jun ${currentYear}`,
                    `Jul ${currentYear}`,
                    `Aug ${currentYear}`,
                    `Sep ${currentYear}`,
                    `Oct ${currentYear}`,
                    `Nov ${currentYear}`,
                    `Dec ${currentYear}`,
                ],
                tooltip: {
                    enabled: false,
                },
            },
            yaxis: {
                labels: {
                    show: false,
                },
            },
            tooltip: {
                x: {
                    format: "dd/MM/yy HH:mm",
                },
            },
        };

        var chart = new ApexCharts(
            document.querySelector(`#${chartId}`),
            options
        );
        chart.render();
    }

    // =========================== Sales Statistic Line Chart Start ===============================
    var options = {
        series: [
            {
                name: "Total Omset",
                data: window.chartData.data,
            },
        ],
        chart: {
            height: 364,
            type: "line",
            toolbar: { show: true },
            zoom: { enabled: true },
            dropShadow: {
                enabled: true,
                top: 6,
                left: 0,
                blur: 4,
                color: "#000000",
                opacity: 0,
            },
        },
        dataLabels: { enabled: true },
        stroke: {
            curve: "smooth",
            colors: ["#487FFF"],
            width: 3,
        },
        markers: {
            size: 0,
            strokeWidth: 2,
            hover: { size: 8 },
        },
        tooltip: {
            enabled: true,
            x: { show: true },
            y: { show: true },
        },
        grid: {
            row: { colors: ["transparent", "transparent"], opacity: 0.5 },
            borderColor: "#D1D5DB",
            strokeDashArray: 3,
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    if (value >= 1000) {
                        // bagi 1000 → satuan ribu (K)
                        let newValue = value / 1000;
                        // cek apakah ada koma (angka desimal)
                        return (
                            newValue.toLocaleString("id-ID", {
                                minimumFractionDigits:
                                    newValue % 1 !== 0 ? 1 : 0,
                                maximumFractionDigits: 1,
                            }) + "K"
                        );
                    }
                    return value.toLocaleString("id-ID");
                },
                style: { fontSize: "11px" },
            },
        },
        xaxis: {
            categories: window.chartData.categories,
            tooltip: { enabled: false },
            labels: {
                formatter: function (value) {
                    return value;
                },
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
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
    // =========================== Sales Statistic Line Chart End ===============================
});
