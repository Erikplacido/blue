<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../conexao.php';

$conn->query("
  UPDATE users u
  JOIN (
    SELECT
      id,
      CASE
        WHEN Successes BETWEEN 1 AND 50 THEN 1
        WHEN Successes BETWEEN 51 AND 119 THEN 2
        WHEN Successes >= 120 THEN 3
        ELSE NULL
      END AS new_level_id
    FROM users
    WHERE user_type = 'referral member'
  ) AS temp ON u.id = temp.id
  LEFT JOIN referral_club_data rcd ON rcd.id = temp.new_level_id
  SET
    u.referral_club_level_id = temp.new_level_id,
    u.referral_club_level_name = rcd.level
");

echo "✔️ Referral levels updated!";