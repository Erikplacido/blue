// bluereferralclub/js/login.js

// 1) Alterna entre login e “forgot password”
document.getElementById('forgotLink').addEventListener('click', e => {
  e.preventDefault();
  document.getElementById('loginForm').style.display = 'none';
  document.getElementById('forgotForm').style.display = 'block';
});

// 2) Função de toggle da senha (deixada global para o onclick inline)
function togglePassword(el, inputId) {
  const input = document.getElementById(inputId);
  const img   = el.querySelector('img');
  if (input.type === 'password') {
    input.type = 'text';
    img.src     = '/bluereferralclub/assest/img/eye-off.svg';
    img.alt     = 'Hide password';
  } else {
    input.type = 'password';
    img.src     = '/bluereferralclub/assest/img/eye.svg';
    img.alt     = 'Show password';
  }
}
window.togglePassword = togglePassword;

// 3) Fetch para enviar reset de senha, com checagem de Content-Type
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  try {
    const res = await fetch('/bluereferralclub/send_reset_email.php', {
      method: 'POST',
      body: formData
    });

    // — Se não for 2xx, já para aqui
    if (!res.ok) {
      console.error('Server error:', await res.text());
      alert(`Server error (${res.status}). Check console.`);
      return;
    }

    // — Garante que o retorno é JSON antes de parsear
    const ct = res.headers.get('Content-Type') || '';
    if (!ct.includes('application/json')) {
      const text = await res.text();
      console.error('Invalid JSON response:', text);
      alert('Invalid response from server. Check console.');
      return;
    }

    // — Agora sim, parse e retorna a mensagem
    const data = await res.json();
    alert(data.success || data.error);

    if (data.success) {
      this.reset();
      this.style.display = 'none';
      document.getElementById('loginForm').style.display = 'block';
    }

  } catch (err) {
    console.error('Network/parsing error:', err);
    alert('Network error or invalid response.');
  }
});