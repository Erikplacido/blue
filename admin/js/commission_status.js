document.addEventListener("DOMContentLoaded", () => {
    const selType   = document.getElementById('commission_type');
    const inpFixed  = document.getElementById('commission_fixed');
    const inpPct    = document.getElementById('commission_percentage');
    const inpBook   = document.getElementById('booking_value');
    const inpAmount = document.getElementById('commission_amount');

    const grpFixed  = document.getElementById('group_fixed');
    const grpPct    = document.getElementById('group_percentage');

    function toggleGroups() {
        if (selType.value === 'fixed') {
            grpFixed.style.display = '';
            grpPct.style.display   = 'none';
        } else {
            grpFixed.style.display = 'none';
            grpPct.style.display   = '';
        }
    }

    function calcAmount() {
        const booking = parseFloat(inpBook.value) || 0;
        let   amount  = 0;

        if (selType.value === 'fixed') {
            amount = parseFloat(inpFixed.value) || 0;
        } else { // percentage
            const pct = parseFloat(inpPct.value) || 0;
            amount = booking * pct / 100;
        }
        inpAmount.value = amount.toFixed(2);
    }

    // reagir a todas as mudanças
    [selType, inpFixed, inpPct, inpBook].forEach(el =>
        el.addEventListener('input', calcAmount)
    );
    selType.addEventListener('change', () => {
        toggleGroups();
        calcAmount();
    });

    // inicialização
    toggleGroups();
    calcAmount();
});
