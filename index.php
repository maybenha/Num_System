<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? '/admin/dashboard.php' : '/user/store.php'));
} else {
    header('Location: /login.php');
}
exit;