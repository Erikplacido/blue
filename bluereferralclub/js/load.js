$(document).ready(function () {
  const targetButtons = [
    '#btnShareReferral',
    '#btnGiveReferral',
    '#btnLogout',
    '#copyLink',
    '.btn-submit',
    '.menu-item'
  ];

  $(document).on('click', targetButtons.join(','), function () {
    var $btn = $(this);

    if (!$btn.hasClass('button-loading')) {
      $btn.addClass('button-loading');
      $btn.prepend('<span class="spinner"><i class="fas fa-spinner fa-spin"></i></span>');
    }

    // Remover o spinner após 2 segundos (simulação de carregamento)
    setTimeout(function () {
      $btn.removeClass('button-loading');
      $btn.find('.spinner').remove();
    }, 2000);
  });
});
