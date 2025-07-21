<?php
// Habilita erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../src/config.php';

use Src\Models\Address;

session_start();
$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    header('Location: login.php');
    exit;
}

// Dados recebidos pela URL
$address_line1 = $_GET['address'] ?? '';
$postcode      = $_GET['postcode'] ?? '';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $postcode      = trim($_POST['postcode'] ?? '');
    $latitude      = trim($_POST['latitude'] ?? '');
    $longitude     = trim($_POST['longitude'] ?? '');

    if (!$address_line1) $errors['address_line1'] = 'Preencha o endereço.';
    if (!$postcode)      $errors['postcode']      = 'Preencha o CEP.';

    if (empty($errors)) {
        $addressModel = new Address();
        $saved = $addressModel->create([
            'customer_id'    => $customerId,
            'label'          => 'Endereço salvo da reserva',
            'address_line1'  => $address_line1,
            'address_line2'  => $address_line2,
            'city'           => $city,
            'state'          => $state,
            'postcode'       => $postcode,
            'latitude'       => $latitude,
            'longitude'      => $longitude,
        ]);

        if ($saved) {
            $success = 'Endereço salvo com sucesso!';
            header('Location: profile.php');
            exit;
        } else {
            $errors['general'] = 'Erro ao salvar o endereço.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Endereço</title>
</head>
<body>
    <h2>Novo Endereço</h2>

    <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
        <p style="color: red;"><?= $errors['general'] ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Endereço*:<br>
            <input name="address_line1" value="<?= htmlspecialchars($address_line1) ?>">
            <span style="color: red;"><?= $errors['address_line1'] ?? '' ?></span>
        </label><br><br>

        <label>Complemento:<br>
            <input name="address_line2" value="<?= htmlspecialchars($address_line2 ?? '') ?>">
        </label><br><br>

        <label>Cidade:<br>
            <input name="city" value="<?= htmlspecialchars($city ?? '') ?>">
        </label><br><br>

        <label>Estado:<br>
            <input name="state" value="<?= htmlspecialchars($state ?? '') ?>">
        </label><br><br>

        <label>CEP*:<br>
            <input name="postcode" value="<?= htmlspecialchars($postcode) ?>">
            <span style="color: red;"><?= $errors['postcode'] ?? '' ?></span>
        </label><br><br>

        <label>Latitude:<br>
            <input name="latitude" value="<?= htmlspecialchars($latitude ?? '') ?>">
        </label><br><br>

        <label>Longitude:<br>
            <input name="longitude" value="<?= htmlspecialchars($longitude ?? '') ?>">
        </label><br><br>

        <button type="submit">Salvar Endereço</button>
    </form>

    <p><a href="profile.php">← Voltar para o perfil</a></p>
</body>
</html>