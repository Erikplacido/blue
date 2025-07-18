<?php
// cron_dispatcher.php
date_default_timezone_set('Australia/Sydney'); // ajuste conforme seu timezone

echo "Dispatcher triggered at " . date('Y-m-d H:i:s') . "\n";

$logFile = __DIR__ . '/cron_dispatch_log.txt';

// Todos os scripts da tela:
$jobs = [
    'quote_to_referral.php',
    'reservation_to_referral.php',
    'cron_sync_service_name.php',
    'cron_sync_user_club_levels.php',
    'update_club_levels.php',
    'cron_create_monthly_commission_view.php',
    'cron_update_commission.php',
];

// Logging inicial
file_put_contents($logFile, "===== Cron Dispatcher Started: " . date('Y-m-d H:i:s') . " =====\n", FILE_APPEND);

foreach ($jobs as $job) {
    $path = __DIR__ . '/' . $job;

    if (!file_exists($path)) {
        file_put_contents($logFile, "[ERROR] $job not found at $path.\n", FILE_APPEND);
        continue;
    }

    ob_start(); // Captura saída do script
    try {
        include $path;
        $output = ob_get_clean();
        file_put_contents($logFile, "[SUCCESS] $job executed.\n", FILE_APPEND);

        if (trim($output)) {
            file_put_contents($logFile, "Output:\n$output\n", FILE_APPEND);
        }
    } catch (Throwable $e) {
        ob_end_clean();
        file_put_contents($logFile, "[FAIL] $job failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    file_put_contents($logFile, "--------------------------\n", FILE_APPEND);
}

file_put_contents($logFile, "===== Dispatcher Finished: " . date('Y-m-d H:i:s') . " =====\n\n", FILE_APPEND);
