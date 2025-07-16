// ─── 6) JS DO MENU HAMBÚRGUER ────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.getElementById("menuToggle");
  const navMenu   = document.getElementById("navMenu");
  if (toggleBtn && navMenu) {
    toggleBtn.addEventListener("click", e => {
      e.stopPropagation();
      navMenu.classList.toggle("active");
    });
    document.addEventListener("click", e => {
      if (!navMenu.contains(e.target) && e.target !== toggleBtn) {
        navMenu.classList.remove("active");
      }
    });
  }
});