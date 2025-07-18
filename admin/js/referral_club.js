document.addEventListener("DOMContentLoaded", function () {
    // Modal open/close
    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = "block";
    };

    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = "none";
    };

    // Close modal when clicking outside
    window.onclick = function (event) {
        const modals = document.querySelectorAll(".custom-modal");
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    };

    // Form validation (basic)
    const form = document.querySelector("form[action='insert_user.php']");
    if (form) {
        form.addEventListener("submit", function (e) {
            const email = form.querySelector("[name='email']");
            const password = form.querySelector("[name='password']");

            if (!email.value || !password.value) {
                alert("Email and Password are required.");
                e.preventDefault();
            }
        });
    }
});
