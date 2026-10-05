'use strict';
const chartSource = document.getElementById('chart-data');
if (chartSource && window.Chart) {
  const d = JSON.parse(chartSource.textContent);
  Chart.defaults.color = '#a4acc4';
  Chart.defaults.borderColor = 'rgba(165,180,220,.1)';
  const make = (id, type, labels, data, label, color) => new Chart(document.getElementById(id), {
    type, data: { labels, datasets: [{label, data, borderColor: color, backgroundColor: color + '45', fill: true, tension: .35, borderWidth: 2, borderRadius: 6}] },
    options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}, x: {grid: {display: false}}}}
  });
  make('revenue-chart', 'line', d.months, d.revenue, 'Revenue (PHP)', '#a78bfa');
  make('orders-chart', 'bar', d.months, d.orders, 'Orders', '#5bceef');
  make('products-chart', 'bar', d.products, d.quantities, 'Units ordered', '#a78bfa');
}
