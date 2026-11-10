<?php
/**
 * S-03: Registrasi Akun Warga (PRD FR-01 & NFR-08)
 * Tanpa meminta nomor NIK KTP demi menjaga privasi warga
 */

$pageTitle = 'Registrasi Warga – Smart Complaint Kota Bandar Lampung';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SEC-01: Validasi CSRF Token
    validateCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    $passConfirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'Nama, email, dan kata sandi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif ($pass !== $passConfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($pass) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } else {
        // Cek email duplikat
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.';
        } else {
            $passHash = password_hash($pass, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'warga')");
            $insert->execute([$name, $email, $phone, $passHash]);
            
            // Login otomatis
            $userId = $pdo->lastInsertId();
            loginUser([
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'warga'
            ]);
            header('Location: dashboard.php?registered=1');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-lg mx-auto space-y-6 py-4">
  <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xl space-y-5">
    
    <div class="text-center space-y-1">
      <h2 class="font-heading font-bold text-xl text-[#00163a]">Pendaftaran Akun Warga</h2>
      <p class="text-xs text-slate-500">Lengkapi data diri singkat Anda untuk memantau status aduan</p>
      <div class="inline-flex items-center gap-1 text-[11px] text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 mt-1">
        <span class="material-symbols-outlined text-sm">shield</span>
        Privasi Terjamin: Tanpa Meminta Nomor NIK KTP (PRD NFR-08)
      </div>
    </div>

    <?php if ($error): ?>
    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
      <span class="material-symbols-outlined text-sm">error</span>
      <span><?= sanitize($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="space-y-3.5 text-xs">
      <?= csrfField() ?>
      <div>
        <label for="reg-name" class="block font-semibold text-slate-700 mb-1">Nama Lengkap Pelapor *</label>
        <input id="reg-name" name="name" type="text" required placeholder="Contoh: Budi Santoso" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label for="reg-email" class="block font-semibold text-slate-700 mb-1">Alamat Email *</label>
          <input id="reg-email" name="email" type="email" required placeholder="budi@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>
        <div>
          <label for="reg-phone" class="block font-semibold text-slate-700 mb-1">Nomor WhatsApp / Ponsel</label>
          <input id="reg-phone" name="phone" type="tel" placeholder="0812-xxxx-xxxx" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>
      </div>

      <div>
        <label for="reg-pass" class="block font-semibold text-slate-700 mb-1">Kata Sandi Akun *</label>
        <input id="reg-pass" name="password" type="password" required placeholder="Minimal 6 karakter" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
      </div>

      <div>
        <label for="reg-pass-confirm" class="block font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
        <input id="reg-pass-confirm" name="password_confirm" type="password" required placeholder="Ulangi kata sandi" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
      </div>

      <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-[11px] text-slate-600 space-y-1.5">
        <label class="flex items-start gap-2 cursor-pointer">
          <input type="checkbox" required checked class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          <span>Saya menyetujui Kebijakan Privasi Smart Complaint. Identitas nama saya dapat disamarkan pada tampilan feed publik.</span>
        </label>
      </div>

      <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004b99] text-white py-2.5 rounded-lg font-semibold text-sm shadow transition">
        Daftar Akun Sekarang
      </button>
    </form>

    <div class="text-center text-xs text-slate-500">
      Sudah punya akun? 
      <a href="login.php" class="text-blue-600 font-semibold hover:underline">Masuk di sini</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

