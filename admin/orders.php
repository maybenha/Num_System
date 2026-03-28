<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('admin');

$db      = getDB();
$success = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['pending','confirmed','cancelled'])) {
        $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
        $success = 'Order status updated.';
    }
}

// Load orders
$filter = $_GET['status'] ?? '';
if ($filter && in_array($filter, ['pending','confirmed','cancelled'])) {
    $stmt = $db->prepare("
        SELECT o.*, u.fullname, u.email, u.phone
        FROM orders o JOIN users u ON u.id = o.user_id
        WHERE o.status = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$filter]);
} else {
    $stmt = $db->query("
        SELECT o.*, u.fullname, u.email, u.phone
        FROM orders o JOIN users u ON u.id = o.user_id
        ORDER BY o.created_at DESC
    ");
}
$orders = $stmt->fetchAll();

// Load items for each order
$itemsMap = [];
$allItems = $db->query("
    SELECT oi.*, p.name AS product_name, p.image_url
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
")->fetchAll();
foreach ($allItems as $item) {
    $itemsMap[$item['order_id']][] = $item;
}

renderLayout('Orders', 'orders');
?>

<div class="topbar">
  <div class="page-title">Orders <small>All customer orders</small></div>
</div>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
  <?php foreach ([''=>'All', 'pending'=>'Pending', 'confirmed'=>'Confirmed', 'cancelled'=>'Cancelled'] as $val => $label): ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-ghost' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Update</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td style="color:var(--muted);">#<?= $o['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($o['fullname']) ?></div>
            <div style="font-size:0.75rem;color:var(--muted);"><?= htmlspecialchars($o['email']) ?></div>
            <?php if ($o['phone']): ?><div style="font-size:0.75rem;color:var(--muted);"><?= htmlspecialchars($o['phone']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php foreach (($itemsMap[$o['id']] ?? []) as $item): ?>
              <div style="font-size:0.8rem;margin-bottom:2px;">
                <?= htmlspecialchars($item['product_name']) ?>
                <span style="color:var(--muted);">× <?= $item['quantity'] ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (empty($itemsMap[$o['id']])): ?><span style="color:var(--muted);font-size:0.8rem;">—</span><?php endif; ?>
          </td>
          <td style="font-weight:700;color:var(--accent);">$<?= number_format($o['total'], 2) ?></td>
          <td>
            <?php
              $cls = match($o['status']) { 'confirmed'=>'badge-success','cancelled'=>'badge-danger',default=>'badge-warning' };
            ?>
            <span class="badge <?= $cls ?>"><?= $o['status'] ?></span>
          </td>
          <td style="color:var(--muted);font-size:0.82rem;"><?= date('M d, Y H:i', strtotime($o['created_at'])) ?></td>
          <td>
            <form method="post" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="id" value="<?= $o['id'] ?>">
              <select name="status" class="form-control" style="width:130px;padding:5px 10px;font-size:0.82rem;">
                <option value="pending"   <?= $o['status']==='pending'?'selected':'' ?>>Pending</option>
                <option value="confirmed" <?= $o['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
              </select>
              <button type="submit" class="btn btn-ghost btn-sm">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endLayout(); ?>