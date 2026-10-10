import Chart from 'chart.js/auto';

window.initNyumbalinkCharts = function () {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const type = canvas.dataset.chart;
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');

        if (canvas.chartInstance) {
            canvas.chartInstance.destroy();
        }

        const colors = ['#1e3a5f', '#b8860b', '#22c55e', '#ef4444', '#f59e0b', '#3b82f6'];

        canvas.chartInstance = new Chart(canvas, {
            type,
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: type === 'doughnut' ? colors.slice(0, values.length) : '#1e3a5f',
                        borderRadius: type === 'bar' ? 6 : 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type === 'doughnut' },
                },
            },
        });
    });
};

document.addEventListener('livewire:init', () => {
    window.initNyumbalinkCharts();
});

document.addEventListener('livewire:navigated', () => {
    window.initNyumbalinkCharts();
});

document.addEventListener('DOMContentLoaded', () => {
    window.initNyumbalinkCharts();
});
