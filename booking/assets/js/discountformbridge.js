document.addEventListener("DOMContentLoaded", () => {
  const input           = document.getElementById("discountCode");
  const applyBtn        = document.getElementById("applyDiscountBtn");
  const hiddenInput     = document.getElementById("hiddenCouponCode");
  const bookingForm     = document.getElementById("bookingForm");
  const totalLabel      = document.querySelector(".summary-total");
  const modalTotalLabel = document.getElementById("totalPriceLabel");

  if (!input || !applyBtn || !hiddenInput || !bookingForm || !totalLabel || !modalTotalLabel) return;

  applyBtn.addEventListener("click", () => {
    const code = input.value.trim();
    if (!code) {
      alert("Por favor, digite um código.");
      return;
    }

    // 1) Pega o total atual (sem formatação)
    const baseTotal = parseFloat(
      totalLabel.textContent.replace(/[^\d.]/g, "")
    ) || 0;

    // 2) Chama o PHP passando código + total atual
    fetch(`/booking/validate_coupon.php?code=${encodeURIComponent(code)}&baseTotal=${baseTotal}`)
      .then(res => res.json())
      .then(data => {
        if (data.valid) {
          // 3) Guarda o cupom no form
          hiddenInput.value = code;

          // 4) Atualiza ambos os rótulos
          totalLabel.textContent      = `$${data.new_total.toFixed(2)}`;
          modalTotalLabel.textContent = `$${data.new_total.toFixed(2)}`;

          alert("✅ Cupom aplicado com sucesso!");
        } else {
          alert("❌ Cupom inválido ou expirado.");
          hiddenInput.value = "";
        }
      })
      .catch(() => {
        alert("⚠️ Não foi possível validar o cupom. Tente novamente.");
      hiddenInput.value = "";
    });
  });

  bookingForm.addEventListener("submit", () => {
    if (hiddenInput.value === "") {
      hiddenInput.value = input.value.trim();
    }
  });
});
