(function() {
    document.addEventListener("DOMContentLoaded", function () {
        var ctx = document.getElementById('recommendedChart').getContext('2d');

        // Access the localized data for labels and datasets
        var labels = recommendedChartData.labels;
        var datasets = recommendedChartData.datasets;

        // Create the chart
        var recommendedChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Disable the default legend
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });

        // Function to update dataset visibility based on dropdown selection
        function updateDatasetVisibility() {
            var select = document.getElementById('legendDropdown');
            var selectedValue = select.value;

            recommendedChart.data.datasets.forEach(function (dataset, index) {
                if (selectedValue === "all") {
                    dataset.hidden = false; // Show all datasets
                } else {
                    dataset.hidden = index !== parseInt(selectedValue); // Show only the selected dataset
                }
            });

            recommendedChart.update(); // Update the chart to reflect changes
        }

        // Attach the function to the dropdown change event
        document.getElementById('legendDropdown').addEventListener('change', updateDatasetVisibility);
    });
})();
