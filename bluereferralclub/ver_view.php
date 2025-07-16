<?php
require_once 'conexao.php';

$view = $_GET['view'] ?? '';

// ✅ Permite nomes como user_commission_summary_joao_silva_2025_04
if (!preg_match('/^user_commission_summary_[a-z0-9_]+_\d{4}_\d{2}$/i', $view)) {
    die("❌ Nome de view inválido.");
}

$result = $conn->query("SELECT * FROM `$view`");
if (!$result) {
    die("❌ Erro ao consultar view: " . $conn->error);
}

echo "<h2>📋 Dados da View: <code>" . htmlspecialchars($view) . "</code></h2>";
echo "<table border='1' cellpadding='8' cellspacing='0'><tr>";
while ($finfo = $result->fetch_field()) {
    echo "<th>" . htmlspecialchars($finfo->name) . "</th>";
}
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $cell) {
        echo "<td>" . htmlspecialchars($cell) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";