document.addEventListener("DOMContentLoaded", function () {
  // Toggle do menu de conta
  const toggle = document.getElementById("accountToggle");
  const menu = document.getElementById("accountMenu");

  toggle.addEventListener("click", function (event) {
    event.stopPropagation();
    menu.classList.toggle("active");
  });

  document.addEventListener("click", function (event) {
    if (!menu.contains(event.target)) {
      menu.classList.remove("active");
    }
  });

  // Abrir modal ao clicar em "Give a Referral"
  const btnGiveReferral = document.getElementById("btnGiveReferral");
  if (btnGiveReferral) {
    btnGiveReferral.addEventListener("click", function () {
      openModal('/bluereferralclub/referral/index.php');
    });
  }

  // Adicionar evento de clique nos links de modal do menu
  const modalLinks = document.querySelectorAll('[data-modal]');
  modalLinks.forEach(link => {
    link.addEventListener('click', function (event) {
      event.preventDefault();
      const url = link.getAttribute('data-modal');
      openModal(url);
    });
  });

  // Função para abrir o modal
  function openModal(url) {
    const modal = document.getElementById('formModal');
    const body = document.getElementById('modalBody');

    fetch(url)
      .then(response => response.text())
      .then(html => {
        body.innerHTML = html;
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // 🔒 Bloqueia rolagem da página
      })
      .catch(error => {
        console.error("Erro ao carregar o modal:", error);
      });

    const closeButton = document.querySelector(".close-button");
    if (closeButton) {
      closeButton.onclick = () => {
        closeModal();
      };
    }

    window.onclick = (event) => {
      if (event.target == modal) {
        closeModal();
      }
    };

    // Fechar o modal com a tecla ESC
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeModal();
      }
    });
  }

  // Função para fechar o modal e liberar o scroll
  function closeModal() {
    const modal = document.getElementById('formModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // 🔓 Libera rolagem da página
  }
});
