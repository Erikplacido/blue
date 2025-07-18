document.addEventListener("DOMContentLoaded", function () {
    const table = document.getElementById("reservationsTable");
    const headers = table.querySelectorAll("th[data-sort]");
    let sortDirection = 1; // 1 = asc, -1 = desc
    let lastSortedColumn = null;

    headers.forEach(header => {
        header.addEventListener("click", () => {
            const columnIndex = parseInt(header.getAttribute("data-sort"));
            const rows = Array.from(table.querySelectorAll("tbody tr"));

            // Alterna entre ascendente e descendente
            if (lastSortedColumn === columnIndex) {
                sortDirection *= -1;
            } else {
                sortDirection = 1;
                lastSortedColumn = columnIndex;
            }

            rows.sort((a, b) => {
                const aText = a.children[columnIndex].innerText.trim();
                const bText = b.children[columnIndex].innerText.trim();

                // Tenta converter para número se possível
                const aVal = isNaN(aText) ? aText : Number(aText);
                const bVal = isNaN(bText) ? bText : Number(bText);

                if (aVal < bVal) return -1 * sortDirection;
                if (aVal > bVal) return 1 * sortDirection;
                return 0;
            });

            // Reinsere as linhas ordenadas
            const tbody = table.querySelector("tbody");
            rows.forEach(row => tbody.appendChild(row));
        });
    });
});
