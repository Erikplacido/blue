// admin/js/user_script.js
document.addEventListener('DOMContentLoaded', () => {
  const markBtn = document.getElementById('markPaidBtn');
  const modalEl = document.getElementById('commissionModal');

  markBtn.addEventListener('click', () => {
    const view = markBtn.dataset.view;
    if (!view) return alert('View inválida.');

    markBtn.disabled    = true;
    markBtn.textContent = 'Processando…';

    fetch('/admin/ajax_mark_as_paid.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ view })
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.hide();

        setTimeout(() => {
          document.body.classList.remove('modal-open');
          document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }, 500); // espera o fade-out terminar
        document.querySelector(`.open-modal[data-view="${view}"]`)?.remove();
        alert('Comissão marcada como paga com sucesso! 🎉');
      } else {
        alert('Erro: ' + (json.message||'Não foi possível marcar como pago.'));
      }
    })
    .catch(() => alert('Erro de rede ao processar o pagamento.'))
    .finally(() => {
      markBtn.disabled    = false;
      markBtn.textContent = 'Mark as Paid';
    });
  });
});
