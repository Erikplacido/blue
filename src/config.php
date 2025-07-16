<?php
// config.php

require_once __DIR__ . '/.env.php';
require_once __DIR__ . '/Database/Connection.php';
require_once __DIR__ . '/Helpers/functions.php';

use Src\Database\Connection;

// ✅ Moeda padrão global para todas as integrações (Stripe, relatórios, etc.)
define('DEFAULT_CURRENCY', 'aud');

// 💥 Esta linha resolve tudo:
$pdo = Connection::getInstance()->getPDO();

// PSR-4 autoloader (já está ok)
spl_autoload_register(function ($class) {
    $prefix = 'Src\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
