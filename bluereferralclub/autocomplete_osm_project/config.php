<?php
$servername = "localhost";
$username = "SEU_USUARIO";
$password = "SUA_SENHA";
$dbname = "SEU_BANCO";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
