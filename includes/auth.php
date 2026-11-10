<?php
/**
 * Smart Complaint - Auth & Security Session Helper
 * Memenuhi Rekomendasi Audit Keamanan: SEC-01 (CSRF), SEC-02 (Rate Limit), SEC-04 (IDOR)
 */

$sessDir = __DIR__ . '/../data/sessions';
if (!is_dir($sessDir)) {
    @mkdir($sessDir, 0777, true);
}
if (is_dir($sessDir)) {
    @session_save_path($sessDir);
}

// SEC-01 & SEC-08: Pengaturan Cookie Sesi Aman
if (session_status() === PHP_SESSION_NONE) {
    // Cookie HttpOnly & SameSite Lax untuk mencegah XSS session stealing & basic CSRF
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', '1');
    @session_start();
}

require_once __DIR__ . '/../config/database.php';

// ========================================================
// 1. CSRF PROTECTION (SEC-01)
// ========================================================

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
            // Validasi gagal - tolak request
            http_response_code(403);
            die('
                <div style="font-family:sans-serif; max-width:500px; margin:50px auto; padding:20px; border:1px solid #f87171; border-radius:12px; background:#fef2f2; color:#991b1b;">
                    <h3 style="margin-top:0;">Permintaan Tidak Valid (CSRF Token Mismatch)</h3>
                    <p style="font-size:14px; line-height:1.5;">Token keamanan formulir kedaluwarsa atau tidak valid. Silakan kembali dan muat ulang halaman.</p>
                    <a href="javascript:history.back()" style="display:inline-block; padding:8px 16px; background:#dc2626; color:#fff; text-decoration:none; border-radius:6px; font-size:13px;">Kembali</a>
                </div>
            ');
        }
    }
}

// ========================================================
// 2. RATE LIMITING LOGIN & BRUTE FORCE DEFENSE (SEC-02)
// ========================================================

function checkLoginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();
    $maxAttempts = 5;
    $lockoutTime = 300; // 5 menit

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    // Bersihkan attempt yang sudah lebih dari 5 menit
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($t) use ($now, $lockoutTime) {
        return ($now - $t) < $lockoutTime;
    });

    if (count($_SESSION['login_attempts']) >= $maxAttempts) {
        $oldest = reset($_SESSION['login_attempts']);
        $remaining = $lockoutTime - ($now - $oldest);
        return [
            'allowed' => false,
            'remaining_seconds' => max(1, $remaining)
        ];
    }

    return ['allowed' => true];
}

function recordFailedLogin() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    $_SESSION['login_attempts'][] = time();
}

function clearLoginAttempts() {
    $_SESSION['login_attempts'] = [];
}

// ========================================================
// 3. AUTENTIKASI & ROTASI SESI (SEC-04 & SEC-08)
// ========================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => (int)$_SESSION['user_id'],
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
    // SEC-08: Rotasi Session ID untuk mencegah Session Fixation
    @session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';

    // Hapus hitungan gagal login
    clearLoginAttempts();
    
    // Perbarui token CSRF baru setelah login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    @session_destroy();
}
