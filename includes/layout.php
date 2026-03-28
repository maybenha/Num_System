<?php
// ============================================
// Layout Helper — renders sidebar + page shell
// ============================================
function renderLayout($pageTitle, $activeNav, $extraHead = '') {
    $user = currentUser();
    $initial = strtoupper(substr($user['name'], 0, 1));
    $isAdmin = $user['role'] === 'admin';
    $basePath = '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — NUM-Starbuck</title>
<link rel="stylesheet" href="/includes/style.css">
<?= $extraHead ?>
</head>
<body>
<div class="shell">
  <!-- Sidebar -->
   
  <aside class="sidebar">
    <img style="width: 100px; margin-left: 50px;" src="https://numer.digital/public/template/university/images/logo/num.png" alt="">
    <div class="sidebar-logo">NUM-Starbuck</div>
    <nav class="sidebar-nav">

      <?php if ($isAdmin): ?>
        <div class="nav-label">Admin</div>
        <a href="/admin/dashboard.php" class="nav-item <?= $activeNav==='dashboard'?'active':'' ?>">
          <?= svgIcon('grid') ?> Dashboard
        </a>
        <a href="/admin/products.php" class="nav-item <?= $activeNav==='products'?'active':'' ?>">
          <?= svgIcon('box') ?> Products
        </a>
        <a href="/admin/users.php" class="nav-item <?= $activeNav==='users'?'active':'' ?>">
          <?= svgIcon('users') ?> Customers
        </a>
        <a href="/admin/orders.php" class="nav-item <?= $activeNav==='orders'?'active':'' ?>">
          <?= svgIcon('receipt') ?> Orders
        </a>
      <?php else: ?>
        <div class="nav-label">Store</div>
        <a href="/user/store.php" class="nav-item <?= $activeNav==='store'?'active':'' ?>">
          <?= svgIcon('shop') ?> Browse Store
        </a>
        <a href="/user/orders.php" class="nav-item <?= $activeNav==='myorders'?'active':'' ?>">
          <?= svgIcon('receipt') ?> My Orders
        </a>
        <a href="/user/profile.php" class="nav-item <?= $activeNav==='profile'?'active':'' ?>">
          <?= svgIcon('user') ?> Profile
        </a>
      <?php endif; ?>

    </nav>
    <div class="sidebar-footer">
      <div class="user-pill">
        <div class="avatar"><?= $initial ?></div>
        <div class="user-info">
          <strong><?= htmlspecialchars($user['name']) ?></strong>
          <small><?= ucfirst($user['role']) ?></small>
        </div>
        <form method="post" action="/logout.php" style="display:inline">
          <button class="logout-btn" title="Logout">
            <?= svgIcon('logout') ?>
          </button>
        </form>
      </div>
    </div>
  </aside>
  <main class="main-content">
<?php
}

function endLayout() {
?>
  </main>
</div>
</body>
</html>
<?php
}

// ---- SVG icon set ----
function svgIcon($name) {
    $icons = [
        'grid'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'box'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'users'   => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'receipt' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
        'shop'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
        'user'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'logout'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'plus'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'edit'    => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'trash'   => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>',
        'cart'    => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
        'close'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    ];
    return $icons[$name] ?? '';
}