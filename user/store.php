<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('user');

$db   = getDB();
$user = currentUser();

// ---- Cart stored in session as array ----
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$msg   = '';
$error = '';

// ---- Handle cart actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add to cart
    if ($action === 'add_to_cart') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        // Load product from DB
        $stmt = $db->prepare("SELECT * FROM products WHERE id=? AND qty > 0");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        if (!$product) {
            $error = 'Product not available.';
        } else {
            $cartQty = isset($_SESSION['cart'][$pid]) ? $_SESSION['cart'][$pid]['qty'] : 0;
            $newQty  = $cartQty + $qty;
            if ($newQty > $product['qty']) {
                $error = 'Not enough stock for "' . htmlspecialchars($product['name']) . '".';
            } else {
                $_SESSION['cart'][$pid] = [
                    'id'        => $product['id'],
                    'name'      => $product['name'],
                    'price'     => $product['price'],
                    'image_url' => $product['image_url'],
                    'qty'       => $newQty,
                ];
                $msg = 'Added to cart!';
            }
        }
    }

    // Update qty
    if ($action === 'update_qty') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 0);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$pid]);
        } elseif (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] = $qty;
        }
    }

    // Remove from cart
    if ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$pid]);
    }

    // Clear cart
    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
    }

    // Checkout — save to DB
    if ($action === 'checkout') {
        if (empty($_SESSION['cart'])) {
            $error = 'Your cart is empty.';
        } else {
            // Calculate total & verify stock
            $total = 0;
            $valid = true;
            foreach ($_SESSION['cart'] as $pid => $item) {
                $stmt = $db->prepare("SELECT qty FROM products WHERE id=?");
                $stmt->execute([$pid]);
                $row = $stmt->fetch();
                if (!$row || $row['qty'] < $item['qty']) {
                    $error = 'Stock issue with "' . htmlspecialchars($item['name']) . '". Please update your cart.';
                    $valid = false;
                    break;
                }
                $total += $item['price'] * $item['qty'];
            }

            if ($valid) {
                $db->beginTransaction();
                try {
                    // Insert order
                    $stmt = $db->prepare("INSERT INTO orders (user_id, total, status) VALUES (?,?,?)");
                    $stmt->execute([$user['id'], $total, 'pending']);
                    $orderId = $db->lastInsertId();

                    // Insert order items & decrement stock
                    $insItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
                    $decrQty = $db->prepare("UPDATE products SET qty = qty - ? WHERE id=?");

                    foreach ($_SESSION['cart'] as $pid => $item) {
                        $insItem->execute([$orderId, $pid, $item['qty'], $item['price']]);
                        $decrQty->execute([$item['qty'], $pid]);
                    }

                    $db->commit();
                    $_SESSION['cart'] = [];
                    $msg = 'Order #' . $orderId . ' placed successfully! 🎉';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Checkout failed. Please try again.';
                }
            }
        }
    }
}

// ---- Load products from DB ----
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM products WHERE qty > 0 AND name LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $db->query("SELECT * FROM products WHERE qty > 0 ORDER BY id DESC");
}
$products = $stmt->fetchAll();

// ---- Cart totals ----
$cartTotal = 0;
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartTotal += $item['price'] * $item['qty'];
    $cartCount += $item['qty'];
}

renderLayout('Browse Store', 'store');
?>

<div class="topbar">
  <div class="page-title">Store <small>Browse and add products to your cart</small></div>
  <form method="get" style="display:flex;gap:8px;align-items:center;">
    <input type="text" name="q" class="form-control" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>" style="width:220px;">
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
    <?php if ($search): ?><a href="/user/store.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<?php if ($error): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($msg):   ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Products grid -->
<?php if ($products): ?>
<div class="product-grid">
  <?php foreach ($products as $p): ?>
  <div class="product-card">
    <?php if ($p['image_url']): ?>
      <img src="<?= htmlspecialchars($p['image_url']) ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
    <?php else: ?>
      <div class="product-img" style="display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--muted);">📦</div>
    <?php endif; ?>
    <div class="product-body">
      <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
      <div class="product-price">$<?= number_format($p['price'], 2) ?></div>
      <div class="product-qty"><?= $p['qty'] ?> in stock</div>
      <div class="product-actions">
        <form method="post" style="display:flex;gap:6px;align-items:center;width:100%;">
          <input type="hidden" name="action" value="add_to_cart">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <input type="number" name="qty" value="1" min="1" max="<?= $p['qty'] ?>" class="form-control" style="width:60px;padding:5px 8px;">
          <button type="submit" class="btn btn-primary btn-sm" style="flex:1;">+ Cart</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="empty-state">
    <div class="icon">🛒</div>
    <p>No products available<?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.</p>
  </div>
<?php endif; ?>

<!-- Cart toggle button -->
<button class="cart-toggle" onclick="toggleCart()">
  <?= svgIcon('cart') ?>
  <?php if ($cartCount > 0): ?>
    <span class="cart-badge"><?= $cartCount ?></span>
  <?php endif; ?>
</button>

<!-- Cart overlay -->
<div class="overlay" id="cartOverlay" onclick="toggleCart()"></div>

<!-- Cart panel -->
<div class="cart-panel" id="cartPanel">
  <div class="cart-header">
    <h3>🛒 Cart <?php if ($cartCount > 0): ?><span style="color:var(--muted);font-size:0.85rem;font-weight:400;">(<?= $cartCount ?> items)</span><?php endif; ?></h3>
    <button onclick="toggleCart()" style="background:none;border:none;color:var(--muted);cursor:pointer;"><?= svgIcon('close') ?></button>
  </div>

  <div class="cart-items">
    <?php if (empty($_SESSION['cart'])): ?>
      <div class="empty-state" style="padding:40px 0;">
        <div class="icon">🛍️</div>
        <p>Your cart is empty.</p>
      </div>
    <?php else: ?>
      <?php foreach ($_SESSION['cart'] as $pid => $item): ?>
      <div class="cart-item">
        <?php if ($item['image_url']): ?>
          <img src="<?= htmlspecialchars($item['image_url']) ?>" class="cart-item-img" alt="">
        <?php else: ?>
          <div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--muted);">📦</div>
        <?php endif; ?>
        <div class="cart-item-info">
          <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="cart-item-price">$<?= number_format($item['price'], 2) ?> each</div>
          <div class="cart-item-qty">
            <!-- Decrease qty -->
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="update_qty">
              <input type="hidden" name="product_id" value="<?= $pid ?>">
              <input type="hidden" name="qty" value="<?= $item['qty'] - 1 ?>">
              <button type="submit" class="qty-btn">−</button>
            </form>
            <span style="font-size:0.85rem;font-weight:600;"><?= $item['qty'] ?></span>
            <!-- Increase qty -->
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="update_qty">
              <input type="hidden" name="product_id" value="<?= $pid ?>">
              <input type="hidden" name="qty" value="<?= $item['qty'] + 1 ?>">
              <button type="submit" class="qty-btn">+</button>
            </form>
            <!-- Remove -->
            <form method="post" style="display:inline;margin-left:4px;">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= $pid ?>">
              <button type="submit" class="qty-btn" style="color:var(--danger);">✕</button>
            </form>
          </div>
        </div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;color:var(--accent);align-self:flex-start;margin-top:2px;">
          $<?= number_format($item['price'] * $item['qty'], 2) ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($_SESSION['cart'])): ?>
  <div class="cart-footer">
    <div class="cart-total">
      <span>Total</span>
      <span>$<?= number_format($cartTotal, 2) ?></span>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="checkout">
      <button type="submit" class="btn btn-secondary btn-full" style="margin-bottom:10px;">
        Proceed to Checkout
      </button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="clear_cart">
      <button type="submit" class="btn btn-ghost btn-full btn-sm">Clear Cart</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleCart() {
    const panel   = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');
    panel.classList.toggle('open');
    overlay.classList.toggle('show');
    document.body.style.overflow = panel.classList.contains('open') ? 'hidden' : '';
}
</script>

<?php endLayout(); ?>