<?php
/**
 * S-13: Verifikasi Laporan (Admin) (PRD FR-13 s/d FR-18, FR-30)
 */

$basePath = '../';
$pageTitle = 'Verifikasi & Disposisi Laporan – Smart Complaint';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin'], '../login.php?quick=admin');

$user = currentUser();
$pdo = getDB();

$ticket = $_GET['ticket'] ?? 'SC-2026-11003';

// Ambil data laporan
$stmt = $pdo->prepare("SELECT r.*, c.name as category_name, d.name as dept_name, u.name as citizen_name, u.phone as citizen_phone 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    LEFT JOIN users u ON r.citizen_id = u.id 
    WHERE r.ticket_number = ? LIMIT 1");
$stmt->execute([$ticket]);
$report = $stmt->fetch();

$departments = $pdo->query("SELECT * FROM departments WHERE is_active = 1")->fetchAll();
$officers = $pdo->query("SELECT * FROM users WHERE role = 'petugas'")->fetchAll();

$msg = '';
$error = '';

// Handle Keputusan Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $report) {
    $actionType = $_POST['action_type'] ?? '';

    if ($actionType === 'verify') {
        $deptId = (int)$_POST['department_id'];
        $officerId = (int)$_POST['officer_id'];
        $notes = trim($_POST['instruction_notes'] ?? '');

        $upd = $pdo->prepare("UPDATE reports SET status = 'assigned', department_id = ?, assigned_officer_id = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
        $upd->execute([$deptId, $officerId, $report['id']]);

        addReportHistory($pdo, $report['id'], $user['name'] . ' (Admin)', $report['status'], 'assigned', "Laporan diverifikasi valid. Diteruskan ke OPD terkait dan diterbitkan SPK ke petugas. Instruksi: $notes");

        header("Location: verifikasi.php?ticket=" . urlencode($ticket) . "&msg=verified");
        exit;
    } elseif ($actionType === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (empty($reason)) {
            $error = 'Alasan penolakan wajib diisi (PRD FR-14).';
        } else {
            $upd = $pdo->prepare("UPDATE reports SET status = 'rejected', rejection_reason = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
            $upd->execute([$reason, $report['id']]);

            addReportHistory($pdo, $report['id'], $user['name'] . ' (Admin)', $report['status'], 'rejected', "Laporan ditolak. Alasan: $reason");

            header("Location: verifikasi.php?ticket=" . urlencode($ticket) . "&msg=rejected");
            exit;
        }
    } elseif ($actionType === 'revision') {
        $revNotes = trim($_POST['revision_notes'] ?? '');
        $upd = $pdo->prepare("UPDATE reports SET status = 'needs_revision', revision_notes = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
        $upd->execute([$revNotes, $report['id']]);

        addReportHistory($pdo, $report['id'], $user['name'] . ' (Admin)', $report['status'], 'needs_revision', "Meminta revisi/kelengkapan data ke warga pelapor: $revNotes");

        header("Location: verifikasi.php?ticket=" . urlencode($ticket) . "&msg=revision");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Panel Verifikasi & Disposisi Laporan</h2>
        <?= getStatusBadge($report['status'] ?? 'submitted') ?>
      </div>
      <p class="text-xs text-slate-500">Tiket: <strong class="font-mono text-blue-900"><?= sanitize($ticket) ?></strong> &bull; Pelapor: <?= sanitize($report['citizen_name'] ?? 'Warga') ?></p>
    </div>
    <div class="flex items-center gap-2">
      <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-300">
        Kembali ke Pusat Kontrol
      </a>
    </div>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'verified'): ?>
  <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">check_circle</span>
    <span>Laporan berhasil diverifikasi valid dan telah didisposisikan kepada regu lapangan terkait!</span>
  </div>
  <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
  <div class="p-3.5 bg-red-50 border border-red-300 rounded-xl text-red-800 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">cancel</span>
    <span>Laporan telah ditolak dengan alasan penolakan tercatat pada audit trail.</span>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="p-3.5 bg-red-50 border border-red-300 rounded-xl text-red-800 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">error</span>
    <span><?= sanitize($error) ?></span>
  </div>
  <?php endif; ?>

  <?php if ($report): ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Kolom Kiri: Bukti Foto, Lokasi & Cek Duplikasi (2 Kolom) -->
    <div class="lg:col-span-2 space-y-5">
      
      <!-- 1. Bukti Foto & EXIF -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
        <h3 class="font-heading font-bold text-sm text-[#00163a]">1. Bukti Foto & Metadata Geotag</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="h-52 bg-slate-100 rounded-xl overflow-hidden border border-slate-300 shadow-sm">
            <img src="<?= sanitize($report['photo_before']) ?>" alt="Bukti Foto" class="w-full h-full object-cover">
          </div>
          <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs">
            <span class="font-bold text-slate-800 block">Informasi Lokasi & Geotag:</span>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Kecamatan:</span>
              <span class="font-semibold text-slate-800"><?= sanitize($report['district']) ?></span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Koordinat:</span>
              <span class="font-mono text-emerald-700 font-bold"><?= $report['latitude'] ?>, <?= $report['longitude'] ?></span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Waktu Lapor:</span>
              <span class="text-slate-800"><?= formatDateIndo($report['created_at']) ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Pelapor:</span>
              <span class="text-slate-800"><?= sanitize($report['citizen_name']) ?> (<?= sanitize($report['citizen_phone']) ?>)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Deteksi Duplikasi Spasial (PRD FR-30) -->
      <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-start gap-3">
        <span class="material-symbols-outlined text-emerald-600 text-2xl shrink-0">check_circle</span>
        <div class="text-xs text-emerald-950">
          <span class="font-bold block">Pemeriksaan Duplikasi Spasial (Radius 100m):</span>
          <p class="text-emerald-900 mt-0.5">
            Tidak ditemukan aduan serupa dalam radius 100 meter dalam 7 hari terakhir di wilayah Kecamatan <?= sanitize($report['district']) ?>. Laporan ini valid untuk diproses.
          </p>
        </div>
      </div>

      <!-- 3. Uraian Substansi Aduan -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3 text-xs">
        <h3 class="font-heading font-bold text-sm text-[#00163a]">2. Substansi Aduan</h3>
        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
          <span class="font-bold text-slate-800 text-sm"><?= sanitize($report['title']) ?></span>
          <p class="text-slate-600 leading-relaxed"><?= nl2br(sanitize($report['description'])) ?></p>
          <div class="pt-2 text-slate-500 text-[11px]">
            <span>Alamat: <strong><?= sanitize($report['address']) ?></strong></span>
          </div>
        </div>
      </div>

    </div>

    <!-- Kolom Kanan: Panel Keputusan Admin & Form Disposisi (1 Kolom) -->
    <div class="space-y-5">
      
      <!-- Form Disposisi -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
        <h3 class="font-heading font-bold text-base text-[#00163a]">Keputusan Verifikasi & Disposisi</h3>

        <form method="POST" action="verifikasi.php?ticket=<?= urlencode($ticket) ?>" class="space-y-3 text-xs">
          <input type="hidden" name="action_type" value="verify">

          <div>
            <label for="disp-opd" class="block font-semibold text-slate-700 mb-1">Dinas / OPD Penangan *</label>
            <select id="disp-opd" name="department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $d['id'] == $report['department_id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?> (<?= $d['code'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="disp-officer" class="block font-semibold text-slate-700 mb-1">Pilih Petugas / Regu Lapangan *</label>
            <select id="disp-officer" name="officer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
              <?php foreach ($officers as $off): ?>
                <option value="<?= $off['id'] ?>"><?= sanitize($off['name']) ?> (<?= $off['email'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="disp-notes" class="block font-semibold text-slate-700 mb-1">Catatan Instruksi untuk Petugas</label>
            <textarea id="disp-notes" name="instruction_notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">Segera lakukan penanganan di lokasi sesuai SOP teknis dinas.</textarea>
          </div>

          <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-base">verified</span>
            <span>Verifikasi & Terbitkan SPK</span>
          </button>
        </form>

        <!-- Form Tolak Laporan (PRD FR-14) -->
        <form method="POST" action="verifikasi.php?ticket=<?= urlencode($ticket) ?>" class="pt-3 border-t border-slate-100 space-y-2 text-xs">
          <input type="hidden" name="action_type" value="reject">
          <div>
            <label for="rej-reason" class="block font-semibold text-red-800 mb-1">Tolak Aduan (Wajib Isi Alasan):</label>
            <input id="rej-reason" type="text" name="rejection_reason" placeholder="Contoh: Bukan jalan wewenang pemda / jalan nasional" class="w-full px-3 py-1.5 border border-red-300 rounded-lg text-xs">
          </div>
          <button type="submit" onclick="return confirm('Yakin ingin menolak laporan ini?')" class="w-full bg-red-50 hover:bg-red-100 text-red-700 py-2 rounded-xl font-semibold text-xs border border-red-200 transition">
            Tolak Aduan dengan Alasan
          </button>
        </form>

      </div>

    </div>

  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

