document.addEventListener("DOMContentLoaded", function () {
    const rows = document.querySelectorAll("#reservationsTable tbody tr");

    const filterName = document.getElementById("filterName");
    const filterEmail = document.getElementById("filterEmail");
    const filterStatus = document.getElementById("filterStatus");
    const filterReferralCode = document.getElementById("filterReferralCode");
    const clearBtn = document.getElementById("clearFilters");

    function applyFilters() {
        const nameVal = filterName.value.toLowerCase();
        const emailVal = filterEmail.value.toLowerCase();
        const statusVal = filterStatus.value.toLowerCase();
        const referralCodeVal = filterReferralCode.value.toLowerCase();

        rows.forEach(row => {
            const rowName = row.dataset.name?.toLowerCase() || "";
            const rowEmail = row.dataset.email?.toLowerCase() || "";
            const rowStatus = row.dataset.status?.toLowerCase() || "";
            const rowReferralCode = row.dataset.referralCode?.toLowerCase() || "";

            const matchesName = rowName.includes(nameVal);
            const matchesEmail = rowEmail.includes(emailVal);
            const matchesStatus = rowStatus.includes(statusVal);
            const matchesReferralCode = rowReferralCode.includes(referralCodeVal);

            const isVisible = matchesName && matchesEmail && matchesStatus && matchesReferralCode;
            row.style.display = isVisible ? "" : "none";
        });
    }

    // Adiciona eventos aos filtros
    [filterName, filterEmail, filterStatus, filterReferralCode].forEach(input => {
        input.addEventListener("input", applyFilters);
        input.addEventListener("change", applyFilters);
    });

    // Botão limpar filtros
    if (clearBtn) {
        clearBtn.addEventListener("click", () => {
            filterName.value = "";
            filterEmail.value = "";
            filterStatus.value = "";
            filterReferralCode.value = "";
            applyFilters();
        });
    }
});
