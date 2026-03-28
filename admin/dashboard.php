<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('admin');

$db = getDB();

$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='confirmed'")->fetchColumn();

// Recent orders
$recentOrders = $db->query("
    SELECT o.id, o.total, o.status, o.created_at, u.fullname, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll();

renderLayout('Dashboard', 'dashboard');
?>

<div class="topbar">
  <div class="page-title">Dashboard <small>Overview of your store</small></div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <span class="icon">👥</span>
    <div class="label">Total Customers</div>
    <div class="value"><?= number_format($totalUsers) ?></div>
  </div>
  <div class="stat-card">
    <span class="icon">📦</span>
    <div class="label">Products</div>
    <div class="value"><?= number_format($totalProducts) ?></div>
  </div>
  <div class="stat-card">
    <span class="icon">🧾</span>
    <div class="label">Total Orders</div>
    <div class="value"><?= number_format($totalOrders) ?></div>
  </div>
  <div class="stat-card">
    <span class="icon">💰</span>
    <div class="label">Revenue</div>
    <div class="value" style="font-size:1.5rem;">$<?= number_format($totalRevenue, 2) ?></div>
  </div>
</div>

<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;">Recent Orders</h2>
    <a href="/admin/orders.php" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($o['fullname']) ?></div>
            <div style="font-size:0.75rem;color:var(--muted);"><?= htmlspecialchars($o['email']) ?></div>
          </td>
          <td style="font-weight:600;">$<?= number_format($o['total'], 2) ?></td>
          <td>
            <?php
              $cls = match($o['status']) {
                'confirmed' => 'badge-success',
                'cancelled' => 'badge-danger',
                default     => 'badge-warning',
              };
            ?>
            <span class="badge <?= $cls ?>"><?= $o['status'] ?></span>
          </td>
          <td style="color:var(--muted);font-size:0.82rem;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentOrders): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:40px;">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endLayout(); ?>