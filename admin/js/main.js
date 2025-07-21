const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [{
      label: 'Vendas',
      data: [3000, 4000, 3200, 5000, 6200, 7100],
      backgroundColor: 'rgba(99, 102, 241, 0.2)', // Indigo-500 transparente
      borderColor: 'rgba(99, 102, 241, 1)',
      borderWidth: 3,
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true
      }
    }
  }
});
