<?php
// conexao.php

// [1] Ajuste as variáveis abaixo conforme seu ambiente:
$host   = 'localhost';
$dbname = 'u979853733_BFS';
$user   = 'u979853733_blue';
$pass   = 'BlueM@rketing33';

// [2] Conexão mysqli
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ───────────────────────────────────────────────────────────
// [3] Conexão PDO — usado no sistema:
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // ✅ Definindo o timezone da sessão MySQL para Melbourne:
    $pdo->exec("SET time_zone = '+10:00'"); // ou '+11:00' no horário de verão
    // Alternativa, se suportado: $pdo->exec("SET time_zone = 'Australia/Melbourne'");
} catch (PDOException $e) {
    die("Falha na conexão PDO: " . $e->getMessage());
}
?>
