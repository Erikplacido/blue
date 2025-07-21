// input.js

/**
 * Máscaras de telefone por país
 */
const phoneFormats = {
  AU: {
    countryCode: '+61',
    digitCount: 9,
    pattern: [3, 3, 3], // ex: 405 102 254
  },
  // Exemplo de estrutura adicional futura:
  // US: { countryCode: '+1', digitCount: 10, pattern: [3, 3, 4] },
  // BR: { countryCode: '+55', digitCount: 11, pattern: [2, 5, 4] },
};

/**
 * Aplica máscara de telefone conforme o país
 * @param {HTMLInputElement} input - campo de input
 * @param {string} countryCode - código do país (ex: 'AU')
 */
function applyPhoneMask(input, countryCode) {
  const format = phoneFormats[countryCode];
  if (!format) return;

  input.addEventListener('input', function (e) {
    let val = e.target.value.replace(/\D/g, '');

    // Remove prefixo do código do país se digitado
    const codeDigits = format.countryCode.replace(/\D/g, '');
    if (val.startsWith(codeDigits)) {
      val = val.slice(codeDigits.length);
    }

    val = val.slice(0, format.digitCount); // limita os dígitos
    const parts = [];

    let cursor = 0;
    for (let size of format.pattern) {
      if (cursor >= val.length) break;
      parts.push(val.slice(cursor, cursor + size));
      cursor += size;
    }

    e.target.value = `${format.countryCode} ${parts.join(' ')}`;
  });
}

// Exemplo de ativação automática para campo com ID 'mobile'
document.addEventListener('DOMContentLoaded', () => {
  const mobileInput = document.getElementById('mobile');
  if (mobileInput) {
    applyPhoneMask(mobileInput, 'AU');
  }
});



// Caixa Alta'
document.addEventListener('DOMContentLoaded', () => {
  const referralInput = document.getElementById('referral_code');
  if (referralInput) {
    referralInput.addEventListener('input', function (e) {
      this.value = this.value.toUpperCase();
    });
  }
});



// TFN,ABN'
function applyNumericMask(input, maxDigits) {
  input.addEventListener('input', function (e) {
    // Remove tudo que não for número
    let val = this.value.replace(/\D/g, '');
    val = val.slice(0, maxDigits); // limita número de dígitos
    this.value = val;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const tfnInput = document.getElementById('tfn');
  const abnInput = document.getElementById('abn');

  if (tfnInput) applyNumericMask(tfnInput, 9);
  if (abnInput) applyNumericMask(abnInput, 11);
});
