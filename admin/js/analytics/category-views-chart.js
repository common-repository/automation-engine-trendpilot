(function() {
    document.addEventListener("DOMContentLoaded", function () {
        var ctx = document.getElementById('categoryViewChart').getContext('2d');

        // Define colors for the datasets
        var colors = [
            "rgba(63, 81, 181, 0.8)", // Indigo
            "rgba(103, 58, 183, 0.8)", // Deep Purple
            "rgba(33, 150, 243, 0.8)", // Blue
            "rgba(156, 39, 176, 0.8)", // Purple
            "rgba(3, 169, 244, 0.8)"   // Light Blue
        ];

        // Access the localized data for labels and datasets
        var labels = categoryViewsChartData.labels;
        var datasets = categoryViewsChartData.datasets;

        // Apply colors and line tension to datasets
        datasets.forEach(function(dataset, index) {
            dataset.borderColor = colors[index % colors.length];
            dataset.backgroundColor = 'rgba(0,0,0,0)'; // No fill for line charts
            dataset.fill = false;
            dataset.tension = 0.4; // Set line tension for curved lines
        });

        // Create the chart with Chart.js
        var categoryViewChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Change to true if legend is needed
                    },
                    tooltip: {
                        mode: 'nearest',
                        intersect: true,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Views'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    });
})();
