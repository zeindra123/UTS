<?php
/**
 * Smart Complaint - Auth & Session Helper
 */

$sessDir = __DIR__ . '/../data/sessions';
if (!is_dir($sessDir)) {
    @mkdir($sessDir, 0777, true);
}
if (is_dir($sessDir)) {
    @session_save_path($sessDir);
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Pengguna',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'warga',
        'phone' => $_SESSION['user_phone'] ?? ''
    ];
}

function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireRole($allowedRoles = [], $redirect = '../login.php') {
    requireLogin($redirect);
    $user = currentUser();
    if (!in_array($user['role'], (array)$allowedRoles)) {
        header("Location: $redirect?error=unauthorized");
        exit;
    }
}

function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
