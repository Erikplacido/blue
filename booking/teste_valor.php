<?php
// bookingx/teste_valor.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1) Bootstrapping
require __DIR__ . '/../src/config.php';
use Src\Models\Service;
use Src\Models\Booking;
use Src\Database\Connection;

// 2) Carrega o serviço “house-cleaning”
$serviceModel = new Service();
$service = $serviceModel->getBySlug('house-cleaning');
if (!$service) {
    die("Serviço 'house-cleaning' não encontrado.");
}
$serviceArr = [
    'id'         => $service['id'],
    'base_price' => $service['base_price']
];

// 3) Dados de formulário de teste
$testFormData = [
    'included_qty' => [
        // supondo que 1=Bedroom, 2=Bathroom, 3=Floors
        1 => 4,
        2 => 2,
        3 => 1,
    ],
    'extra_qty' => [
        // ids de extras com quantidade
        // exemplo: 4=>1, 5=>0 …
    ],
];

// 4) Prefs de teste: usa o default do banco
$db = Connection::getInstance()->getPDO();
$stmt = $db->query("SELECT id, is_checked_default FROM preference_fields WHERE field_type='checkbox'");
$defaults = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
// agora $defaults[id] = 1 ou 0
$testFormData['preferences'] = [];
foreach ($defaults as $id => $isDefault) {
    // marca 1 se default=1, senão 0 (forçando a lógica de extra_fee)
    $testFormData['preferences'][(int)$id] = (int)$isDefault;
}

// 5) Calcula total via método PHP
$phpTotal = Booking::calculateTotal($testFormData, $serviceArr);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Teste de Valor - Debug</title>
  <style>
    body { font-family: sans-serif; margin: 2rem; }
    pre { background: #f5f5f5; padding: 1rem; overflow-x: auto; }
    h2 { margin-top: 2rem; }
  </style>
</head>
<body>
  <h1>🛠️ Página de Teste de Cálculo de Valor</h1>

  <h2>1) Dados de Teste em PHP</h2>
  <pre><?php echo htmlspecialchars(print_r($testFormData, true)); ?></pre>

  <h2>2) Total calculado pelo PHP</h2>
  <p><strong>Booking::calculateTotal(...) = $<?php echo number_format($phpTotal, 2); ?></strong></p>

  <h2>3) Código de <code>app.js</code></h2>
  <pre><?php 
    echo htmlspecialchars(
      file_get_contents(__DIR__ . '/assets/js/app.js')
    );
  ?></pre>

  <h2>4) Código de <code>preference.js</code></h2>
  <pre><?php 
    echo htmlspecialchars(
      file_get_contents(__DIR__ . '/assets/js/preference.js')
    );
  ?></pre>

  <h2>5) Código de <code>discountformbridge.js</code></h2>
  <pre><?php 
    echo htmlspecialchars(
      file_get_contents(__DIR__ . '/assets/js/discountformbridge.js')
    );
  ?></pre>

  <h2>6) Para comparar: UI de teste</h2>
  <!-- um exemplo mínimo de UI que executa updateTotal() -->
  <div>
    <div class="item-card" data-price="20" data-min-quantity="0">
      <h4>Bedroom</h4>
      <button class="minus">−</button>
      <span class="qty">4</span>
      <input type="hidden" name="included_qty[1]" value="4">
      <button class="plus">+</button>
    </div>
    <div class="item-card" data-price="40" data-min-quantity="0">
      <h4>Bathroom</h4>
      <button class="minus">−</button>
      <span class="qty">2</span>
      <input type="hidden" name="included_qty[2]" value="2">
      <button class="plus">+</button>
    </div>
    <div class="item-card" data-price="40" data-min-quantity="0">
      <h4>Floors</h4>
      <button class="minus">−</button>
      <span class="qty">1</span>
      <input type="hidden" name="included_qty[3]" value="1">
      <button class="plus">+</button>
    </div>
    <div>
      Total (JS): <span class="summary-total">$0.00</span>
    </div>
  </div>

  <script src="assets/js/preference.js"></script>
  <script src="assets/js/app.js"></script>
  <script>
    // Força o cálculo inicial
    document.addEventListener('DOMContentLoaded', () => {
      // simula preferências (nenhuma desmarcada neste exemplo)
      document.querySelectorAll('.preference-checkbox').forEach(cb => {
        cb.checked = true;
      });
      updateTotal();
    });
  </script>
</body>
</html>