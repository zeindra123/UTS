<?php
/**
 * S-14: Laporan Siap Cetak A4 (Berita Acara Penanganan BAP-PM Resmi)
 * PRD FR-31, UI-LOGO-01/02, UI-FOOTER-01
 */

$pageTitle = 'Cetak Berita Acara Penanganan (BAP-PM) – Smart Complaint';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$ticket = $_GET['ticket'] ?? 'SC-2026-11001';

$stmt = $pdo->prepare("SELECT r.*, c.name as category_name, d.name as dept_name, d.code as dept_code, u.name as citizen_name, u.phone as citizen_phone 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    LEFT JOIN users u ON r.citizen_id = u.id 
    WHERE r.ticket_number = ? LIMIT 1");
$stmt->execute([$ticket]);
$report = $stmt->fetch();

if (!$report) {
    die("Laporan tiket tidak ditemukan.");
}

$histStmt = $pdo->prepare("SELECT * FROM report_history WHERE report_id = ? ORDER BY id ASC");
$histStmt->execute([$report['id']]);
$history = $histStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Top Action Bar (Sembunyi saat cetak) -->
  <div class="no-print flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
      <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Dokumen Berita Acara Penanganan (BAP-PM)</h2>
      <p class="text-xs text-slate-500">Format resmi siap cetak lembar kerja A4 untuk pengesahan dinas</p>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="window.print()" class="bg-[#005cbd] hover:bg-[#004791] text-white px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center gap-1.5">
        <span class="material-symbols-outlined text-base">print</span>
        <span>Cetak Dokumen / Simpan PDF (A4)</span>
      </button>
      <a href="lacak.php?ticket=<?= urlencode($ticket) ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300">
        Kembali ke Pelacakan
      </a>
    </div>
  </div>

  <!-- KERTAS DOKUMEN A4 YANG DICETAK (PRINT AREA) -->
  <div id="print-area" class="max-w-[800px] mx-auto bg-white p-8 sm:p-12 rounded-xl border border-slate-300 shadow-lg text-slate-900 font-sans space-y-6">
    
    <!-- KOP DUA LOGO RESMI (PRD UI-LOGO-01 s/d 06) -->
    <div class="flex items-center justify-between pb-4 border-b-2 border-slate-900 gap-4">
      <!-- Logo 1: Pemkot Bandar Lampung -->
      <div class="w-16 h-16 shrink-0 flex items-center justify-center">
        <img src="logo/logo_pemda.png" alt="Logo Pemkot Bandar Lampung" class="h-16 w-auto object-contain" onerror="this.src='logo/LOGO%20PEMDA%20BANDAR%20LAMPUNG.png'">
      </div>

      <!-- Teks Kop Surat -->
      <div class="text-center flex-1 space-y-0.5">
        <h3 class="font-heading font-bold text-sm tracking-widest uppercase text-slate-800">PEMERINTAH KOTA BANDAR LAMPUNG</h3>
        <h2 class="font-heading font-extrabold text-base text-slate-900 uppercase"><?= sanitize($report['dept_name'] ?? 'DINAS BINA MARGA DAN BINA KONSTRUKSI') ?></h2>
        <p class="text-[10px] text-slate-600">Sistem Pengaduan Masyarakat (Smart Complaint) &bull; Riset Kolaborasi Universitas Teknokrat Indonesia</p>
        <p class="text-[9px] text-slate-500">Jl. Dokter Susilo No. 2, Teluk Betung, Kota Bandar Lampung 35214</p>
      </div>

      <!-- Logo 2: Teknokrat -->
      <div class="w-16 h-16 shrink-0 flex items-center justify-center">
        <img src="logo/logo_teknokrat.png" alt="Logo Universitas Teknokrat Indonesia" class="h-16 w-auto object-contain" onerror="this.src='logo/LOGO%20TEKNOKRAT.png'">
      </div>
    </div>

    <!-- Judul Berita Acara -->
    <div class="text-center space-y-1">
      <h1 class="font-heading font-extrabold text-base tracking-wide uppercase underline">
        BERITA ACARA PENANGANAN PENGADUAN MASYARAKAT (BAP-PM)
      </h1>
      <p class="font-mono text-xs font-semibold text-slate-700">Nomor Registrasi: BAP/2026/XI/<?= sanitize($report['ticket_number']) ?></p>
    </div>

    <!-- I. Informasi Laporan -->
    <div class="space-y-1 text-xs">
      <p class="font-bold text-slate-800">I. DATA PENGADUAN DAN LOKASI</p>
      <table class="w-full border border-slate-300 text-xs">
        <tr class="border-b border-slate-300">
          <td class="p-2 bg-slate-100 font-semibold w-40">Nomor Tiket Aduan</td>
          <td class="p-2 font-mono font-bold text-blue-900"><?= sanitize($report['ticket_number']) ?></td>
          <td class="p-2 bg-slate-100 font-semibold w-32">Waktu Lapor</td>
          <td class="p-2"><?= formatDateIndo($report['created_at']) ?></td>
        </tr>
        <tr class="border-b border-slate-300">
          <td class="p-2 bg-slate-100 font-semibold">Nama Pelapor</td>
          <td class="p-2"><?= sanitize($report['citizen_name']) ?> (<?= sanitize($report['citizen_phone']) ?>)</td>
          <td class="p-2 bg-slate-100 font-semibold">Kategori Masalah</td>
          <td class="p-2"><?= sanitize($report['category_name']) ?></td>
        </tr>
        <tr class="border-b border-slate-300">
          <td class="p-2 bg-slate-100 font-semibold">Lokasi Kejadian</td>
          <td class="p-2" colspan="3">Kecamatan <?= sanitize($report['district']) ?> &bull; <?= sanitize($report['address']) ?> (Koordinat: <?= $report['latitude'] ?>, <?= $report['longitude'] ?>)</td>
        </tr>
        <tr>
          <td class="p-2 bg-slate-100 font-semibold">Uraian Masalah</td>
          <td class="p-2" colspan="3"><?= nl2br(sanitize($report['description'])) ?></td>
        </tr>
      </table>
    </div>

    <!-- II. Tindakan Penanganan -->
    <div class="space-y-1 text-xs">
      <p class="font-bold text-slate-800">II. TINDAKAN PENANGANAN TEKNIS LAPANGAN</p>
      <table class="w-full border border-slate-300 text-xs">
        <tr class="border-b border-slate-300">
          <td class="p-2 bg-slate-100 font-semibold w-40">Dinas / Regu Pelaksana</td>
          <td class="p-2"><?= sanitize($report['dept_name'] ?? 'Regu Reaksi Cepat 02') ?></td>
          <td class="p-2 bg-slate-100 font-semibold w-32">Status Terkini</td>
          <td class="p-2 font-bold uppercase"><?= sanitize($report['status']) ?></td>
        </tr>
        <tr class="border-b border-slate-300">
          <td class="p-2 bg-slate-100 font-semibold">Material / Tindakan</td>
          <td class="p-2" colspan="3"><?= sanitize($report['materials_used'] ?: 'Pembersihan dan perbaikan teknis standar dinas') ?></td>
        </tr>
        <tr>
          <td class="p-2 bg-slate-100 font-semibold">Catatan BAP Petugas</td>
          <td class="p-2 italic" colspan="3"><?= sanitize($report['action_notes'] ?: 'Pekerjaan fisik telah diselesaikan sesuai target teknis.') ?></td>
        </tr>
      </table>
    </div>

    <!-- III. Dokumentasi Visual -->
    <div class="space-y-1 text-xs">
      <p class="font-bold text-slate-800">III. DOKUMENTASI VISUAL LAPANGAN</p>
      <div class="grid grid-cols-2 gap-4 border border-slate-300 p-3 rounded text-center">
        <div class="space-y-1">
          <div class="h-40 bg-slate-100 border border-slate-300 rounded overflow-hidden">
            <img src="<?= sanitize($report['photo_before']) ?>" alt="Sebelum" class="w-full h-full object-cover">
          </div>
          <p class="text-[10px] font-bold text-slate-700">FOTO AWAL (Laporan Warga)</p>
        </div>
        <div class="space-y-1">
          <div class="h-40 bg-slate-100 border border-slate-300 rounded overflow-hidden flex items-center justify-center">
            <?php if ($report['photo_after']): ?>
              <img src="<?= sanitize($report['photo_after']) ?>" alt="Sesudah" class="w-full h-full object-cover">
            <?php else: ?>
              <span class="text-slate-400 text-xs">Menunggu foto dokumentasi akhir</span>
            <?php endif; ?>
          </div>
          <p class="text-[10px] font-bold text-emerald-800">FOTO AKHIR (Hasil Perbaikan)</p>
        </div>
      </div>
    </div>

    <!-- IV. Pengesahan Tiga Pihak -->
    <div class="pt-4 space-y-1 text-xs">
      <p class="font-bold text-slate-800">IV. PENGESAHAN DAN VERIFIKASI TIGA PIHAK</p>
      <div class="grid grid-cols-3 gap-2 text-center text-[11px] pt-2">
        <div class="space-y-12">
          <p class="text-slate-600">Pelapor (Warga):</p>
          <div class="pt-4 border-t border-slate-400 font-semibold text-slate-900">
            ( <?= sanitize($report['citizen_name']) ?> )
          </div>
        </div>
        <div class="space-y-12">
          <p class="text-slate-600">Petugas Lapangan:</p>
          <div class="pt-4 border-t border-slate-400 font-semibold text-slate-900">
            ( Hendra Wijaya )
          </div>
        </div>
        <div class="space-y-12">
          <p class="text-slate-600">Penanggung Jawab Dinas:</p>
          <div class="pt-4 border-t border-slate-400 font-semibold text-slate-900">
            ( Ir. Rian Ardiansyah, M.T. )
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Wajib pada Cetakan A4 (PRD UI-FOOTER-01) -->
    <div class="pt-6 border-t border-slate-200 text-center text-[10px] text-slate-500">
      <p class="font-medium">Dokumen Simulasi Prototipe Akademik &bull; Sistem Pengaduan Masyarakat Kota Bandar Lampung</p>
      <p class="font-bold text-slate-700 mt-0.5 select-all">
        Copyright©2026 by Zeindra Adi Verqiawan dan Dr. Erliyan Redy Susanto, M.Kom
      </p>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

