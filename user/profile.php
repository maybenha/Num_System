<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('user');

$db      = getDB();
$user    = currentUser();
$error   = '';
$success = '';

// Load full user data from DB
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        if (!$fullname) {
            $error = 'Full name is required.';
        } else {
            $db->prepare("UPDATE users SET fullname=?, phone=? WHERE id=?")->execute([$fullname, $phone, $user['id']]);
            $_SESSION['user_name'] = $fullname;
            $success = 'Profile updated.';
            $profile['fullname'] = $fullname;
            $profile['phone']    = $phone;
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $profile['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user['id']]);
            $success = 'Password changed successfully.';
        }
    }
}

renderLayout('Profile', 'profile');
?>

<div class="topbar">
  <div class="page-title">My Profile <small>Manage your account details</small></div>
</div>

<?php if ($error):   ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <!-- Profile info -->
  <div class="card">
    <div style="font-family: Arial, Helvetica, sans-serif;font-weight:700;font-size:1.05rem;margin-bottom:20px;">Personal Information</div>
    <form method="post">
      <input type="hidden" name="action" value="update_profile">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($profile['fullname']) ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" disabled style="opacity:0.6;cursor:not-allowed;">
        <small style="color:var(--muted);font-size:0.75rem;">Email cannot be changed.</small>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="012-345-6789">
      </div>
      <div class="form-group">
        <label>Account Role</label>
        <input type="text" class="form-control" value="<?= ucfirst($profile['role']) ?>" disabled style="opacity:0.6;">
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <!-- Change password -->
  <div class="card">
    <div style="font-family: Arial, Helvetica, sans-serif;font-weight:700;font-size:1.05rem;margin-bottom:20px;">Change Password</div>
    <form method="post">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Current Password *</label>
        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label>New Password *</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min 6 chars" required>
      </div>
      <div class="form-group">
        <label>Confirm New Password *</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>

</div>

<?php endLayout(); ?>