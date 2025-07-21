document.addEventListener('DOMContentLoaded', function () {

    // Habilitar edição do status
    document.querySelectorAll('.enable-status-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const select = this.previousElementSibling;
            if (select && select.tagName === 'SELECT') {
                select.disabled = false;
                select.focus();
                this.disabled = true; // botão "Edit" fica desabilitado enquanto edição ocorre
            }
        });
    });

    // Listener delegando evento para qualquer select com classe 'status-select'
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('status-select')) {
            const select = e.target;
            const id = select.dataset.id;
            const newStatus = select.value;

            fetch('actions/update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erro ao atualizar o status: ' + (data.error || 'Erro desconhecido'));
                } else {
                    alert('Status atualizado com sucesso!');
                    select.disabled = true;

                    // Reabilita o botão "Edit" relacionado ao select
                    const editBtn = select.parentElement.querySelector('.enable-status-btn');
                    if (editBtn) {
                        editBtn.disabled = false;
                    }

                    // Atualiza o atributo data-status da <tr> para manter filtro funcional
                    const row = select.closest('tr');
                    if (row) {
                        row.dataset.status = newStatus.toLowerCase();
                    }
                }
            })
            .catch(err => {
                console.error('Erro na atualização do status:', err);
                alert('Erro de comunicação com o servidor.');
            });
        }
    });

});