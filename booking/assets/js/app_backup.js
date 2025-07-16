document.addEventListener('DOMContentLoaded', function () {
  const summaryTotal = document.querySelector('.summary-total');
  const discountLine = document.getElementById('discountLine');
  const summaryInfo = document.getElementById('summaryInfoPanel');

  // Atualiza total e painéis
  window.updateTotal = function () {
    let total = 0.0;

    // Soma todos os itens com preço e quantidade
    document.querySelectorAll('.item-card, .extra-item').forEach(el => {
      const price = parseFloat(el.getAttribute('data-price') || 0);
      const qty = parseInt(el.querySelector('.qty')?.textContent || '0', 10);
      total += price * qty;
    });

    // Adiciona taxa se o checkbox de produtos estiver desmarcado
document.querySelectorAll('.preference-checkbox').forEach(cb => {
  // lê o extra_fee vindo do backend (ex: 10, 15, etc)
  const extraFee = parseFloat(cb.getAttribute('data-extra-fee') || '0');
  // se estiver desmarcado e tiver fee > 0, soma ao total
  if (!cb.checked && extraFee > 0) {
    total += extraFee;
  }
});

    // Atualiza o valor total exibido
    if (summaryTotal) {
      summaryTotal.textContent = `$${total.toFixed(2)}`;
    }

    if (discountLine) {
      discountLine.textContent = total > 0 ? 'You saved $0.00' : '';
    }

    updateSummaryPanel();
  }

  // Atualiza painel lateral com resumo de itens
  function updateSummaryPanel() {
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

  // Manipuladores dos botões de quantidade (+ / -)
  document.body.addEventListener('click', function (e) {
    if (!e.target.classList.contains('plus') && !e.target.classList.contains('minus')) return;

    const isPlus = e.target.classList.contains('plus');
    const container = e.target.closest('.item-card, .extra-item');
    const qtySpan = container.querySelector('.qty');

    let qty = parseInt(qtySpan.textContent, 10);
    const minQty = parseInt(container.getAttribute('data-min-quantity')) || 0;

    if (isPlus) {
      qty++;
    } else {
      qty = Math.max(minQty, qty - 1);
    }

    qtySpan.textContent = qty;
    updateTotal();
  });

  // Total inicial ao carregar a página
  updateTotal();
});
