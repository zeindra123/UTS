<?php
/**
 * S-09: Detail Laporan & Konfirmasi Warga (PRD FR-12, FR-25, FR-33)
 */

$pageTitle = 'Detail & Konfirmasi Pengaduan – Smart Complaint';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$ticket = $_GET['ticket'] ?? 'SC-2026-11001';

$stmt = $pdo->prepare("SELECT r.*, c.name as category_name, d.name as dept_name, u.name as citizen_name 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    LEFT JOIN users u ON r.citizen_id = u.id 
    WHERE r.ticket_number = ? LIMIT 1");
$stmt->execute([$ticket]);
$report = $stmt->fetch();

if (!$report) {
    die("Tiket tidak ditemukan.");
}

// Handle Konfirmasi Warga
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = $_POST['feedback_state'] ?? 'selesai';
    $rating = (int)($_POST['rating'] ?? 5);
    $feedback = trim($_POST['feedback_notes'] ?? '');

    $newStatus = ($state === 'selesai') ? 'resolved' : 'in_progress';
    $notes = ($state === 'selesai') 
        ? "Warga mengonfirmasi pekerjaan selesai (Rating: $rating/5). Catatan: $feedback"
        : "Warga mengajukan sanggahan / masalah belum tuntas. Catatan: $feedback";

    $upd = $pdo->prepare("UPDATE reports SET status = ?, citizen_rating = ?, citizen_feedback = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$newStatus, $rating, $feedback, $report['id']]);

    addReportHistory($pdo, $report['id'], 'Budi Santoso (Warga)', $report['status'], $newStatus, $notes);

    header("Location: detail.php?ticket=" . urlencode($ticket) . "&success=1");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6 py-4">
  <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xl space-y-5">
    
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <span class="text-xs text-slate-400">Pemeriksaan Hasil Penanganan</span>
        <h2 class="font-heading font-bold text-xl text-[#00163a]">Detail & Konfirmasi Penyelesaian</h2>
      </div>
      <span class="font-mono text-xs font-bold text-blue-900 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
        <?= sanitize($report['ticket_number']) ?>
      </span>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-center gap-2">
      <span class="material-symbols-outlined text-base">check_circle</span>
      <span>Terima kasih! Konfirmasi dan penilaian kepuasan Anda telah berhasil dicatat ke dalam database.</span>
    </div>
    <?php endif; ?>

    <!-- Informasi Laporan Pokok -->
    <div class="space-y-2 text-xs">
      <div class="flex items-center justify-between">
        <span class="font-semibold text-slate-500">Kategori: <?= sanitize($report['category_name']) ?></span>
        <?= getStatusBadge($report['status']) ?>
      </div>
      <h3 class="font-bold text-slate-900 text-sm"><?= sanitize($report['title']) ?></h3>
      <p class="text-slate-600 leading-relaxed"><?= nl2br(sanitize($report['description'])) ?></p>
      <p class="text-slate-500 text-[11px]">Lokasi: Kecamatan <?= sanitize($report['district']) ?> &bull; <?= sanitize($report['address']) ?></p>
    </div>

    <!-- Berita Acara Petugas Lapangan -->
    <?php if ($report['action_notes']): ?>
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
      <div class="flex items-center justify-between font-semibold text-slate-700">
        <span>Catatan Tindakan Petugas Lapangan:</span>
        <span class="text-[10px] text-slate-500">Unit: <?= sanitize($report['dept_name']) ?></span>
      </div>
      <p class="text-slate-700 leading-relaxed italic bg-white p-3 rounded-lg border border-slate-200">
        "<?= sanitize($report['action_notes']) ?>"
      </p>
      <?php if ($report['materials_used']): ?>
      <p class="text-[11px] text-slate-500">Material / Tindakan: <strong><?= sanitize($report['materials_used']) ?></strong></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Komparasi Visual Foto -->
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1 text-center">
        <span class="text-[11px] font-semibold text-slate-600">Foto Saat Lapor:</span>
        <div class="h-36 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
          <img src="<?= sanitize($report['photo_before']) ?>" alt="Sebelum" class="w-full h-full object-cover">
        </div>
      </div>
      <div class="space-y-1 text-center">
        <span class="text-[11px] font-semibold text-emerald-700">Foto Hasil Pengerjaan:</span>
        <div class="h-36 bg-slate-100 rounded-xl overflow-hidden border border-emerald-300 shadow-sm ring-2 ring-emerald-100 flex items-center justify-center">
          <?php if ($report['photo_after']): ?>
            <img src="<?= sanitize($report['photo_after']) ?>" alt="Sesudah" class="w-full h-full object-cover">
          <?php else: ?>
            <span class="text-slate-400 text-xs">Belum ada foto hasil</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Formulir Konfirmasi Warga (Jika status menunggu konfirmasi atau belum dinilai) -->
    <?php if ($report['status'] === 'awaiting_citizen_confirmation' || (!$report['citizen_rating'] && $report['status'] !== 'resolved')): ?>
    <form method="POST" action="detail.php?ticket=<?= urlencode($ticket) ?>" class="p-5 bg-blue-50/50 rounded-2xl border border-blue-200 space-y-4">
      <h3 class="font-heading font-bold text-sm text-[#00163a]">Apakah masalah di lokasi telah benar-benar selesai?</h3>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
        <label class="flex items-center gap-2 p-3 bg-white rounded-xl border border-emerald-400 cursor-pointer shadow-sm">
          <input type="radio" name="feedback_state" value="selesai" checked class="text-emerald-600 focus:ring-emerald-500">
          <span class="font-semibold text-emerald-900">Ya, Telah Selesai Memuaskan</span>
        </label>
        <label class="flex items-center gap-2 p-3 bg-white rounded-xl border border-slate-300 hover:border-red-400 cursor-pointer shadow-sm">
          <input type="radio" name="feedback_state" value="belum" class="text-red-600 focus:ring-red-500">
          <span class="font-semibold text-slate-800">Belum Selesai / Ajukan Sanggahan</span>
        </label>
      </div>

      <!-- Rating Bintang 1-5 -->
      <div class="space-y-1.5 text-xs">
        <label class="block font-semibold text-slate-700">Penilaian Kepuasan Pelayanan (Rating Bintang):</label>
        <select name="rating" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white font-semibold text-amber-600">
          <option value="5">⭐⭐⭐⭐⭐ 5 Bintang - Sangat Memuaskan</option>
          <option value="4">⭐⭐⭐⭐ 4 Bintang - Memuaskan</option>
          <option value="3">⭐⭐⭐ 3 Bintang - Cukup</option>
          <option value="2">⭐⭐ 2 Bintang - Kurang</option>
          <option value="1">⭐ 1 Bintang - Sangat Kurang</option>
        </select>
      </div>

      <!-- Ulasan Warga -->
      <div class="text-xs space-y-1">
        <label for="feedback_notes" class="block font-semibold text-slate-700">Komentar / Ulasan Anda:</label>
        <textarea id="feedback_notes" name="feedback_notes" rows="2" placeholder="Sampaikan apresiasi atau catatan tambahan mengenai hasil pekerjaan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">Terima kasih banyak, penambalan aspal rapi dan cepat.</textarea>
      </div>

      <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-2.5 rounded-xl font-bold text-xs shadow transition">
        Kirim Konfirmasi & Tutup Pengaduan
      </button>
    </form>
    <?php else: ?>
    <!-- Tampilan Ulasan Yang Sudah Diberikan -->
    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200 text-xs space-y-1">
      <span class="font-bold text-emerald-900 block">Penilaian Warga Terdaftar:</span>
      <p class="text-amber-500 font-bold"><?= str_repeat('⭐', (int)$report['citizen_rating']) ?> (<?= $report['citizen_rating'] ?> / 5 Bintang)</p>
      <p class="text-emerald-950 italic">"<?= sanitize($report['citizen_feedback']) ?>"</p>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-between pt-2">
      <a href="dashboard.php" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">arrow_back</span> Kembali ke Dashboard
      </a>
      <a href="cetak_bap.php?ticket=<?= urlencode($report['ticket_number']) ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold border border-slate-300 flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">print</span> Cetak BAP A4
      </a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

