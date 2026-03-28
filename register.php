<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? '/admin/dashboard.php' : '/user/store.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $role     = $_POST['role']          ?? 'user';

    if (!in_array($role, ['admin', 'user'])) $role = 'user';

    if (!$fullname || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $chk  = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?,?,?,?,?)");
            $stmt->execute([$fullname, $email, $phone, $hash, $role]);
            header('Location: /login.php?msg=registered');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — NUM-Starbuck</title>
<link rel="stylesheet" href="/includes/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box" style="max-width:460px;">
    <div class="auth-logo"><img style="width: 100px; margin-left: 15px;" src="https://numer.digital/public/template/university/images/logo/num.png" alt="NUM-Starbuck"></div>
    <div class="auth-logo">NUM-Starbuck</div>
    <p class="auth-subtitle">Create your account</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Full Name <span style="color:var(--accent2)">*</span></label>
        <input type="text" name="fullname" class="form-control" placeholder="John Doe" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email <span style="color:var(--accent2)">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" placeholder="012-345-6789" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password <span style="color:var(--accent2)">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Min 6 chars" required>
        </div>
        <div class="form-group">
          <label>Confirm Password <span style="color:var(--accent2)">*</span></label>
          <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>
      <div class="form-group">
        <label>Register As</label>
        <select name="role" class="form-control">
          <option value="user" <?= (($_POST['role']??'user')==='user')?'selected':'' ?>>Customer</option>
          <option value="admin" <?= (($_POST['role']??'')==='admin')?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">Create Account</button>
    </form>

    <hr class="divider">
    <p style="text-align:center;font-size:0.85rem;color:var(--muted);">
      Already have an account? <a href="/login.php" style="color:var(--accent);font-weight:600;text-decoration:none;">Sign In</a>
    </p>
  </div>
</div>
</body>
</html>