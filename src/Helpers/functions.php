<?php

function env(string $key, $default = null) {
    static $vars = null;

    if ($vars === null) {
        $vars = require __DIR__ . '/../.env.php';
    }

    return $vars[$key] ?? $default;
}

function formatPrice(float $amount): string {
    return '$' . number_format($amount, 2);
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
