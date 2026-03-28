<?php
// ============================================
// Auth & Session Helpers
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn() {
    // Check session first
    if (isset($_SESSION['user_id'])) return true;
    // Fall back to remember-me cookie
    if (isset($_COOKIE['remember_token'])) {
        $db = getDB();
        // Simple token: base64(id:email)
        $decoded = base64_decode($_COOKIE['remember_token']);
        if ($decoded && substr_count($decoded, ':') === 1) {
            [$id, $email] = explode(':', $decoded, 2);
            $stmt = $db->prepare("SELECT * FROM users WHERE id=? AND email=? AND status='active'");
            $stmt->execute([$id, $email]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email']= $user['email'];
                return true;
            }
        }
        // Invalid cookie — clear it
        setcookie('remember_token', '', time() - 3600, '/');
    }
    return false;
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        header('Location: /login.php?error=unauthorized');
        exit;
    }
}

function currentUser() {
    return [
        'id'    => $_SESSION['user_id']   ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'role'  => $_SESSION['user_role'] ?? '',
        'email' => $_SESSION['user_email']?? '',
    ];
}

function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: /login.php?msg=logout');
    exit;
}