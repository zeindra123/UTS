<?php
/**
 * S-08: Lacak Pengaduan Publik (PRD FR-24)
 */

$pageTitle = 'Lacak Pengaduan – Smart Complaint Kota Bandar Lampung';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$ticketInput = trim($_GET['ticket'] ?? 'SC-2026-11001');

$report = null;
$history = [];
$error = '';

if ($ticketInput) {
    $stmt = $pdo->prepare("SELECT r.*, c.name as category_name, d.name as dept_name 
        FROM reports r 
        LEFT JOIN categories c ON r.category_id = c.id 
        LEFT JOIN departments d ON r.department_id = d.id 
        WHERE r.ticket_number = ? LIMIT 1");
    $stmt->execute([$ticketInput]);
    $report = $stmt->fetch();

    if ($report) {
        $histStmt = $pdo->prepare("SELECT * FROM report_history WHERE report_id = ? ORDER BY id ASC");
        $histStmt->execute([$report['id']]);
        $history = $histStmt->fetchAll();
    } else {
        $error = "Nomor tiket '$ticketInput' tidak ditemukan dalam basis data.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Search Box Header -->
  <div class="bg-gradient-to-r from-[#00163a] to-[#0b2a5b] text-white p-6 rounded-2xl shadow-md space-y-3">
    <h2 class="font-heading font-extrabold text-xl">Lacak Status Pengaduan Publik</h2>
    <p class="text-xs text-blue-200">Masukkan kode tiket aduan untuk melihat riwayat progres penanganan secara transparan</p>
    
    <form method="GET" action="lacak.php" class="flex flex-wrap gap-2 pt-1 max-w-xl">
      <div class="relative flex-1 min-w-[240px]">
        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
        <input type="text" name="ticket" value="<?= sanitize($ticketInput) ?>" placeholder="Contoh: SC-2026-11001" class="w-full pl-10 pr-3 py-2.5 rounded-xl text-slate-900 text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <button type="submit" class="bg-[#005cbd] hover:bg-[#4e92fe] text-white px-5 py-2.5 rounded-xl font-heading font-bold text-xs shadow transition">
        Cari Aduan
      </button>
    </form>
  </div>

  <?php if ($error): ?>
  <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">warning</span>
    <span><?= sanitize($error) ?></span>
  </div>
  <?php endif; ?>

  <?php if ($report): ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Kolom Kiri: Ringkasan Tiket & Foto (1 Kolom) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
      <div class="flex justify-between items-center pb-2 border-b border-slate-100">
        <span class="text-xs text-slate-400">Kode Pengaduan</span>
        <span class="font-mono font-bold text-blue-900 text-sm"><?= sanitize($report['ticket_number']) ?></span>
      </div>

      <div class="space-y-1.5">
        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
          Kategori: <?= sanitize($report['category_name']) ?>
        </span>
        <h3 class="font-heading font-bold text-sm text-[#00163a]"><?= sanitize($report['title']) ?></h3>
        <p class="text-xs text-slate-600 leading-relaxed"><?= nl2br(sanitize($report['description'])) ?></p>
      </div>

      <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[11px] space-y-1">
        <p class="text-slate-500 font-medium">Lokasi Wilayah:</p>
        <p class="font-bold text-slate-800">Kecamatan <?= sanitize($report['district']) ?></p>
        <p class="text-slate-600 text-[10px] font-mono">Koordinat: <?= $report['latitude'] ?>, <?= $report['longitude'] ?></p>
        <p class="text-slate-500 pt-1">Dinas Penangan: <strong><?= sanitize($report['dept_name'] ?? 'Dalam Proses Verifikasi') ?></strong></p>
      </div>

      <!-- Komparasi Visual Foto Lapangan -->
      <div class="space-y-2 pt-2 border-t border-slate-100">
        <p class="font-heading font-semibold text-xs text-slate-800">Dokumentasi Visual Lapangan:</p>
        <div class="grid grid-cols-2 gap-2 text-center text-[10px]">
          <div class="space-y-1">
            <div class="h-28 bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
              <img src="<?= sanitize($report['photo_before']) ?>" alt="Foto Awal" class="w-full h-full object-cover">
            </div>
            <span class="font-semibold text-red-700">Foto Awal Warga</span>
          </div>
          <div class="space-y-1">
            <div class="h-28 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 flex items-center justify-center">
              <?php if ($report['photo_after']): ?>
                <img src="<?= sanitize($report['photo_after']) ?>" alt="Foto Hasil" class="w-full h-full object-cover">
              <?php else: ?>
                <span class="text-slate-400 text-[9px] p-2">Menunggu perbaikan lapangan</span>
              <?php endif; ?>
            </div>
            <span class="font-semibold text-emerald-700">Foto Hasil Perbaikan</span>
          </div>
        </div>
      </div>

      <div class="pt-2">
        <a href="cetak_bap.php?ticket=<?= urlencode($report['ticket_number']) ?>" target="_blank" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 rounded-xl text-xs font-semibold border border-slate-300 flex items-center justify-center gap-1.5 transition block text-center">
          <span class="material-symbols-outlined text-sm">print</span>
          <span>Cetak Dokumen Resmi A4</span>
        </a>
      </div>
    </div>

    <!-- Kolom Kanan: Linimasa Vertikal Riwayat Status (2 Kolom) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 space-y-5">
      <div class="flex items-center justify-between">
        <h3 class="font-heading font-bold text-base text-[#00163a]">Linimasa Perjalanan Status Pengaduan</h3>
        <?= getStatusBadge($report['status']) ?>
      </div>

      <!-- Stepper Vertikal Riwayat Nyata dari Database -->
      <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
        <?php foreach ($history as $h): ?>
        <div class="relative">
          <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white ring-2 ring-emerald-200"></span>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-heading font-bold text-xs text-slate-900"><?= sanitize($h['actor_name']) ?></span>
              <span class="text-[10px] text-slate-400"><?= formatDateIndo($h['created_at']) ?></span>
            </div>
            <p class="text-xs text-slate-600 mt-0.5"><?= sanitize($h['notes']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Jika Menunggu Konfirmasi Warga -->
      <?php if ($report['status'] === 'awaiting_citizen_confirmation'): ?>
      <div class="p-4 bg-amber-50 rounded-xl border border-amber-300 flex items-center justify-between gap-3">
        <div>
          <p class="font-bold text-amber-900 text-xs">Pekerjaan telah dinyatakan selesai oleh petugas lapangan.</p>
          <p class="text-[11px] text-amber-800">Mohon berikan konfirmasi apakah masalah telah terselesaikan secara memuaskan.</p>
        </div>
        <a href="detail.php?ticket=<?= urlencode($report['ticket_number']) ?>" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow shrink-0">
          Beri Konfirmasi
        </a>
      </div>
      <?php endif; ?>

      <!-- Panduan Status PRD 8.1 -->
      <div class="pt-4 border-t border-slate-100">
        <p class="text-[11px] font-semibold text-slate-700 mb-2">Panduan Arti Status Pengaduan:</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[10px]">
          <span class="p-1.5 rounded bg-amber-50 text-amber-800 border border-amber-200">⏳ Menunggu Verifikasi</span>
          <span class="p-1.5 rounded bg-blue-50 text-blue-800 border border-blue-200">⚙️ Diproses Petugas</span>
          <span class="p-1.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">✓ Selesai Terverifikasi</span>
          <span class="p-1.5 rounded bg-orange-50 text-orange-800 border border-orange-200">📝 Perlu Revisi Warga</span>
          <span class="p-1.5 rounded bg-red-50 text-red-800 border border-red-200">✕ Laporan Ditolak</span>
          <span class="p-1.5 rounded bg-slate-100 text-slate-700 border border-slate-200">📍 Di Luar Wilayah Kota</span>
        </div>
      </div>

    </div>

  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

