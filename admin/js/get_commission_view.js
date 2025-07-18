// js/get_commission_view.js
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    const cards   = document.querySelectorAll('.open-modal');
    const modalEl = document.getElementById('commissionModal');
    const markBtn = document.getElementById('markPaidBtn');

    cards.forEach(card => {
      card.addEventListener('click', function() {
        const viewName = this.dataset.view;
        fetch(`/admin/ajax_get_view_data.php?view=${encodeURIComponent(viewName)}`)
          .then(response => response.text())
          .then(html => {
            // injeta o HTML de detalhes
            document.getElementById('modalContent').innerHTML = html;
            
            // marca o botão com a view correta
            markBtn.setAttribute('data-view', viewName);

            // exibe o modal
            new bootstrap.Modal(modalEl).show();
          })
          .catch(err => {
            console.error('Erro ao carregar dados:', err);
            document.getElementById('modalContent')
              .innerHTML = `<div class="alert alert-danger">Erro ao carregar: ${err.message}</div>`;
          });
      });
    });
  });
})();
