document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".open-modal").forEach(button => {
        button.addEventListener("click", event => {
            event.preventDefault(); // 👈 PREVINE comportamento padrão
            const view = button.getAttribute("data-view");

            console.log("Abrindo view:", view);

            const modalContent = document.getElementById("modalContent");
            modalContent.innerHTML = "Carregando...";

            fetch(`ajax_get_view_data.php?view=${view}`)
                .then(res => res.text())
                .then(html => {
                    modalContent.innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById("commissionModal"));
                    modal.show();
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar: ${error}</div>`;
                });
        });
    });
});
