<?php
/**
 * S-10: Dashboard Petugas Lapangan (PRD FR-19 s/d FR-22)
 */

$basePath = '../';
$pageTitle = 'Dashboard Petugas Lapangan – Smart Complaint';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['petugas', 'admin'], '../login.php?quick=petugas');

$user = currentUser();
$pdo = getDB();

// Handle Submit BAP Penanganan Lapangan
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_bap') {
    $reportId = (int)$_POST['report_id'];
    $materials = trim($_POST['materials_used'] ?? '');
    $notes = trim($_POST['action_notes'] ?? '');

    $photoAfter = 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=600&q=80'; // fallback
    if (isset($_FILES['photo_after']) && $_FILES['photo_after']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['photo_after']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'bap_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo_after']['tmp_name'], $uploadDir . $filename)) {
                $photoAfter = 'uploads/' . $filename;
            }
        }
    }

    $upd = $pdo->prepare("UPDATE reports SET status = 'awaiting_citizen_confirmation', photo_after = ?, action_notes = ?, materials_used = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$photoAfter, $notes, $materials, $reportId]);

    addReportHistory($pdo, $reportId, $user['name'] . ' (Petugas)', 'in_progress', 'awaiting_citizen_confirmation', 'Pekerjaan fisik selesai tuntas. Berita Acara dan foto bukti diunggah.');

    $msg = 'Berita Acara Penanganan (BAP) berhasil dikirimkan. Status laporan kini menunggu konfirmasi warga pelapor.';
}

// Ambil daftar tugas lapangan
$tasks = $pdo->query("SELECT r.*, c.name as category_name, d.name as dept_name 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    WHERE r.status IN ('assigned', 'in_progress', 'awaiting_citizen_confirmation')
    ORDER BY r.id DESC")->fetchAll();

// Tiket aktif terpilih untuk BAP
$selectedId = (int)($_GET['task_id'] ?? ($tasks[0]['id'] ?? 0));
$currentTask = null;
foreach ($tasks as $t) {
    if ($t['id'] == $selectedId) {
        $currentTask = $t;
        break;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Header Info Petugas -->
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Dashboard Petugas Lapangan</h2>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Tim Eksekusi Lapangan</span>
      </div>
      <p class="text-xs text-slate-500">Petugas: <strong><?= sanitize($user['name']) ?></strong> &bull; Dinas Bina Marga & Bina Konstruksi (BMBK)</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200">
        <span class="material-symbols-outlined text-sm">navigation</span> GPS Aktif di Bandar Lampung
      </span>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">check_circle</span>
    <span><?= sanitize($msg) ?></span>
  </div>
  <?php endif; ?>

  <!-- 3 Kartu Ringkasan Tugas -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500">Total Tugas Aktif</p>
        <p class="text-2xl font-bold font-heading text-blue-600 mt-0.5"><?= count($tasks) ?></p>
      </div>
      <span class="material-symbols-outlined text-2xl text-blue-500">assignment</span>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500">Dalam Pengerjaan</p>
        <p class="text-2xl font-bold font-heading text-amber-600 mt-0.5">2</p>
      </div>
      <span class="material-symbols-outlined text-2xl text-amber-500">pending_actions</span>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
      <div>
        <p class="text-xs text-slate-500">Selesai Hari Ini</p>
        <p class="text-2xl font-bold font-heading text-emerald-600 mt-0.5">3</p>
      </div>
      <span class="material-symbols-outlined text-2xl text-emerald-500">task_alt</span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Kolom Kiri: Antrean Tugas SPK (1 Kolom) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
      <h3 class="font-heading font-bold text-sm text-[#00163a]">Daftar Tugas SPK Terjadwal</h3>
      
      <div class="space-y-3">
        <?php foreach ($tasks as $t): ?>
        <a href="index.php?task_id=<?= $t['id'] ?>" class="block p-3.5 rounded-xl border <?= $t['id'] == $selectedId ? 'border-2 border-blue-500 bg-blue-50/50' : 'border-slate-200 bg-white hover:border-blue-400' ?> transition space-y-2">
          <div class="flex justify-between items-center text-[10px]">
            <span class="font-mono font-bold text-blue-900"><?= sanitize($t['ticket_number']) ?></span>
            <?= getStatusBadge($t['status']) ?>
          </div>
          <h4 class="font-bold text-xs text-slate-900 line-clamp-1"><?= sanitize($t['title']) ?></h4>
          <p class="text-[11px] text-slate-600">Kec. <?= sanitize($t['district']) ?> &bull; <?= sanitize($t['address']) ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Kolom Kanan: Form Input BAP Lapangan (2 Kolom) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 space-y-4">
      <?php if ($currentTask): ?>
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <div>
            <h3 class="font-heading font-bold text-base text-[#00163a]">Form Berita Acara Penanganan (BAP Lapangan)</h3>
            <p class="text-xs text-slate-500">Tiket: <strong class="font-mono text-blue-900"><?= sanitize($currentTask['ticket_number']) ?></strong> &bull; <?= sanitize($currentTask['title']) ?></p>
          </div>
          <a href="https://maps.google.com/?q=<?= $currentTask['latitude'] ?>,<?= $currentTask['longitude'] ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold flex items-center gap-1 border border-blue-200">
            <span class="material-symbols-outlined text-sm">directions</span>
            <span>Rute GPS</span>
          </a>
        </div>

        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
          <p class="font-semibold text-slate-800">Uraian Masalah Warga:</p>
          <p class="text-slate-600"><?= nl2br(sanitize($currentTask['description'])) ?></p>
          <div class="pt-2 flex items-center gap-3">
            <div class="w-16 h-14 rounded-lg overflow-hidden border border-slate-300">
              <img src="<?= sanitize($currentTask['photo_before']) ?>" alt="Foto Awal" class="w-full h-full object-cover">
            </div>
            <span class="text-[11px] text-slate-500">Foto bukti awal dari warga pelapor</span>
          </div>
        </div>

        <form method="POST" action="index.php?task_id=<?= $currentTask['id'] ?>" enctype="multipart/form-data" class="space-y-4 text-xs">
          <input type="hidden" name="action" value="submit_bap">
          <input type="hidden" name="report_id" value="<?= $currentTask['id'] ?>">

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Unggah Foto Bukti Selesai Pekerjaan *</label>
            <input type="file" name="photo_after" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            <p class="text-[10px] text-slate-400 mt-1">Koordinat GPS terekam otomatis dari posisi perangkat petugas.</p>
          </div>

          <div>
            <label for="bap-material" class="block font-semibold text-slate-700 mb-1">Material / Tindakan Penanganan *</label>
            <input id="bap-material" name="materials_used" type="text" required value="Aspal Dingin (Cold Mix) 5 Sak + Pasir Silika + Mesin Pemadat Stamper" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
          </div>

          <div>
            <label for="bap-notes" class="block font-semibold text-slate-700 mb-1">Catatan Tindakan Teknis Lapangan *</label>
            <textarea id="bap-notes" name="action_notes" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed">Pembersihan puing aspal lama, pemadatan agregat dasar, dan penambalan dengan aspal penetrasi setebal 15 cm. Permukaan sudah rata dan aman dilalui.</textarea>
          </div>

          <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center justify-center gap-1.5">
              <span class="material-symbols-outlined text-base">check_circle</span>
              <span>Laporkan Selesai & Kirim ke Warga</span>
            </button>
            <a href="../cetak_bap.php?ticket=<?= urlencode($currentTask['ticket_number']) ?>" target="_blank" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-xl font-semibold text-xs transition">
              Pratinjau Cetak BAP
            </a>
          </div>
        </form>

      <?php else: ?>
        <p class="text-xs text-slate-500">Pilih tugas pada daftar sebelah kiri untuk memproses Berita Acara.</p>
      <?php endif; ?>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

