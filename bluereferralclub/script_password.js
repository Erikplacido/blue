// script_password.js

// Validação do formulário (como exemplo, igual ao snippet anterior)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('changePasswordForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      const newPassword = document.getElementById('newPassword');
      const confirmPassword = document.getElementById('confirmPassword');
      if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('New passwords do not match.');
      }
    });
  }
});

// Declarando a função no escopo global
function toggleAllPasswords(el) {
  const fields = ['currentPassword', 'newPassword', 'confirmPassword'];
  const icon = el.querySelector('img');
  const textEl = el.querySelector('#toggleText');

  // Verifica se algum campo já está visível (tipo "text")
  let isVisible = fields.some(id => {
    const input = document.getElementById(id);
    return input && input.type === 'text';
  });

  // Alterna entre "password" e "text" para cada campo
  fields.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.type = isVisible ? 'password' : 'text';
    }
  });

  // Atualiza o ícone e o texto conforme o estado atual
  if (icon) {
    icon.src = isVisible ? 'assets/img/eye.svg' : 'assets/img/eye-off.svg';
    icon.alt = isVisible ? 'Show Passwords' : 'Hide Passwords';
  }
  if (textEl) {
    textEl.innerText = isVisible ? 'Show Password' : 'Hide Password';
  }
}

// Se preferir garantir explicitamente que está global
window.toggleAllPasswords = toggleAllPasswords;
