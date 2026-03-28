<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('admin');

$db      = getDB();
$success = '';
$error   = '';

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id === (int)currentUser()['id']) {
        $error = 'You cannot modify your own account.';
    } elseif ($action === 'disable') {
        $db->prepare("UPDATE users SET status='disabled' WHERE id=?")->execute([$id]);
        $success = 'User account disabled.';
    } elseif ($action === 'enable') {
        $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
        $success = 'User account enabled.';
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $success = 'User deleted.';
    }
}

// ---- Load users ----
$search = trim($_GET['q'] ?? '');
$role   = $_GET['role'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[]  = "(fullname LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($role && in_array($role, ['admin','user'])) {
    $where[] = "role = ?";
    $params[] = $role;
}

$sql  = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

renderLayout('Customers', 'users');
?>

<div class="topbar">
  <div class="page-title">Customers <small>Manage all registered users</small></div>
</div>

<?php if ($error):   ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <div class="search-bar" style="flex-wrap:wrap;gap:10px;">
    <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control" placeholder="Search name, email, phone…" value="<?= htmlspecialchars($search) ?>" style="max-width:260px;">
      <select name="role" class="form-control" style="width:140px;">
        <option value="">All Roles</option>
        <option value="user"  <?= $role==='user'?'selected':'' ?>>Customer</option>
        <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
      <?php if ($search || $role): ?>
        <a href="/admin/users.php" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--muted);">#<?= $u['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar" style="width:30px;height:30px;font-size:0.7rem;"><?= strtoupper(substr($u['fullname'],0,1)) ?></div>
              <span style="font-weight:600;"><?= htmlspecialchars($u['fullname']) ?></span>
            </div>
          </td>
          <td style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($u['email']) ?></td>
          <td style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
          <td><span class="badge <?= $u['role']==='admin'?'badge-accent':'badge-warning' ?>"><?= $u['role'] ?></span></td>
          <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>"><?= $u['status'] ?></span></td>
          <td style="color:var(--muted);font-size:0.82rem;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['id'] !== (int)currentUser()['id']): ?>
            <div style="display:flex;gap:6px;">
              <?php if ($u['status'] === 'active'): ?>
                <form method="post" onsubmit="return confirm('Disable this user?')">
                  <input type="hidden" name="action" value="disable">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-warning btn-sm">Disable</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="action" value="enable">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-success btn-sm">Enable</button>
                </form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Permanently delete this user?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"><?= svgIcon('trash') ?></button>
              </form>
            </div>
            <?php else: ?>
              <span style="color:var(--muted);font-size:0.8rem;">You</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endLayout(); ?>