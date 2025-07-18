document.addEventListener("DOMContentLoaded", () => {
    const modalElement = document.getElementById("commissionModal");
    const modalContent = document.getElementById("modalContent");
    const markPaidBtn = document.getElementById("markPaidBtn");

    if (!modalElement || !modalContent || !markPaidBtn) {
        console.warn("Modal or button not found in DOM.");
        return;
    }

    // 👉 Ao clicar nos cards de comissão
    document.querySelectorAll(".open-modal").forEach(button => {
        button.addEventListener("click", event => {
            event.preventDefault();

            const view = button.getAttribute("data-view");
            if (!view) {
                modalContent.innerHTML = "<div class='alert alert-warning'>View inválida</div>";
                return;
            }

            modalContent.innerHTML = "Carregando...";

            fetch(`ajax_get_view_data.php?view=${encodeURIComponent(view)}`)
                .then(response => {
                    if (!response.ok) throw new Error("Erro ao buscar dados");
                    return response.text();
                })
                .then(html => {
                    modalContent.innerHTML = html;

                    // 🔧 ESSENCIAL: Armazenar a view no modal
                    modalElement.setAttribute("data-view", view);

                    new bootstrap.Modal(modalElement).show();
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar dados: ${error.message}</div>`;
                });
        });
    });

    // 👉 Ao clicar no botão "Mark as Paid"
markPaidBtn.addEventListener("click", () => {
    const view = modalElement.getAttribute("data-view");
    const paymentReference = document.getElementById("paymentReference").value.trim();

    if (!view) {
        alert("Nenhuma view foi selecionada.");
        return;
    }

    if (!paymentReference) {
        alert("Por favor, insira o número de identificação do pagamento.");
        return;
    }

    const confirmation = confirm(
        "Please ensure all items have been paid. If you're certain, click OK to confirm."
    );

    if (!confirmation) return;

    fetch("ajax_mark_as_paid.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ view, paymentReference })
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            alert("Marked as Paid successfully!");
            location.reload();
        } else {
            alert("Failed to mark as paid: " + response.message);
        }
    })
    .catch(err => {
        alert("Error marking as paid: " + err.message);
    });
});
});
