(function () {
    'use strict';

    const chartEl = document.getElementById('history-chart');
    if (!chartEl || typeof Chart === 'undefined') {
        return;
    }

    const labels = JSON.parse(chartEl.dataset.labels || '[]');
    const values = JSON.parse(chartEl.dataset.values || '[]');

    const ctx = chartEl.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.18)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

    new Chart(chartEl, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Response Time (ms)',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: gradient,
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
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    title: { display: true, text: 'Milliseconds' }
                },
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 40, autoSkip: true, maxTicksLimit: 10 },
                    title: { display: true, text: 'Time Checked' }
                }
            }
        }
    });
})();
