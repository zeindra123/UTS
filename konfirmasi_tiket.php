<?php
/**
 * S-06: Konfirmasi Laporan Berhasil (PRD FR-10)
 */

$pageTitle = 'Laporan Berhasil Terkirim – Smart Complaint';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$ticket = $_GET['ticket'] ?? '';

$stmt = $pdo->prepare("SELECT r.*, c.name as category_name FROM reports r LEFT JOIN categories c ON r.category_id = c.id WHERE r.ticket_number = ? LIMIT 1");
$stmt->execute([$ticket]);
$report = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto space-y-6 py-6 text-center">
  <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xl space-y-4">
    
    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-sm">
      <span class="material-symbols-outlined text-4xl">check_circle</span>
    </div>

    <div class="space-y-1">
      <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Laporan Berhasil Terkirim!</h2>
      <p class="text-xs text-slate-500">Pengaduan Anda telah dicatat ke dalam basis data Pemerintah Kota Bandar Lampung.</p>
    </div>

    <!-- Nomor Tiket Box Monospace -->
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
      <p class="text-[11px] text-slate-500 font-medium">Nomor Tiket Pengaduan Anda:</p>
      <div class="flex items-center justify-center gap-2">
        <span id="ticketDisplay" class="font-mono font-extrabold text-2xl text-blue-900 tracking-wider">
          <?= sanitize($ticket ?: 'SC-2026-11001') ?>
        </span>
        <button onclick="navigator.clipboard.writeText('<?= sanitize($ticket) ?>'); alert('Nomor tiket berhasil disalin!');" class="p-1.5 bg-white text-slate-700 hover:text-blue-600 rounded-md border border-slate-300 shadow-sm transition" title="Salin Tiket">
          <span class="material-symbols-outlined text-base">content_copy</span>
        </button>
      </div>
      <?= getStatusBadge('submitted') ?>
    </div>

    <?php if ($report): ?>
    <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-200 text-left text-xs space-y-1 text-slate-700">
      <p class="font-semibold text-slate-900"><?= sanitize($report['title']) ?></p>
      <p class="text-slate-500 text-[11px]">Kategori: <?= sanitize($report['category_name']) ?> &bull; Kec. <?= sanitize($report['district']) ?></p>
    </div>
    <?php endif; ?>

    <p class="text-[11px] text-slate-500 leading-relaxed text-left">
      Simpan nomor tiket di atas untuk mengecek perkembangan secara berkala. Status laporan akan diperbarui saat diverifikasi dan ditindaklanjuti oleh petugas.
    </p>

    <div class="space-y-2 pt-2">
      <a href="lacak.php?ticket=<?= urlencode($ticket) ?>" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center justify-center gap-1.5 block">
        <span class="material-symbols-outlined text-base">search</span>
        <span>Lacak Status Tiket Ini Sekarang</span>
      </a>
      <a href="dashboard.php" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 py-2.5 rounded-xl font-semibold text-xs border border-slate-300 transition block">
        Buka Dashboard Warga Saya
      </a>
      <a href="lapor.php" class="w-full text-blue-600 hover:underline text-xs py-1 block">
        + Buat Pengaduan Lainnya
      </a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

