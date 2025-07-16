// Adiciona classes necessárias caso não existam
function prepareFieldClasses() {
  document.querySelectorAll('select[name="commission_type"]').forEach(el => el.classList.add('commission_type_select'));
  document.querySelectorAll('input[name="commission_fixed"]').forEach(el => el.classList.add('commission_fixed_field'));
  document.querySelectorAll('input[name="commission_amount"]').forEach(el => el.classList.add('commission_amount_field'));
}

// Alterna visibilidade dos campos de comissão
function toggleCommissionFields(selectElement) {
  const row = selectElement.closest('tr');
  const fixedField = row.querySelector('.commission_fixed_field');
  const amountField = row.querySelector('.commission_amount_field');

  if (selectElement.value === 'fixed') {
    if (fixedField) fixedField.closest('td').style.display = '';
    if (amountField) amountField.closest('td').style.display = 'none';
  } else if (selectElement.value === 'percentage') {
    if (fixedField) fixedField.closest('td').style.display = 'none';
    if (amountField) amountField.closest('td').style.display = '';
  }
}

// Alterna modo edição / visualização
document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function () {
    const row = this.closest('tr');
    const inputs = row.querySelectorAll('input, select');
    const saveBtn = row.querySelector('.save-line-btn');
    const isEditing = row.classList.toggle('editing');

    if (isEditing) {
      this.textContent = 'Cancelar';
      saveBtn.style.display = 'inline-block';

      inputs.forEach(el => {
        if (el.name !== 'id') {
          el.removeAttribute('readonly');
          if (el.tagName === 'SELECT' || el.type === 'checkbox') {
            el.disabled = false;
          }
        }
      });

      const commissionType = row.querySelector('.commission_type_select');
      if (commissionType) toggleCommissionFields(commissionType);

    } else {
      this.textContent = 'Editar';
      saveBtn.style.display = 'none';

      const original = JSON.parse(row.dataset.original);
      inputs.forEach(el => {
        const name = el.name;
        if (original[name] !== undefined) {
          if (el.type === 'checkbox') {
            el.checked = original[name] == 1;
          } else {
            el.value = original[name];
          }
        }
        if (el.name !== 'id') {
          el.setAttribute('readonly', true);
          if (el.tagName === 'SELECT' || el.type === 'checkbox') {
            el.disabled = true;
          }
        }
      });

      const fixed = row.querySelector('.commission_fixed_field');
      const amount = row.querySelector('.commission_amount_field');
      if (fixed) fixed.closest('td').style.display = '';
      if (amount) amount.closest('td').style.display = '';
    }
  });
});

// Salva via AJAX
document.querySelectorAll('.save-line-btn').forEach(button => {
  button.addEventListener('click', function () {
    const row = this.closest('tr');
    const data = new FormData();
    const id = row.querySelector('input[name="id"]').value;
    data.append('single_update', '1');
    data.append('id', id);

    row.querySelectorAll('input, select').forEach(el => {
      if (el.type === 'checkbox') {
        data.append(el.name, el.checked ? 1 : 0);
      } else {
        data.append(el.name, el.value);
      }
    });

    fetch('referrals_admin.php', {
      method: 'POST',
      body: data
    }).then(resp => {
      if (resp.ok) {
        alert('Linha atualizada com sucesso!');
        row.classList.remove('editing');
        row.querySelector('.edit-btn').textContent = 'Editar';
        row.querySelector('.save-line-btn').style.display = 'none';

        row.querySelectorAll('input, select').forEach(el => {
          if (el.name !== 'id') {
            el.setAttribute('readonly', true);
            if (el.tagName === 'SELECT' || el.type === 'checkbox') {
              el.disabled = true;
            }
          }
        });

        const updated = {};
        row.querySelectorAll('input, select').forEach(el => {
          if (el.type === 'checkbox') {
            updated[el.name] = el.checked ? 1 : 0;
          } else {
            updated[el.name] = el.value;
          }
        });
        row.dataset.original = JSON.stringify(updated);
      } else {
        alert('Erro ao salvar a linha.');
      }
    }).catch(error => {
      console.error('Erro AJAX:', error);
      alert('Erro de comunicação com o servidor.');
    });
  });
});

// Escuta mudanças no tipo de comissão
document.addEventListener('change', function (e) {
  if (e.target.matches('.commission_type_select')) {
    toggleCommissionFields(e.target);
  }
});

// Ao carregar a página
window.addEventListener('DOMContentLoaded', () => {
  prepareFieldClasses();

  document.querySelectorAll('.commission_type_select').forEach(select => {
    toggleCommissionFields(select);
  });
});