<?php
/**
 * S-07: Dashboard Warga (PRD FR-11 & FR-25)
 */

$pageTitle = 'Dashboard Warga – Smart Complaint Kota Bandar Lampung';
require_once __DIR__ . '/includes/auth.php';
requireLogin('login.php');

$user = currentUser();
$pdo = getDB();

// Ambil laporan milik user ini
$stmt = $pdo->prepare("SELECT r.*, c.name as category_name, d.name as dept_name 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    WHERE r.citizen_id = ? 
    ORDER BY r.id DESC");
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

// Hitung metrik
$total = count($reports);
$inProgress = 0;
$resolved = 0;
$waitingConfirmation = null;

foreach ($reports as $r) {
    if ($r['status'] === 'resolved') {
        $resolved++;
    } elseif ($r['status'] === 'awaiting_citizen_confirmation') {
        $waitingConfirmation = $r;
        $inProgress++;
    } elseif (in_array($r['status'], ['submitted', 'pending_verification', 'verified', 'assigned', 'in_progress'])) {
        $inProgress++;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Greeting Header -->
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Dashboard Laporan Saya</h2>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Warga Terdaftar</span>
      </div>
      <p class="text-xs text-slate-500">Selamat datang kembali, <strong><?= sanitize($user['name']) ?></strong> (<?= sanitize($user['email']) ?>)</p>
    </div>
    <a href="lapor.php" class="bg-[#005cbd] hover:bg-[#004791] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow transition flex items-center gap-1.5">
      <span class="material-symbols-outlined text-sm">add_circle</span>
      <span>Buat Pengaduan Baru</span>
    </a>
  </div>

  <!-- 3 Kartu Ringkasan Tiket Warga -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500 font-medium">Total Aduan Saya</p>
        <p class="text-2xl font-bold font-heading text-slate-900 mt-0.5"><?= $total ?></p>
      </div>
      <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">folder_open</span>
      </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500 font-medium">Sedang Ditangani</p>
        <p class="text-2xl font-bold font-heading text-amber-600 mt-0.5"><?= $inProgress ?></p>
      </div>
      <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">engineering</span>
      </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500 font-medium">Telah Selesai</p>
        <p class="text-2xl font-bold font-heading text-emerald-600 mt-0.5"><?= $resolved ?></p>
      </div>
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">verified</span>
      </div>
    </div>
  </div>

  <!-- Widget Notifikasi Respon Warga (PRD FR-25) -->
  <?php if ($waitingConfirmation): ?>
  <div class="bg-amber-50 border border-amber-300 p-4 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-xl">notifications_active</span>
      </div>
      <div>
        <h4 class="font-heading font-bold text-sm text-amber-950">Aduan Menunggu Konfirmasi Anda!</h4>
        <p class="text-xs text-amber-900">Petugas telah menyelesaikan perbaikan pada tiket <strong><?= sanitize($waitingConfirmation['ticket_number']) ?></strong> (<?= sanitize($waitingConfirmation['title']) ?>). Mohon konfirmasi kepuasan Anda.</p>
      </div>
    </div>
    <a href="detail.php?ticket=<?= urlencode($waitingConfirmation['ticket_number']) ?>" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center gap-1">
      <span>Buka & Nilai Hasil</span>
      <span class="material-symbols-outlined text-sm">arrow_forward</span>
    </a>
  </div>
  <?php endif; ?>

  <!-- Tabel Riwayat Aduan Saya -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h3 class="font-heading font-bold text-base text-[#00163a]">Riwayat Pengaduan Saya</h3>
      <span class="text-xs text-slate-500"><?= $total ?> Laporan Tercatat</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3">Nomor Tiket</th>
            <th class="py-2.5 px-3">Kategori & Judul</th>
            <th class="py-2.5 px-3">Kecamatan</th>
            <th class="py-2.5 px-3">Tanggal</th>
            <th class="py-2.5 px-3">Status</th>
            <th class="py-2.5 px-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($reports)): ?>
          <tr>
            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada pengaduan yang dibuat. Klik tombol "Buat Pengaduan Baru" di atas.</td>
          </tr>
          <?php else: ?>
            <?php foreach ($reports as $r): ?>
            <tr class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-3 font-mono font-bold text-blue-900"><?= sanitize($r['ticket_number']) ?></td>
              <td class="py-3 px-3">
                <span class="font-semibold text-slate-800 block"><?= sanitize($r['title']) ?></span>
                <span class="text-[10px] text-slate-500">Kategori: <?= sanitize($r['category_name']) ?></span>
              </td>
              <td class="py-3 px-3 text-slate-600"><?= sanitize($r['district']) ?></td>
              <td class="py-3 px-3 text-slate-500"><?= formatDateIndo($r['created_at']) ?></td>
              <td class="py-3 px-3"><?= getStatusBadge($r['status']) ?></td>
              <td class="py-3 px-3 text-right space-x-1">
                <a href="detail.php?ticket=<?= urlencode($r['ticket_number']) ?>" class="px-2.5 py-1 rounded bg-blue-600 text-white font-medium hover:bg-blue-700">Detail</a>
                <a href="cetak_bap.php?ticket=<?= urlencode($r['ticket_number']) ?>" target="_blank" class="px-2 py-1 rounded bg-slate-200 text-slate-700 hover:bg-slate-300">Cetak A4</a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

