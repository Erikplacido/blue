<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

// 📛 Pega o nome do banco atual dinamicamente
$dbResult = $conn->query("SELECT DATABASE()");
$dbName = $dbResult->fetch_row()[0];

// 🔍 Busca todas as views no padrão user_commission_summary_nome_ano_mes
$sql = "SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW' AND Tables_in_{$dbName} LIKE 'user_commission_summary_%'";
$result = $conn->query($sql);

$viewsPorUsuario = [];

while ($row = $result->fetch_array()) {
    $viewName = $row[0];

    // Remove prefixo e separa: exemplo: user_commission_summary_joao_silva_2025_04
    if (preg_match('/^user_commission_summary_(.+)_\d{4}_\d{2}$/', $viewName, $matches)) {
        $username = $matches[1];
        $viewsPorUsuario[$username][] = $viewName;
    } else {
        // Views não reconhecidas podem ser listadas separadas
        $viewsPorUsuario['outros'][] = $viewName;
    }
}

// ✅ Interface
echo "<h2>📊 Views de Comissão por Usuário</h2>";

if (empty($viewsPorUsuario)) {
    echo "<p>❌ Nenhuma view encontrada.</p>";
} else {
    echo "<ul>";
    foreach ($viewsPorUsuario as $username => $views) {
        echo "<li><strong>" . htmlspecialchars(str_replace('_', ' ', $username)) . "</strong>";
        echo "<ul>";
        foreach ($views as $v) {
            echo "<li><a href='ver_view.php?view=" . urlencode($v) . "' target='_blank'>$v</a></li>";
        }
        echo "</ul></li>";
    }
    echo "</ul>";
}
?>