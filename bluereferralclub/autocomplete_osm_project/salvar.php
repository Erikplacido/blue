<?php
// Mostrar erros (debug em desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeçalho de resposta em JSON
header('Content-Type: application/json');

// Conexão com o banco
require_once __DIR__ . '/../conexao.php';

// Leitura e validação do corpo JSON
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Verifica se veio JSON válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Erro ao decodificar JSON: ' . json_last_error_msg(),
        'debug' => $rawData
    ]);
    exit;
}

// Verifica se campos obrigatórios estão presentes
if (!$data || !isset($data['endereco'], $data['lat'], $data['lon'])) {
    http_response_code(422);
    echo json_encode(['message' => 'Campos obrigatórios ausentes.']);
    exit;
}

// Prepara e escapa dados
$endereco = $conn->real_escape_string($data['endereco']);
$lat = $conn->real_escape_string($data['lat']);
$lon = $conn->real_escape_string($data['lon']);

// Comando SQL de inserção na nova tabela "locate"
$sql = "INSERT INTO locate (endereco, latitude, longitude) VALUES ('$endereco', '$lat', '$lon')";

// Executa e retorna resposta
if ($conn->query($sql) === TRUE) {
    echo json_encode(['message' => 'Endereço salvo com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erro ao salvar no banco.',
        'erro_sql' => $conn->error
    ]);
}
?>
