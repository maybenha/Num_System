<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin('admin');

$db    = getDB();
$error = '';
$success = '';

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add product
    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $qty   = (int)($_POST['qty'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $img   = trim($_POST['image_url'] ?? '');

        if (!$name || $qty < 0 || $price <= 0) {
            $error = 'Please fill in all required fields correctly.';
        } else {
            $stmt = $db->prepare("INSERT INTO products (name, qty, price, image_url) VALUES (?,?,?,?)");
            $stmt->execute([$name, $qty, $price, $img]);
            $success = 'Product added successfully.';
        }
    }

    // Edit product
    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $qty   = (int)($_POST['qty'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $img   = trim($_POST['image_url'] ?? '');

        if (!$id || !$name || $price <= 0) {
            $error = 'Invalid product data.';
        } else {
            $stmt = $db->prepare("UPDATE products SET name=?, qty=?, price=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $qty, $price, $img, $id]);
            $success = 'Product updated.';
        }
    }

    // Delete product
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            $success = 'Product deleted.';
        }
    }
}

// ---- Load products ----
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll();

renderLayout('Products', 'products');
?>

<div class="topbar">
  <div class="page-title">Products <small>Manage your store inventory</small></div>
  <button class="btn btn-primary" onclick="openModal('addModal')">
    <?= svgIcon('plus') ?> Add Product
  </button>
</div>

<?php if ($error):   ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <div class="search-bar">
    <form method="get" style="display:flex;gap:10px;align-items:center;">
      <input type="text" name="q" class="form-control" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-ghost btn-sm">Search</button>
      <?php if ($search): ?>
        <a href="/admin/products.php" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Image</th><th>Name</th><th>Qty</th><th>Price</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td style="color:var(--muted);">#<?= $p['id'] ?></td>
          <td>
            <?php if ($p['image_url']): ?>
              <img src="<?= htmlspecialchars($p['image_url']) ?>" class="thumb" alt="">
            <?php else: ?>
              <div class="thumb" style="display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:1.2rem;">📦</div>
            <?php endif; ?>
          </td>
          <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <span class="badge <?= $p['qty'] > 0 ? 'badge-success' : 'badge-danger' ?>">
              <?= $p['qty'] ?> in stock
            </span>
          </td>
          <td style="font-weight:600;color:var(--accent);">$<?= number_format($p['price'], 2) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button class="btn btn-ghost btn-sm"
                onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
                <?= svgIcon('edit') ?> Edit
              </button>
              <form method="post" onsubmit="return confirm('Delete this product?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"><?= svgIcon('trash') ?> Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px;">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-wrap" id="addModal">
  <div class="modal-box">
    <div class="modal-title">➕ Add New Product</div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Wireless Headphones" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="qty" class="form-control" min="0" value="0" required>
        </div>
        <div class="form-group">
          <label>Price ($) *</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
      </div>
      <div class="form-group">
        <label>Image URL</label>
        <input type="url" name="image_url" class="form-control" placeholder="https://…">
      </div>
      <div style="display:flex;gap:10px;margin-top:4px;">
        <button type="submit" class="btn btn-primary">Add Product</button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-wrap" id="editModal">
  <div class="modal-box">
    <div class="modal-title">✏️ Edit Product</div>
    <form method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" name="name" id="editName" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="qty" id="editQty" class="form-control" min="0" required>
        </div>
        <div class="form-group">
          <label>Price ($) *</label>
          <input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0.01" required>
        </div>
      </div>
      <div class="form-group">
        <label>Image URL</label>
        <input type="url" name="image_url" id="editImg" class="form-control">
      </div>
      <div style="display:flex;gap:10px;margin-top:4px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}
function openEdit(p) {
    document.getElementById('editId').value    = p.id;
    document.getElementById('editName').value  = p.name;
    document.getElementById('editQty').value   = p.qty;
    document.getElementById('editPrice').value = p.price;
    document.getElementById('editImg').value   = p.image_url || '';
    openModal('editModal');
}
// Close modal on backdrop click
document.querySelectorAll('.modal-wrap').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});
</script>

<?php endLayout(); ?>