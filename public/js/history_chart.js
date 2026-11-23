(function () {
    'use strict';

    const chartEl = document.getElementById('history-chart');
    if (!chartEl || typeof Chart === 'undefined') {
        return;
    }

    new Chart(chartEl, {
        type: 'line',
        data: {
            labels: JSON.parse(chartEl.dataset.labels || '[]'),
            datasets: [{
                label: 'Response Time (ms)',
                data: JSON.parse(chartEl.dataset.values || '[]'),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.25,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Milliseconds' }
                },
                x: {
                    title: { display: true, text: 'Time Checked' }
                }
            }
        }
    });
})();
