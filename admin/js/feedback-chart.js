jQuery(function($){
    if (typeof feedbackChartData === 'undefined') {
        return;
    }
    var ctx = document.getElementById('feedback-chart-canvas');
    if (!ctx) {
        return;
    }
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: feedbackChartData.labels,
            datasets: [
                {
                    label: 'Ja',
                    data: feedbackChartData.yes,
                    backgroundColor: '#4CAF50'
                },
                {
                    label: 'Nein',
                    data: feedbackChartData.no,
                    backgroundColor: '#F44336'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { beginAtZero: true, stacked: true }
            }
        }
    });

    $('#download-chart').on('click', function(e){
        e.preventDefault();
        var url = chart.toBase64Image();
        var link = document.createElement('a');
        link.href = url;
        link.download = 'feedback-chart.png';
        link.click();
    });
});
