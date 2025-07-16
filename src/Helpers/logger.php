<?php
function log_event(string $message, string $file = 'stripe.log'): void {
    $logPath = __DIR__ . '/../../logs/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logPath, $log, FILE_APPEND);
}
?>