  document.getElementById('registrationForm')
          .addEventListener('submit', function (e) {
    e.preventDefault();                               // bloqueia o POST
    alert('Password saved locally. Click OK to continue.');
    window.location.href = 'index.php';               // vai para home
  });