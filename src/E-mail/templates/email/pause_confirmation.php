<!-- /templates/email/pause_confirmation.php -->
<p>Olá <?= htmlspecialchars($customerName) ?>,</p>
<p>Sua assinatura foi pausada por 1 mês. Retomaremos as cobranças em <?= date('d/m/Y', strtotime('+1 month')) ?>.</p>
<p>Qualquer dúvida, estamos à disposição.</p>