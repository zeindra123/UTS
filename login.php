<?php
/**
 * S-02: Masuk ke Smart Complaint (PRD FR-02 & FR-03)
 */

$pageTitle = 'Masuk – Smart Complaint Kota Bandar Lampung';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
$error = '';
$success = '';

// Fitur Quick Demo Login untuk Evaluasi Dosen / Sidang UTS
if (isset($_GET['quick'])) {
    $qRole = $_GET['quick'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
    $stmt->execute([$qRole]);
    $u = $stmt->fetch();
    if ($u) {
        loginUser($u);
        if ($u['role'] === 'admin') header('Location: admin/index.php');
        elseif ($u['role'] === 'petugas') header('Location: petugas/index.php');
        else header('Location: dashboard.php');
        exit;
    }
}

// Proses Form Login Normal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SEC-01: Validasi CSRF Token
    validateCsrf();

    // SEC-02: Cek Rate Limiting (Maksimal 5 percobaan gagal per 5 menit)
    $rateCheck = checkLoginRateLimit();
    if (!$rateCheck['allowed']) {
        $error = 'Terlalu banyak percobaan login gagal. Demi keamanan, akun ditangguhkan sementara selama ' . ceil($rateCheck['remaining_seconds'] / 60) . ' menit.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email/kontak dan kata sandi wajib diisi.';
        } else {
            // SEC-09: Parameterized query aman dari SQL Injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                loginUser($user);
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } elseif ($user['role'] === 'petugas') {
                    header('Location: petugas/index.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                // Catat kegagalan login untuk rate limiting
                recordFailedLogin();
                // SEC-02: Pesan generik tanpa membocorkan apakah email terdaftar
                $error = 'Email atau kata sandi tidak valid. Silakan coba kembali.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto space-y-6 py-4">
  <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xl space-y-5">
    
    <!-- Header Login dengan Dua Logo -->
    <div class="text-center space-y-2">
      <div class="flex justify-center items-center gap-3 mb-2">
        <img src="logo/logo_pemda.png" alt="Logo Pemkot Bandar Lampung" class="h-12 w-auto object-contain drop-shadow" onerror="this.src='logo/LOGO%20PEMDA%20BANDAR%20LAMPUNG.png'">
        <div class="h-8 w-[1px] bg-slate-300"></div>
        <img src="logo/logo_teknokrat.png" alt="Logo Universitas Teknokrat Indonesia" class="h-12 w-auto object-contain drop-shadow" onerror="this.src='logo/LOGO%20TEKNOKRAT.png'">
      </div>
      <h2 class="font-heading font-bold text-xl text-[#00163a]">Masuk ke Smart Complaint</h2>
      <p class="text-xs text-slate-500">Gunakan akun Anda untuk mengelola atau memantau pengaduan</p>
    </div>

    <!-- Alert Error / Success -->
    <?php if ($error): ?>
    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
      <span class="material-symbols-outlined text-sm">error</span>
      <span><?= sanitize($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- 1-Click Quick Demo Login (Sangat Membantu Evaluasi Dosen) -->
    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg space-y-1.5">
      <p class="text-[11px] font-semibold text-blue-900 flex items-center gap-1">
        <span class="material-symbols-outlined text-sm text-blue-700">bolt</span>
        Akses Cepat Pengujian Dosen (1-Klik Masuk):
      </p>
      <div class="grid grid-cols-3 gap-1.5 text-[10px]">
        <a href="login.php?quick=warga" class="bg-blue-600 hover:bg-blue-700 text-white py-1 px-1.5 rounded text-center font-medium block">Warga Demo</a>
        <a href="login.php?quick=petugas" class="bg-amber-600 hover:bg-amber-700 text-white py-1 px-1.5 rounded text-center font-medium block">Petugas Demo</a>
        <a href="login.php?quick=admin" class="bg-emerald-600 hover:bg-emerald-700 text-white py-1 px-1.5 rounded text-center font-medium block">Admin Demo</a>
      </div>
    </div>

    <!-- Form Login -->
    <form method="POST" action="login.php" class="space-y-4 text-xs">
      <?= csrfField() ?>
      <div>
        <label for="login-email" class="block font-semibold text-slate-700 mb-1">Email / Nomor Ponsel</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base">person</span>
          <input id="login-email" name="email" type="text" required value="budi@warga.com" class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="nama@email.com / 0812xxxx">
        </div>
      </div>

      <div>
        <label for="login-password" class="block font-semibold text-slate-700 mb-1">Kata Sandi</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base">lock</span>
          <input id="login-password" name="password" type="password" required value="password123" class="w-full pl-9 pr-9 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
          <button type="button" onclick="const p = document.getElementById('login-password'); p.type = p.type==='password'?'text':'password';" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
            <span class="material-symbols-outlined text-base">visibility</span>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between text-[11px]">
        <label class="flex items-center gap-1.5 text-slate-600 cursor-pointer">
          <input type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          Ingat saya
        </label>
        <a href="lupa_password.php" class="text-blue-600 hover:underline">Lupa kata sandi?</a>
      </div>

      <button type="submit" class="w-full bg-[#0b2a5b] hover:bg-[#00163a] text-white py-2.5 rounded-lg font-semibold text-sm shadow transition flex items-center justify-center gap-2">
        <span>Masuk ke Sistem</span>
        <span class="material-symbols-outlined text-base">arrow_forward</span>
      </button>
    </form>

    <div class="border-t border-slate-100 pt-3 text-center text-xs text-slate-500">
      Belum memiliki akun warga? 
      <a href="register.php" class="text-blue-600 font-semibold hover:underline">Daftar Akun Baru</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

