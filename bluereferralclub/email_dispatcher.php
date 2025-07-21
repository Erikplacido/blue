<?php
// [1] Constantes para uso global
define('DB_HOST', 'localhost');
define('DB_USER', 'u979853733_blue');
define('DB_PASS', 'BlueM@rketing33');
define('DB_NAME', 'u979853733_BFS');

// [2] Mysqli
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados (mysqli): " . $conn->connect_error);
}
$conn->set_charset("utf8");

// [3] PDO
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Falha na conexão (PDO): " . $e->getMessage());
}
?>
