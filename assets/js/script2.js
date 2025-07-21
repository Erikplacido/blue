document.addEventListener('DOMContentLoaded', function () {
  // Total e resumo
  const summaryTotal = document.querySelector('.summary-total');
  const summaryPanel = document.getElementById('summaryPanel');
  const summaryOverlay = document.getElementById('summaryPanelOverlay');
  const summaryBtn = document.getElementById('openSummaryBtn');
  const closeBtn = document.querySelector('.close-btn');

  // Manipular botões + e −
  document.body.addEventListener('click', function (e) {
    if (e.target.classList.contains('plus') || e.target.classList.contains('minus')) {
      const isPlus = e.target.classList.contains('plus');
      const counter = e.target.closest('.item-card, .extra-item');
      const qtySpan = counter.querySelector('.qty');

      let qty = parseInt(qtySpan.textContent, 10);
      qty = isPlus ? qty + 1 : Math.max(0, qty - 1);
      qtySpan.textContent = qty;

      updateTotal();
    }
  });

function updateTotal() {
  let total = 0.0;

  document.querySelectorAll('.item-card, .extra-item').forEach(el => {
    const price = parseFloat(el.getAttribute('data-price') || 0);
    const qty = parseInt(el.querySelector('.qty')?.textContent || 0, 10);
    total += price * qty;
  });

  const summaryTotal = document.querySelector('.summary-total');
  if (summaryTotal) {
    summaryTotal.textContent = `$${total.toFixed(2)}`;
  }

  const discountLine = document.getElementById('discountLine');
  if (discountLine) {
    discountLine.textContent = total > 0 ? `You saved $0.00` : '';
  }

  updateSummaryPanel();
}

function updateSummaryPanel() {
  const summaryInfo = document.getElementById('summaryInfoPanel');
  if (!summaryInfo) return;

  summaryInfo.innerHTML = '';

  document.querySelectorAll('.item-card, .extra-item').forEach(el => {
    const name = el.querySelector('h4, .extra-name')?.textContent?.trim();
    const qty = parseInt(el.querySelector('.qty')?.textContent || 0, 10);
    if (qty > 0) {
      const line = document.createElement('p');
      line.textContent = `${name} × ${qty}`;
      summaryInfo.appendChild(line);
    }
  });
}

  // Atualizar preço inicial
  updateTotal();
});
