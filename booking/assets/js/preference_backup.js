document.addEventListener('DOMContentLoaded', function () {
  // Aplica a função a todos os checkboxes no carregamento inicial
  document.querySelectorAll('.preference-checkbox').forEach(cb => {
    togglePrefNote(cb);
    cb.addEventListener('change', () => togglePrefNote(cb));
  });
});

function togglePrefNote(checkbox) {
  const note = checkbox.getAttribute('data-note');
  const noteDiv = checkbox.closest('.preferences-field')?.querySelector('.preference-note');

  if (!noteDiv) return;

  if (!checkbox.checked && note && note !== 'null') {
    try {
      const noteObj = JSON.parse(note);
      noteDiv.textContent = noteObj.note || '';
      noteDiv.style.display = 'block';
    } catch (e) {
      noteDiv.textContent = note;
      noteDiv.style.display = 'block';
    }
  } else {
    noteDiv.style.display = 'none';
  }
}