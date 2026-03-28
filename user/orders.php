<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('user');

$db   = getDB();
$user = currentUser();

// Load this user's orders from DB
$orders = $db->prepare("
    SELECT * FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

// Load all items for this user's orders
$itemsMap = [];
if ($orders) {
    $orderIds = implode(',', array_column($orders, 'id'));
    $items    = $db->query("
        SELECT oi.*, p.name AS product_name, p.image_url
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id IN ($orderIds)
    ")->fetchAll();
    foreach ($items as $item) {
        $itemsMap[$item['order_id']][] = $item;
    }
}

renderLayout('My Orders', 'myorders');
?>

<div class="topbar">
  <div class="page-title">My Orders <small>Your purchase history</small></div>
</div>

<?php if (!$orders): ?>
  <div class="card">
    <div class="empty-state">
      <div class="icon">📦</div>
      <p>You haven't placed any orders yet.</p>
      <a href="/user/store.php" class="btn btn-primary" style="margin-top:16px;">Browse Store</a>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($orders as $order): ?>
  <div class="card" style="margin-bottom:16px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.05rem;">
          Order #<?= $order['id'] ?>
        </div>
        <div style="font-size:0.8rem;color:var(--muted);margin-top:2px;">
          <?= date('F d, Y — H:i', strtotime($order['created_at'])) ?>
        </div>
      </div>
      <div style="text-align:right;">
        <?php
          $cls = match($order['status']) { 'confirmed'=>'badge-success','cancelled'=>'badge-danger',default=>'badge-warning' };
        ?>
        <span class="badge <?= $cls ?>"><?= $order['status'] ?></span>
        <div style="font-family: Arial, Helvetica, sans-serif;font-size:1.5rem;font-weight:800;color:var(--accent);margin-top:6px;">
          $<?= number_format($order['total'], 2) ?>
        </div>
      </div>
    </div>

    <!-- Order items -->
    <div style="display:flex;flex-direction:column;gap:10px;">
      <?php foreach (($itemsMap[$order['id']] ?? []) as $item): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bg3);border-radius:10px;">
        <?php if ($item['image_url']): ?>
          <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width:48px;height:48px;border-radius:8px;object-fit:cover;" alt="">
        <?php else: ?>
          <div style="width:48px;height:48px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">📦</div>
        <?php endif; ?>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($item['product_name']) ?></div>
          <div style="font-size:0.78rem;color:var(--muted);">
            <?= $item['quantity'] ?> × $<?= number_format($item['price'], 2) ?>
          </div>
        </div>
        <div style="font-weight:700;font-size:0.9rem;color:var(--accent);">
          $<?= number_format($item['price'] * $item['quantity'], 2) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php endLayout(); ?>