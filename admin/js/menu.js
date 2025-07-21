document.addEventListener("DOMContentLoaded", function () {
    const settingsToggle = document.querySelectorAll(".toggle-submenu");

    settingsToggle.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault(); // ✅ Impede que o link '#' recarregue a página
            const parentLi = this.closest("li");
            const submenu = parentLi.querySelector(".submenu");

            if (submenu) {
                parentLi.classList.toggle("open");
            }
        });
    });
});
