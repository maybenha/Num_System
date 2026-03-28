<?php
require_once __DIR__ . '/includes/auth.php';

// Already logged in → redirect
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    header('Location: ' . ($role === 'admin' ? '/admin/dashboard.php' : '/user/store.php'));
    exit;
}

$error = '';
$msg   = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logout') $msg = 'You have been logged out.';
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') $error = 'Access denied.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'disabled') {
            $error = 'Your account has been disabled. Contact support.';
        } elseif ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['fullname'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            if ($remember) {
                $token = base64_encode($user['id'] . ':' . $user['email']);
                setcookie('remember_token', $token, time() + (30 * 86400), '/', '', false, true);
            }

            header('Location: ' . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/store.php'));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — ShopSys</title>
<link rel="stylesheet" href="/includes/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <div class="auth-logo"><img style="width: 100px; margin-left: 15px;" src="https://numer.digital/public/template/university/images/logo/num.png" alt="NUM-Starbuck"></div>
    <div class="auth-logo">NUM-Starbuck</div>
    <p class="auth-subtitle">Sign in to your account</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
      <div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="remember" id="remember" style="accent-color:var(--accent);width:16px;height:16px;">
        <label for="remember" style="font-size:0.85rem;color:var(--muted);text-transform:none;letter-spacing:0;font-weight:400;margin:0;">Remember me for 30 days</label>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:4px;">Sign In</button>
    </form>

    <hr class="divider">
    <p style="text-align:center;font-size:0.85rem;color:var(--muted);">
      Don't have an account? <a href="/register.php" style="color:var(--accent);font-weight:600;text-decoration:none;">Register</a>
    </p>
  </div>
</div>
</body>
</html>