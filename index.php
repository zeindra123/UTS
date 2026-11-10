<?php
/**
 * S-01: Beranda Publik (PRD 9.2 & FR-29)
 * Smart Complaint Kota Bandar Lampung
 */

$pageTitle = 'Smart Complaint – Pengaduan Fasilitas Publik Kota Bandar Lampung';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Hitung statistik dinamis
$totalReports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$inProgress = $pdo->query("SELECT COUNT(*) FROM reports WHERE status IN ('assigned', 'in_progress', 'awaiting_citizen_confirmation')")->fetchColumn();
$resolved = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();

// Ambil laporan untuk peta sebaran publik
$mapReports = $pdo->query("SELECT ticket_number, title, district, latitude, longitude, status FROM reports LIMIT 20")->fetchAll();

// Ambil 3 laporan terkini
$recentReports = $pdo->query("SELECT r.*, c.name as category_name, d.name as dept_name 
    FROM reports r 
    LEFT JOIN categories c ON r.category_id = c.id 
    LEFT JOIN departments d ON r.department_id = d.id 
    ORDER BY r.id DESC LIMIT 3")->fetchAll();
?>

<div class="space-y-8">
  <!-- Hero Banner -->
  <div class="bg-gradient-to-br from-[#00163a] via-[#0b2a5b] to-[#005cbd] rounded-2xl p-6 sm:p-10 text-white relative overflow-hidden shadow-xl border border-blue-900">
    <div class="relative z-10 max-w-2xl space-y-4">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-xs font-semibold text-blue-200">
        <span class="material-symbols-outlined text-sm text-yellow-300">verified</span>
        Sistem Layanan Pengaduan Berbasis Lokasi Spasial
      </div>
      <h1 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl tracking-tight leading-tight">
        Sampaikan Keluhan Fasilitas Kota Bandar Lampung
      </h1>
      <p class="text-blue-100 text-sm sm:text-base leading-relaxed">
        Laporkan jalan berlubang, lampu PJU padam, tumpukan sampah liar, drainase tersumbat, dan genangan banjir secara transparan, mudah, dan terpantau sampai tuntas.
      </p>
      <div class="pt-2 flex flex-wrap gap-3">
        <a href="lapor.php" class="bg-blue-500 hover:bg-blue-400 text-white font-semibold px-5 py-3 rounded-xl shadow-lg transition flex items-center gap-2 text-sm">
          <span class="material-symbols-outlined text-lg">add_circle</span>
          <span>Buat Laporan Baru</span>
        </a>
        <a href="lacak.php" class="bg-white/15 hover:bg-white/25 text-white font-semibold px-5 py-3 rounded-xl border border-white/20 backdrop-blur-sm transition flex items-center gap-2 text-sm">
          <span class="material-symbols-outlined text-lg">search</span>
          <span>Lacak Aduan dengan Nomor Tiket</span>
        </a>
      </div>
    </div>
    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>
  </div>

  <!-- 5 Kategori Laporan Utama (PRD 8.8) -->
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="font-heading font-bold text-xl text-[#00163a]">Kategori Pengaduan Fasilitas</h2>
        <p class="text-xs text-slate-500">Pilih jenis kendala yang Anda temukan di wilayah Kota Bandar Lampung</p>
      </div>
      <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">5 Sektor Utama</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <a href="lapor.php?cat=1" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center space-y-2 block group">
        <div class="w-12 h-12 mx-auto rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center group-hover:scale-105 transition">
          <span class="material-symbols-outlined text-2xl">traffic</span>
        </div>
        <h3 class="font-heading font-semibold text-sm text-slate-800">Jalan Rusak</h3>
        <p class="text-[11px] text-slate-500 leading-snug">Lubang jalan, aspal amblas, trotoar hancur</p>
      </a>

      <a href="lapor.php?cat=2" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center space-y-2 block group">
        <div class="w-12 h-12 mx-auto rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-105 transition">
          <span class="material-symbols-outlined text-2xl">light</span>
        </div>
        <h3 class="font-heading font-semibold text-sm text-slate-800">Lampu PJU Padam</h3>
        <p class="text-[11px] text-slate-500 leading-snug">Penerangan mati, tiang roboh, korsleting</p>
      </a>

      <a href="lapor.php?cat=3" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center space-y-2 block group">
        <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:scale-105 transition">
          <span class="material-symbols-outlined text-2xl">delete</span>
        </div>
        <h3 class="font-heading font-semibold text-sm text-slate-800">Sampah Liar</h3>
        <p class="text-[11px] text-slate-500 leading-snug">TPS meluap, tumpukan liar, bau menyengat</p>
      </a>

      <a href="lapor.php?cat=4" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center space-y-2 block group">
        <div class="w-12 h-12 mx-auto rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center group-hover:scale-105 transition">
          <span class="material-symbols-outlined text-2xl">flood</span>
        </div>
        <h3 class="font-heading font-semibold text-sm text-slate-800">Banjir & Drainase</h3>
        <p class="text-[11px] text-slate-500 leading-snug">Gorong-gorong mampet, genangan air hujan</p>
      </a>

      <a href="lapor.php?cat=5" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition text-center space-y-2 col-span-2 sm:col-span-1 block group">
        <div class="w-12 h-12 mx-auto rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center group-hover:scale-105 transition">
          <span class="material-symbols-outlined text-2xl">park</span>
        </div>
        <h3 class="font-heading font-semibold text-sm text-slate-800">Gangguan Lingkungan</h3>
        <p class="text-[11px] text-slate-500 leading-snug">Pohon rawan tumbang, fasilitas taman rusak</p>
      </a>
    </div>
  </div>

  <!-- 4 Langkah Alur Penanganan (PRD 8.2) -->
  <div class="bg-white p-6 rounded-2xl border border-slate-200 space-y-4">
    <h2 class="font-heading font-bold text-lg text-[#00163a]">Alur Penanganan Transparan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-[#00163a] text-white flex items-center justify-center font-bold text-xs shrink-0">1</span>
        <div>
          <h4 class="font-semibold text-xs text-slate-900">Kirim Aduan & Lokasi</h4>
          <p class="text-[11px] text-slate-600 mt-0.5">Warga mengunggah foto bukti dan menandai titik GPS di peta.</p>
        </div>
      </div>
      <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-[#0b2a5b] text-white flex items-center justify-center font-bold text-xs shrink-0">2</span>
        <div>
          <h4 class="font-semibold text-xs text-slate-900">Verifikasi & Disposisi</h4>
          <p class="text-[11px] text-slate-600 mt-0.5">Admin dinas memeriksa validasi aduan lalu menugaskan ke regu teknis.</p>
        </div>
      </div>
      <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-[#005cbd] text-white flex items-center justify-center font-bold text-xs shrink-0">3</span>
        <div>
          <h4 class="font-semibold text-xs text-slate-900">Eksekusi Lapangan</h4>
          <p class="text-[11px] text-slate-600 mt-0.5">Petugas lapangan memperbaiki kerusakan dan mengunggah Berita Acara.</p>
        </div>
      </div>
      <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">4</span>
        <div>
          <h4 class="font-semibold text-xs text-slate-900">Konfirmasi Warga</h4>
          <p class="text-[11px] text-slate-600 mt-0.5">Warga menilai hasil pekerjaan atau menyampaikan sanggahan.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistik Agregat Dinamis dari Database (PRD 1.3 Butir 4) -->
  <div class="bg-gradient-to-r from-slate-900 to-[#0b2a5b] text-white p-6 rounded-2xl border border-slate-800 space-y-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-amber-400">bar_chart</span>
        <h2 class="font-heading font-bold text-base text-white">Statistik Kinerja Penanganan Nyata</h2>
      </div>
      <span class="text-[10px] font-semibold tracking-wider uppercase px-2 py-0.5 bg-amber-400/20 text-amber-300 border border-amber-400/30 rounded">
        Data Real Database SQLite
      </span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-1">
      <div class="bg-white/5 p-3 rounded-xl border border-white/10">
        <p class="text-xs text-blue-200">Total Pengaduan Masuk</p>
        <p class="text-2xl font-bold font-heading text-white mt-1"><?= $totalReports ?></p>
        <p class="text-[10px] text-slate-400">Tercatat di basis data</p>
      </div>
      <div class="bg-white/5 p-3 rounded-xl border border-white/10">
        <p class="text-xs text-blue-200">Sedang Ditangani Regu</p>
        <p class="text-2xl font-bold font-heading text-amber-300 mt-1"><?= $inProgress ?></p>
        <p class="text-[10px] text-slate-400">Dalam tindak lanjut lapangan</p>
      </div>
      <div class="bg-white/5 p-3 rounded-xl border border-white/10">
        <p class="text-xs text-blue-200">Telah Selesai Tuntas</p>
        <p class="text-2xl font-bold font-heading text-emerald-400 mt-1"><?= $resolved ?></p>
        <p class="text-[10px] text-slate-400">Tervalidasi tuntas</p>
      </div>
      <div class="bg-white/5 p-3 rounded-xl border border-white/10">
        <p class="text-xs text-blue-200">Target Respons Verifikasi</p>
        <p class="text-2xl font-bold font-heading text-blue-300 mt-1">&le; 24 Jam</p>
        <p class="text-[10px] text-slate-400">Standar SLA simulasi</p>
      </div>
    </div>
  </div>

  <!-- Peta Sebaran Publik Tersamar (Leaflet Map Dinamis - PRD FR-29 & NFR-08) -->
  <div class="bg-white p-6 rounded-2xl border border-slate-200 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="font-heading font-bold text-base text-[#00163a]">Peta Sebaran Laporan Publik Tersamar</h2>
        <p class="text-xs text-slate-500">Koordinat diambil dinamis dari tabel pengaduan Kota Bandar Lampung</p>
      </div>
      <div class="flex items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Menunggu</span>
        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Diproses</span>
        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Selesai</span>
      </div>
    </div>
    <!-- Container Peta Leaflet -->
    <div id="map-home" class="h-80 w-full rounded-xl border border-slate-200 overflow-hidden shadow-inner bg-slate-100"></div>
  </div>

  <!-- Transparansi Feed Laporan Terkini -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-heading font-bold text-base text-[#00163a]">Laporan Terkini di Kota Bandar Lampung</h3>
      <span class="text-xs text-slate-500">Transparansi Publik Real-time</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
      <?php foreach ($recentReports as $rep): ?>
      <div class="p-3.5 rounded-xl border border-slate-200 space-y-2 bg-slate-50">
        <div class="flex justify-between items-center">
          <span class="font-mono text-[11px] text-slate-600 font-bold"><?= sanitize($rep['ticket_number']) ?></span>
          <?= getStatusBadge($rep['status']) ?>
        </div>
        <h4 class="font-semibold text-slate-800 line-clamp-1"><?= sanitize($rep['title']) ?></h4>
        <p class="text-[11px] text-slate-500">Kec. <?= sanitize($rep['district']) ?> &bull; <?= sanitize($rep['dept_name'] ?? 'Menunggu Disposisi') ?></p>
        <div class="pt-1 text-right">
          <a href="lacak.php?ticket=<?= urlencode($rep['ticket_number']) ?>" class="text-blue-600 font-semibold hover:underline text-[11px]">
            Lacak Status &rarr;
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Inisialisasi Peta Leaflet untuk Beranda -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const map = L.map('map-home').setView([-5.4297, 105.2625], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const reports = <?= json_encode($mapReports) ?>;
  reports.forEach(r => {
    let color = '#2563eb';
    if (r.status === 'resolved') color = '#16a34a';
    if (r.status === 'pending_verification' || r.status === 'submitted') color = '#f59e0b';

    L.circle([r.latitude, r.longitude], {
      radius: 250,
      color: color,
      fillColor: color,
      fillOpacity: 0.5
    }).addTo(map).bindPopup(`
      <div style="font-size:12px; font-family:sans-serif;">
        <b style="color:#00163a">${r.ticket_number}</b><br>
        <span>${r.title}</span><br>
        <span style="color:#64748b">Kec. ${r.district}</span><br>
        <a href="lacak.php?ticket=${r.ticket_number}" style="color:#005cbd; font-weight:bold; text-decoration:underline;">Lihat Detail</a>
      </div>
    `);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

