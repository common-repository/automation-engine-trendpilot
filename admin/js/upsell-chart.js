(function() {
    document.addEventListener("DOMContentLoaded", function () {
        var ctx = document.getElementById('upsellChart').getContext('2d');

        // Access the localized data for labels and datasets
        var labels = upsellChartData.labels;
        var data = upsellChartData.data;
        var backgroundColor = upsellChartData.backgroundColor;
        var borderColor = upsellChartData.borderColor;

        // Create the chart
        var upsellChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '# of Upsell Clicks',
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        beginAtZero: false // Ensure this is correct for your data
                    }
                }
            }
        });
    });
})();
