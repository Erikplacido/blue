<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexao.php';

$sql = "SELECT endereco, latitude, longitude FROM locate ORDER BY criado_em DESC";
$result = $conn->query($sql);

$dados = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
}

echo json_encode($dados);
?>
