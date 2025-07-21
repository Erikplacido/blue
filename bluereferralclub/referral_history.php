<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT referral_code, first_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($referral_code, $first_name);
$stmt->fetch();
$stmt->close();

$query = $conn->prepare("
  SELECT 
    referred,
    status,
    commission_amount,
    city,
    DATE_FORMAT(created_at, '%d/%m/%Y') AS created_at
  FROM referrals
  WHERE referral_code = ?
  ORDER BY created_at DESC
");
$query->bind_param("s", $referral_code);
$query->execute();
$res = $query->get_result();

$referrals = [];
while ($row = $res->fetch_assoc()) {
    $referrals[] = $row;
}
$query->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Referral History</title>
  <link rel="stylesheet" href="css/referral_style.css">
</head>
<body>
  <header class="main-header">
    <h2>Hello, <?= htmlspecialchars($first_name) ?> — Referral History</h2>
  </header>

  <div class="container-referrals">
    <h3 class="mb-3">All Your Referrals</h3>
    
    <?php
$extratoDir = __DIR__ . '/extratos';
$extratos = [];

// Buscar extratos criados para o referral_code do usuário
foreach (glob("$extratoDir/*.html") as $file) {
    if (stripos($file, strtolower($referral_code)) !== false) {
        $extratos[] = basename($file);
    }
}
?>

<?php if (!empty($extratos)): ?>
    <h3 class="mt-5">Seus Extratos Gerados</h3>
    <ul>
        <?php foreach ($extratos as $filename): ?>
            <li>
                <a href="extratos/<?= urlencode($filename) ?>" target="_blank">
                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME)))) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

    <div class="filters">
      <input type="text" id="filterName" placeholder="Search by Name">
      <select id="filterStatus">
        <option value="">Status</option>
        <option value="Pending">Pending</option>
        <option value="Successes">Successes</option>
        <option value="Unsuccessful">Unsuccessful</option>
        <option value="Negotiating">Negotiating</option>
        <option value="Paid">Paid</option>
      </select>
      <input type="text" id="filterCity" placeholder="Search by City">
      <button class="btn-clear" id="clearFilters">Clear Filters</button>
    </div>

    <table id="referralsTable">
      <thead>
        <tr>
          <th data-sort="0">Referred</th>
          <th data-sort="1">Status</th>
          <th data-sort="2">Commission</th>
          <th data-sort="3">City</th>
          <th data-sort="4">Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($referrals) === 0): ?>
          <tr><td colspan="5" class="text-center">No referrals found.</td></tr>
        <?php else: ?>
          <?php foreach ($referrals as $row): ?>
            <tr 
              data-name="<?= strtolower(htmlspecialchars($row['referred'])) ?>"
              data-status="<?= strtolower(htmlspecialchars($row['status'])) ?>"
              data-city="<?= strtolower(htmlspecialchars($row['city'])) ?>"
            >
              <td><?= htmlspecialchars($row['referred']) ?></td>
              <td><?= htmlspecialchars($row['status']) ?></td>
              <td><?= number_format((float)$row['commission_amount'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars($row['city']) ?></td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <a href="user_dashboard.php" class="btn">← Back to Dashboard</a>
  </div>

  <!-- Script original de filtros e ordenação -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const filterName = document.getElementById("filterName");
      const filterStatus = document.getElementById("filterStatus");
      const filterCity = document.getElementById("filterCity");
      const clearBtn = document.getElementById("clearFilters");
      const rows = document.querySelectorAll("#referralsTable tbody tr");

      function applyFilters() {
        const nameVal = filterName.value.toLowerCase();
        const statusVal = filterStatus.value.toLowerCase();
        const cityVal = filterCity.value.toLowerCase();

        rows.forEach(row => {
          const rowName = row.dataset.name || "";
          const rowStatus = row.dataset.status || "";
          const rowCity = row.dataset.city || "";

          const matchName = rowName.includes(nameVal);
          const matchStatus = rowStatus.includes(statusVal);
          const matchCity = rowCity.includes(cityVal);

          row.style.display = (matchName && matchStatus && matchCity) ? "" : "none";
        });
      }

      [filterName, filterStatus, filterCity].forEach(input => {
        input.addEventListener("input", applyFilters);
        input.addEventListener("change", applyFilters);
      });

      clearBtn.addEventListener("click", () => {
        filterName.value = "";
        filterStatus.value = "";
        filterCity.value = "";
        applyFilters();
      });

      const headers = document.querySelectorAll("th[data-sort]");
      let sortDirection = 1;
      let lastSorted = null;

      headers.forEach(header => {
        header.addEventListener("click", () => {
          const index = parseInt(header.dataset.sort);
          const tbody = document.querySelector("#referralsTable tbody");
          const rowsArray = Array.from(tbody.querySelectorAll("tr"));

          if (lastSorted === index) {
            sortDirection *= -1;
          } else {
            sortDirection = 1;
            lastSorted = index;
          }

          rowsArray.sort((a, b) => {
            const aText = a.children[index].innerText.trim().toLowerCase();
            const bText = b.children[index].innerText.trim().toLowerCase();
            return (aText < bText ? -1 : aText > bText ? 1 : 0) * sortDirection;
          });

          rowsArray.forEach(row => tbody.appendChild(row));
        });
      });
    });
  </script>
</body>
</html>