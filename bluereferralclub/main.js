document.addEventListener('DOMContentLoaded', () => {
  const modal    = document.getElementById('quoteModal');
  const leftPane = document.getElementById('modalLeft');
  const titleEl  = document.getElementById('modalServiceName');
  const form     = document.getElementById('quoteForm');
  const msgBox   = document.getElementById('messageBox');

  document.querySelectorAll('.booking-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const card = btn.closest('.card');
      titleEl.textContent = card.dataset.serviceName;
      leftPane.style.backgroundImage = `url('${card.querySelector('img').src}')`;
      form.service_id.value   = card.dataset.serviceId;
      form.service_name.value = card.dataset.serviceName;
      modal.style.display = 'flex';
    });
  });

  document.querySelector('.close-modal').addEventListener('click', () => {
    modal.style.display = 'none';
  });
  window.addEventListener('click', e => {
    if (e.target === modal) modal.style.display = 'none';
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    msgBox.textContent = 'Enviando...';
    fetch('quote.php', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        msgBox.innerHTML = data.error;
      } else {
        msgBox.textContent = data.success;
        form.reset();
      }
    })
    .catch(() => {
      msgBox.textContent = 'Erro de rede. Tente novamente.';
    });
  });
});