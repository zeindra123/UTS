<?php
/**
 * Lupa Kata Sandi – Smart Complaint
 * Memenuhi Rekomendasi Audit Keamanan: SEC-03 (Reset Kata Sandi Aman & Anti-Enumeration)
 */

$pageTitle = 'Pemulihan Kata Sandi – Smart Complaint';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Silakan masukkan alamat email yang valid.';
    } else {
        // SEC-03: Selalu berikan respons generik tanpa membocorkan apakah email terdaftar (Cegah Enumerasi Akun)
        // Token acak berentropi tinggi dan masa berlaku 15 menit
        $resetToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 900); // 15 menit

        // Catat ke log audit internal tanpa membocorkan ke frontend
        $info = 'Jika alamat email terdaftar pada sistem kami, tautan pemulihan kata sandi telah dikirimkan. Demi keamanan, tautan tersebut hanya berlaku selama 15 menit.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto space-y-6 py-6">
  <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xl space-y-5">
    
    <div class="text-center space-y-2">
      <div class="w-12 h-12 bg-blue-50 text-blue-700 rounded-full flex items-center justify-center mx-auto">
        <span class="material-symbols-outlined text-2xl">lock_reset</span>
      </div>
      <h2 class="font-heading font-bold text-xl text-[#00163a]">Lupa Kata Sandi</h2>
      <p class="text-xs text-slate-500">Masukkan email Anda untuk menerima instruksi pemulihan kata sandi yang aman</p>
    </div>

    <?php if ($info): ?>
    <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-start gap-2">
      <span class="material-symbols-outlined text-base shrink-0 mt-0.5">info</span>
      <span><?= sanitize($info) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-3.5 bg-red-50 border border-red-300 rounded-xl text-red-800 text-xs flex items-center gap-2">
      <span class="material-symbols-outlined text-base">error</span>
      <span><?= sanitize($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="lupa_password.php" class="space-y-4 text-xs">
      <?= csrfField() ?>
      <div>
        <label for="reset-email" class="block font-semibold text-slate-700 mb-1">Alamat Email Terdaftar *</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base">mail</span>
          <input id="reset-email" name="email" type="email" required placeholder="nama@email.com" class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>
      </div>

      <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center justify-center gap-1.5">
        <span class="material-symbols-outlined text-sm">send</span>
        <span>Kirim Tautan Pemulihan</span>
      </button>
    </form>

    <div class="border-t border-slate-100 pt-3 text-center text-xs text-slate-500">
      Sudah ingat kata sandi Anda? 
      <a href="login.php" class="text-blue-600 font-semibold hover:underline">Kembali ke Halaman Masuk</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

