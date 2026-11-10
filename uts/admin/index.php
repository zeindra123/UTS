<?php
/**
 * S-11: Pusat Kontrol GIS Admin & S-12: Monitoring Rekapitulasi (PRD FR-17, FR-27 s/d FR-31)
 */

$basePath = '../';
$pageTitle = 'Pusat Kontrol Admin Dinas – Smart Complaint';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin'], '../login.php?quick=admin');

$pdo = getDB();

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Rekap_Pengaduan_Kota_Bandar_Lampung_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No Tiket', 'Kategori', 'Kecamatan', 'Alamat', 'Latitude', 'Longitude', 'Dinas Penangan', 'Status', 'Tanggal Lapor']);

    $stmt = $pdo->query("SELECT r.ticket_number, c.name as category_name, r.district, r.address, r.latitude, r.longitude, d.name as dept_name, r.status, r.created_at 
        FROM reports r 
        LEFT JOIN categories c ON r.category_id = c.id 
        LEFT JOIN departments d ON r.department_id = d.id 
        ORDER BY r.id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['ticket_number'],
            $row['category_name'],
            $row['district'],
            $row['address'],
            $row['latitude'],
            $row['longitude'],
            $row['dept_name'] ?? '-',
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Hitung 7 Status Lengkap
$statTotal = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$statSubmitted = $pdo->query("SELECT COUNT(*) FROM reports WHERE status IN ('submitted', 'pending_verification')")->fetchColumn();
$statInProgress = $pdo->query("SELECT COUNT(*) FROM reports WHERE status IN ('assigned', 'in_progress', 'awaiting_citizen_confirmation')")->fetchColumn();
$statResolved = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
$statRevision = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'needs_revision'")->fetchColumn();
$statRejected = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'rejected'")->fetchColumn();
$statOutside = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'outside_area'")->fetchColumn();

// Filter Query
$filterStatus = $_GET['status'] ?? '';
$filterDistrict = $_GET['district'] ?? '';

$sql = "SELECT r.*, c.name as category_name, d.name as dept_name 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    WHERE 1=1";
$params = [];

if ($filterStatus) {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}
if ($filterDistrict) {
    $sql .= " AND r.district = ?";
    $params[] = $filterDistrict;
}
$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Koordinat untuk Leaflet Peta S-11
$mapPoints = [];
foreach ($reports as $r) {
    $mapPoints[] = [
        'ticket' => $r['ticket_number'],
        'title' => $r['title'],
        'district' => $r['district'],
        'lat' => $r['latitude'],
        'lng' => $r['longitude'],
        'status' => $r['status']
    ];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Header & Ekspor -->
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Pusat Kontrol Geospasial Admin Dinas</h2>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">Command Center GIS</span>
      </div>
      <p class="text-xs text-slate-500">Pemantauan Sebaran 20 Kecamatan Kota Bandar Lampung &bull; SQLite Spasial View</p>
    </div>
    <div class="flex items-center gap-2 text-xs">
      <a href="verifikasi.php" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-xl font-semibold shadow transition flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">fact_check</span>
        <span>Verifikasi Laporan Baru (<?= $statSubmitted ?>)</span>
      </a>
      <a href="master.php" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-xl font-semibold shadow transition flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">settings</span>
        <span>Master Data OPD</span>
      </a>
      <a href="index.php?export=csv" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-xl font-semibold shadow transition flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">download</span>
        <span>Ekspor CSV</span>
      </a>
    </div>
  </div>

  <!-- 7 Kartu Status Lengkap (PRD 8.1) -->
  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 text-center text-xs">
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
      <p class="text-[10px] text-slate-500">Total Masuk</p>
      <p class="text-xl font-bold font-heading text-slate-900 mt-0.5"><?= $statTotal ?></p>
    </div>
    <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 shadow-sm">
      <p class="text-[10px] text-amber-800 font-medium">Menunggu Verif</p>
      <p class="text-xl font-bold font-heading text-amber-700 mt-0.5"><?= $statSubmitted ?></p>
    </div>
    <div class="bg-blue-50 p-3 rounded-xl border border-blue-200 shadow-sm">
      <p class="text-[10px] text-blue-800 font-medium">Diproses Regu</p>
      <p class="text-xl font-bold font-heading text-blue-700 mt-0.5"><?= $statInProgress ?></p>
    </div>
    <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200 shadow-sm">
      <p class="text-[10px] text-emerald-800 font-medium">Selesai Tuntas</p>
      <p class="text-xl font-bold font-heading text-emerald-700 mt-0.5"><?= $statResolved ?></p>
    </div>
    <div class="bg-orange-50 p-3 rounded-xl border border-orange-200 shadow-sm">
      <p class="text-[10px] text-orange-800 font-medium">Perlu Revisi</p>
      <p class="text-xl font-bold font-heading text-orange-700 mt-0.5"><?= $statRevision ?></p>
    </div>
    <div class="bg-red-50 p-3 rounded-xl border border-red-200 shadow-sm">
      <p class="text-[10px] text-red-800 font-medium">Ditolak</p>
      <p class="text-xl font-bold font-heading text-red-700 mt-0.5"><?= $statRejected ?></p>
    </div>
    <div class="bg-slate-100 p-3 rounded-xl border border-slate-300 shadow-sm col-span-2 sm:col-span-1">
      <p class="text-[10px] text-slate-600 font-medium">Luar Wilayah</p>
      <p class="text-xl font-bold font-heading text-slate-700 mt-0.5"><?= $statOutside ?></p>
    </div>
  </div>

  <!-- Peta Sebaran GIS Leaflet (S-11) -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="font-heading font-bold text-base text-[#00163a]">Peta Sebaran Laporan Aktif di Bandar Lampung</h3>
      <span class="text-xs text-slate-500">Klik marker penanda untuk aksi cepat</span>
    </div>
    <div id="map-admin" class="h-80 w-full rounded-xl border border-slate-300 overflow-hidden bg-slate-100 shadow-inner"></div>
  </div>

  <!-- Tabel Master Laporan Rekapitulasi (S-12) -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h3 class="font-heading font-bold text-base text-[#00163a]">Daftar Master Pengaduan Terdaftar</h3>
      
      <form method="GET" action="index.php" class="flex flex-wrap items-center gap-2 text-xs">
        <select name="status" onchange="this.form.submit()" class="px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white">
          <option value="">Semua Status</option>
          <option value="submitted" <?= $filterStatus === 'submitted' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
          <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>Diproses</option>
          <option value="awaiting_citizen_confirmation" <?= $filterStatus === 'awaiting_citizen_confirmation' ? 'selected' : '' ?>>Konfirmasi Warga</option>
          <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>Selesai</option>
        </select>
        <select name="district" onchange="this.form.submit()" class="px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white">
          <option value="">Semua Kecamatan</option>
          <option value="Kedaton" <?= $filterDistrict === 'Kedaton' ? 'selected' : '' ?>>Kedaton</option>
          <option value="Rajabasa" <?= $filterDistrict === 'Rajabasa' ? 'selected' : '' ?>>Rajabasa</option>
          <option value="Tanjung Karang Pusat" <?= $filterDistrict === 'Tanjung Karang Pusat' ? 'selected' : '' ?>>Tanjung Karang Pusat</option>
          <option value="Way Halim" <?= $filterDistrict === 'Way Halim' ? 'selected' : '' ?>>Way Halim</option>
          <option value="Sukarame" <?= $filterDistrict === 'Sukarame' ? 'selected' : '' ?>>Sukarame</option>
        </select>
      </form>
    </div>

    <!-- Tabel Data Master -->
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3">Tiket</th>
            <th class="py-2.5 px-3">Bukti</th>
            <th class="py-2.5 px-3">Kategori & Judul</th>
            <th class="py-2.5 px-3">Kecamatan</th>
            <th class="py-2.5 px-3">Dinas Tujuan</th>
            <th class="py-2.5 px-3">Status</th>
            <th class="py-2.5 px-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($reports as $r): ?>
          <tr class="hover:bg-slate-50 transition">
            <td class="py-3 px-3 font-mono font-bold text-blue-900"><?= sanitize($r['ticket_number']) ?></td>
            <td class="py-3 px-3">
              <div class="w-9 h-9 rounded bg-slate-200 overflow-hidden">
                <img src="<?= sanitize($r['photo_before']) ?>" alt="Bukti" class="w-full h-full object-cover">
              </div>
            </td>
            <td class="py-3 px-3">
              <span class="font-semibold text-slate-800 block"><?= sanitize($r['title']) ?></span>
              <span class="text-[10px] text-slate-500"><?= sanitize($r['category_name']) ?></span>
            </td>
            <td class="py-3 px-3"><?= sanitize($r['district']) ?></td>
            <td class="py-3 px-3 font-medium text-slate-700"><?= sanitize($r['dept_name'] ?? 'Menunggu Disposisi') ?></td>
            <td class="py-3 px-3"><?= getStatusBadge($r['status']) ?></td>
            <td class="py-3 px-3 text-right space-x-1">
              <a href="verifikasi.php?ticket=<?= urlencode($r['ticket_number']) ?>" class="px-2.5 py-1 rounded bg-blue-600 text-white font-medium hover:bg-blue-700">Periksa</a>
              <a href="../cetak_bap.php?ticket=<?= urlencode($r['ticket_number']) ?>" target="_blank" class="px-2 py-1 rounded bg-slate-200 text-slate-700 font-medium hover:bg-slate-300">BAP A4</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Inisialisasi Peta GIS Leaflet S-11 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const map = L.map('map-admin').setView([-5.4297, 105.2625], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const points = <?= json_encode($mapPoints) ?>;
  points.forEach(p => {
    L.marker([p.lat, p.lng]).addTo(map).bindPopup(`
      <div style="font-size:12px; font-family:sans-serif;">
        <b style="color:#00163a">${p.ticket}</b><br>
        <span>${p.title}</span><br>
        <span style="color:#64748b">Kec. ${p.district} (${p.status})</span><br>
        <a href="verifikasi.php?ticket=${p.ticket}" style="color:#005cbd; font-weight:bold; text-decoration:underline;">Buka Verifikasi</a>
      </div>
    `);
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

