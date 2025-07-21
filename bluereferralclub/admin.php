<?php
require_once 'conexao.php';

// Delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<p class='success'>User successfully deleted!</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error deleting user: " . $e->getMessage() . "</p>";
    }
}

// Save user edit
if (isset($_POST['edit_user'])) {
    $id = intval($_POST['user_id']);
    $data = [
        ':abn' => $_POST['abn'] ?? null,
        ':email' => $_POST['email'] ?? '',
        ':first_name' => $_POST['first_name'] ?? '',
        ':last_name' => $_POST['last_name'] ?? '',
        ':referral_code' => $_POST['referral_code'] ?? null,
        ':user_type' => $_POST['user_type'] ?? '',
        ':id' => $id,
    ];

    if (empty($data[':email']) || empty($data[':first_name']) || empty($data[':last_name']) || empty($data[':user_type'])) {
        echo "<p class='error'>Please fill out all required fields to edit the user.</p>";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE users SET
                    abn = :abn,
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    referral_code = :referral_code,
                    user_type = :user_type
                WHERE id = :id
            ");
            $stmt->execute($data);
            echo "<p class='success'>User updated successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error updating user: " . $e->getMessage() . "</p>";
        }
    }
}

// Create user
if (isset($_POST['create_user'])) {
    $email = $_POST['email'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($user_type)) {
        echo "<p class='error'>Please fill out all required fields to create a user.</p>";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (abn, email, first_name, last_name, password, referral_code, user_type)
                VALUES (:abn, :email, :first_name, :last_name, :password, :referral_code, :user_type)
            ");
            $stmt->execute([
                ':abn' => $_POST['abn'] ?? null,
                ':email' => $email,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':password' => $hashedPassword,
                ':referral_code' => $_POST['referral_code'] ?? null,
                ':user_type' => $user_type,
            ]);
            echo "<p class='success'>User created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error creating user: " . $e->getMessage() . "</p>";
        }
    }
}

// Fetch only "referral member" users
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'referral member' ORDER BY id DESC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<p class='error'>Error fetching users: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en-AU">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="stylesheet" href="css/admin_users.css">
</head>
<body>

<div class="container">
  <header>
    <h1>Manage Users</h1>
  </header>

  <main class="dashboard-grid">

    <!-- Create User Card -->
    <section class="card">
      <h2>Create New User</h2>
      <form method="POST">
        <input type="hidden" name="create_user" value="1">

        <label>ABN (optional):<input type="text" name="abn"></label>
        <label>Email*:<input type="email" name="email" required></label>
        <label>First Name*:<input type="text" name="first_name" required></label>
        <label>Last Name*:<input type="text" name="last_name" required></label>
        <label>Password*:<input type="password" name="password" required></label>
        <label>Referral Code (optional):<input type="text" name="referral_code"></label>
        <label>User Type*:
          <select name="user_type" required>
            <option value="">Select...</option>
            <option value="consumer">Consumer</option>
            <option value="cleaner">Cleaner</option>
            <option value="referral member">Referral Member</option>
            <option value="admin member">Admin</option>
            <option value="super admin">Super Admin</option>
          </select>
        </label>
        <button type="submit" class="btn-primary">Create User</button>
      </form>
    </section>

    <!-- User List Card -->
    <section class="card table-wrapper">
      <h2>Users List</h2>

      <?php if (!empty($usuarios)): ?>
        <table>
          <thead>
            <tr>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $usuario): ?>
              <tr>
                <td><?= htmlspecialchars($usuario['first_name']) ?></td>
                <td><?= htmlspecialchars($usuario['last_name']) ?></td>
                <td>
                  <a href="?delete=<?= $usuario['id'] ?>" class="btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                  <a href="?edit=<?= $usuario['id'] ?>" class="btn-secondary">Edit</a>
                </td>
              </tr>

              <?php if (isset($_GET['edit']) && intval($_GET['edit']) === intval($usuario['id'])): ?>
                <tr>
                  <td colspan="3">
                    <article class="card">
                      <h3>Editing: <?= htmlspecialchars($usuario['first_name']) ?></h3>
                      <form method="POST">
                        <input type="hidden" name="edit_user" value="1">
                        <input type="hidden" name="user_id" value="<?= $usuario['id'] ?>">

                        <label>First Name*:<input type="text" name="first_name" value="<?= htmlspecialchars($usuario['first_name']) ?>" required></label>
                        <label>Last Name*:<input type="text" name="last_name" value="<?= htmlspecialchars($usuario['last_name']) ?>" required></label>
                        <label>Email*:<input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required></label>
                        <label>ABN:<input type="text" name="abn" value="<?= htmlspecialchars($usuario['abn']) ?>"></label>
                        <label>Referral Code:<input type="text" name="referral_code" value="<?= htmlspecialchars($usuario['referral_code']) ?>"></label>
                        <label>User Type*:
                          <select name="user_type" required>
                            <option value="">Select...</option>
                            <option value="consumer" <?= $usuario['user_type'] === 'consumer' ? 'selected' : '' ?>>Consumer</option>
                            <option value="cleaner" <?= $usuario['user_type'] === 'cleaner' ? 'selected' : '' ?>>Cleaner</option>
                            <option value="referral member" <?= $usuario['user_type'] === 'referral member' ? 'selected' : '' ?>>Referral Member</option>
                            <option value="admin member" <?= $usuario['user_type'] === 'admin member' ? 'selected' : '' ?>>Admin</option>
                            <option value="super admin" <?= $usuario['user_type'] === 'super admin' ? 'selected' : '' ?>>Super Admin</option>
                          </select>
                        </label>

                        <button type="submit" class="btn-primary">Save Changes</button>
                      </form>
                    </article>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No users found.</p>
      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
