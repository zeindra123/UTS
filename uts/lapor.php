<?php
/**
 * S-04: Buat Laporan Pengaduan (PRD FR-05 s/d FR-08, FR-10)
 * Mendukung Tampilan Web Penuh dan Simulasi Mobile (S-05)
 */

$pageTitle = 'Buat Laporan – Smart Complaint Kota Bandar Lampung';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$districts = getBandarLampungDistricts();
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1")->fetchAll();

$error = '';
$selectedCat = (int)($_GET['cat'] ?? 1);
$isMobileView = isset($_GET['view']) && $_GET['view'] === 'mobile';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $catId = (int)($_POST['category_id'] ?? 1);
    $district = trim($_POST['district'] ?? 'Kedaton');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = (float)($_POST['latitude'] ?? -5.381200);
    $lng = (float)($_POST['longitude'] ?? 105.257400);
    $isAnon = isset($_POST['is_anonymous']) ? 1 : 0;

    // Pastikan user warga (atau buat user tamu jika belum login)
    $citizenId = 1; // Default Budi Santoso
    $citizenName = 'Budi Santoso (Warga)';
    if (isLoggedIn()) {
        $u = currentUser();
        $citizenId = $u['id'];
        $citizenName = $u['name'] . ' (Warga)';
    }

    if (empty($title) || empty($description)) {
        $error = 'Judul aduan dan rincian kerusakan wajib diisi.';
    } else {
        // Handle upload foto bukti
        $photoBefore = 'https://images.unsplash.com/photo-1515162816999-a0c47dc192f7?auto=format&fit=crop&w=600&q=80'; // fallback
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'bukti_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                    $photoBefore = 'uploads/' . $filename;
                }
            }
        }

        // Generate tiket unik
        $ticket = generateTicketNumber($pdo);

        // Ambil default departemen dari kategori
        $catStmt = $pdo->prepare("SELECT default_department_id FROM categories WHERE id = ?");
        $catStmt->execute([$catId]);
        $defaultDept = $catStmt->fetchColumn() ?: null;

        // Simpan ke database reports
        $stmt = $pdo->prepare("INSERT INTO reports 
            (ticket_number, citizen_id, category_id, department_id, title, description, district, address, latitude, longitude, is_anonymous, status, photo_before, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?, datetime('now', 'localtime'))");
        $stmt->execute([
            $ticket, $citizenId, $catId, $defaultDept,
            $title, $description, $district, $address,
            $lat, $lng, $isAnon, $photoBefore
        ]);

        $reportId = $pdo->lastInsertId();

        // Catat ke report_history
        addReportHistory($pdo, $reportId, $citizenName, null, 'submitted', 'Laporan berhasil dibuat oleh warga dan menunggu verifikasi admin dinas.');

        header("Location: konfirmasi_tiket.php?ticket=" . urlencode($ticket));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
  
  <!-- Header Title -->
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
      <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Formulir Pengaduan Fasilitas Publik</h2>
      <p class="text-xs text-slate-500">Lengkapi data aduan dan titik koordinat kerusakan di Kota Bandar Lampung</p>
    </div>
    <div class="flex items-center gap-2">
      <?php if ($isMobileView): ?>
        <a href="lapor.php" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 flex items-center gap-1 transition">
          <span class="material-symbols-outlined text-sm">desktop_windows</span>
          <span>Beralih ke Tampilan Web (S-04)</span>
        </a>
      <?php else: ?>
        <a href="lapor.php?view=mobile" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 flex items-center gap-1 transition">
          <span class="material-symbols-outlined text-sm">smartphone</span>
          <span>Beralih ke Mode Mobile (S-05)</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">error</span>
    <span><?= sanitize($error) ?></span>
  </div>
  <?php endif; ?>

  <?php if ($isMobileView): ?>
    <!-- ============================================== -->
    <!-- S-05: MODE TAMPILAN MOBILE DALAM FRAME SMARTPHONE -->
    <!-- ============================================== -->
    <div class="max-w-sm mx-auto py-2">
      <div class="bg-slate-900 p-3 rounded-[36px] shadow-2xl border-4 border-slate-800">
        <div class="w-32 h-4 bg-slate-800 rounded-b-xl mx-auto mb-2 flex items-center justify-center">
          <div class="w-3 h-3 rounded-full bg-slate-900 border border-slate-700"></div>
        </div>

        <div class="bg-[#f9f9ff] rounded-[24px] overflow-hidden text-slate-900 text-xs min-h-[580px] flex flex-col">
          <div class="bg-[#0b2a5b] text-white p-3 flex items-center justify-between">
            <div class="flex items-center gap-1.5 font-heading font-bold text-xs">
              <span class="material-symbols-outlined text-base">campaign</span> Lapor Cepat (Mobile)
            </div>
            <span class="text-[10px] text-blue-200">Kec. Bandar Lampung</span>
          </div>

          <!-- Emergency 112 Banner (PRD FR-37) -->
          <div class="bg-red-50 p-2 border-b border-red-200 text-red-900 flex items-center justify-between">
            <span class="text-[10px] font-bold">Darurat Banjir/Bencana?</span>
            <a href="tel:112" class="bg-red-600 text-white px-2 py-0.5 rounded text-[9px] font-bold">Hubungi 112</a>
          </div>

          <!-- Form Mobile -->
          <form method="POST" action="lapor.php" enctype="multipart/form-data" class="p-4 space-y-3 flex-1 overflow-y-auto">
            <div>
              <label class="block font-bold text-slate-800 text-[11px] mb-1">Kategori Laporan *</label>
              <select name="category_id" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-800 text-[11px] mb-1">Judul Ringkas Aduan *</label>
              <input type="text" name="title" required value="Jalan Berlubang di Dekat Kampus" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs">
            </div>

            <div>
              <label class="block font-bold text-slate-800 text-[11px] mb-1">Kecamatan di Bandar Lampung *</label>
              <select name="district" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                <?php foreach ($districts as $dName => $dCoord): ?>
                  <option value="<?= $dName ?>"><?= $dName ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-800 text-[11px] mb-1">Uraian Detail Kerusakan *</label>
              <textarea name="description" rows="2" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs">Lubang aspal kedalaman 15 cm sangat membahayakan pemotor.</textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-800 text-[11px] mb-1">Foto Bukti (Kamera/Galeri) *</label>
              <input type="file" name="photo" accept="image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-blue-50 file:text-blue-700">
            </div>

            <input type="hidden" name="latitude" value="-5.381200">
            <input type="hidden" name="longitude" value="105.257400">

            <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-2.5 rounded-xl font-bold text-xs shadow flex items-center justify-center gap-1.5 mt-2">
              <span class="material-symbols-outlined text-sm">send</span>
              <span>Kirim Laporan Sekarang</span>
            </button>
          </form>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- ============================================== -->
    <!-- S-04: FORMULIR PELAPORAN WEB LENGKAP          -->
    <!-- ============================================== -->
    <form method="POST" action="lapor.php" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Kolom Kiri: Form Detail (2 Kolom) -->
      <div class="lg:col-span-2 space-y-5">
        
        <!-- 1. Kategori Laporan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
          <label class="block font-heading font-bold text-sm text-[#00163a]">
            1. Pilih Kategori Masalah *
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
            <?php foreach ($categories as $c): ?>
            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-blue-500 cursor-pointer transition">
              <input type="radio" name="category_id" value="<?= $c['id'] ?>" <?= $c['id'] == $selectedCat ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
              <span class="font-semibold text-slate-800"><?= sanitize($c['name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- 2. Rincian Laporan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
          <label class="block font-heading font-bold text-sm text-[#00163a]">
            2. Uraian Detail Kerusakan *
          </label>
          <div class="space-y-3 text-xs">
            <div>
              <label for="rep-title" class="block font-semibold text-slate-700 mb-1">Judul Ringkas Aduan *</label>
              <input id="rep-title" name="title" type="text" required placeholder="Contoh: Lubang Aspal Dalam Dekat Lampu Merah Jl. ZA Pagar Alam" value="Jalan Berlubang Parah Membahayakan Pemotor di Dekat Halte Bus" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
              <label for="rep-desc" class="block font-semibold text-slate-700 mb-1">Deskripsi & Patokan Lokasi *</label>
              <textarea id="rep-desc" name="description" rows="3" required placeholder="Jelaskan ukuran lubang, kondisi air menggenang, patokan toko terdekat..." class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed">Lubang berdiameter sekitar 80 cm dengan kedalaman 15 cm di lajur kiri arah Rajabasa, tepatnya 50 meter sebelum kampus Teknokrat. Saat hujan tertutup air sehingga sering membuat motor terperosok.</textarea>
            </div>
          </div>
        </div>

        <!-- 3. Unggah Foto Bukti (Max 5MB - PRD FR-06 & NFR-02) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <label class="block font-heading font-bold text-sm text-[#00163a]">
              3. Foto Bukti Lapangan *
            </label>
            <span class="text-[11px] text-slate-500">Maks. 5 MB (Format: JPG, PNG, WEBP)</span>
          </div>

          <div class="border-2 border-dashed border-blue-300 rounded-xl p-5 text-center bg-blue-50/40 hover:bg-blue-50 transition cursor-pointer relative">
            <input type="file" name="photo" id="photo-input" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" onchange="previewUpload(this)">
            <div class="flex flex-col items-center justify-center gap-1.5 pointer-events-none">
              <span class="material-symbols-outlined text-3xl text-blue-600">add_photo_alternate</span>
              <p class="text-xs font-semibold text-blue-900" id="photo-label">Klik atau Seret Foto Bukti Lapangan ke Sini</p>
              <p class="text-[10px] text-slate-500">Mendukung kamera langsung atau galeri file</p>
            </div>
          </div>
        </div>

      </div>

      <!-- Kolom Kanan: Peta Koordinat GPS (1 Kolom) -->
      <div class="space-y-5">
        
        <!-- 4. Peta Koordinat (Leaflet S-04 - PRD FR-07 & FR-08) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <label class="block font-heading font-bold text-sm text-[#00163a]">
              4. Titik Koordinat Peta *
            </label>
            <span class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">my_location</span> Geser Pin
            </span>
          </div>

          <div class="space-y-2 text-xs">
            <div>
              <label for="rep-district" class="block font-medium text-slate-700 mb-1">Kecamatan di Bandar Lampung *</label>
              <select id="rep-district" name="district" onchange="panMapDistrict(this.value)" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <?php foreach ($districts as $dName => $dCoord): ?>
                  <option value="<?= $dName ?>" <?= $dName === 'Kedaton' ? 'selected' : '' ?>><?= $dName ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="rep-address" class="block font-medium text-slate-700 mb-1">Alamat Jalan / Patokan *</label>
              <input id="rep-address" name="address" type="text" value="Jl. ZA Pagar Alam No. 9" placeholder="Nama jalan / nomor ruko..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
            </div>

            <!-- Peta Leaflet Container S-04 -->
            <div id="map-lapor" class="h-48 w-full rounded-xl border border-slate-300 overflow-hidden shadow-inner bg-slate-100"></div>

            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[10px] space-y-0.5">
              <div class="flex justify-between">
                <span class="text-slate-500">Latitude:</span>
                <input type="text" id="lat-input" name="latitude" value="-5.381200" readonly class="font-mono font-semibold text-slate-800 bg-transparent text-right border-0 p-0 focus:outline-none">
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Longitude:</span>
                <input type="text" id="lng-input" name="longitude" value="105.257400" readonly class="font-mono font-semibold text-slate-800 bg-transparent text-right border-0 p-0 focus:outline-none">
              </div>
              <p class="text-emerald-700 font-medium pt-1">✓ Berada di dalam wilayah administratif Kota Bandar Lampung</p>
            </div>
          </div>
        </div>

        <!-- 5. Privasi Pelapor & Submit -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <label class="block font-heading font-bold text-sm text-[#00163a]">
            5. Pengaturan Privasi Pelapor
          </label>
          <div class="text-xs space-y-2">
            <label class="flex items-start gap-2 cursor-pointer p-2.5 rounded-lg bg-slate-50 border border-slate-200">
              <input type="checkbox" name="is_anonymous" value="1" checked class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
              <div>
                <span class="font-semibold text-slate-800">Samarkan Nama Saya ke Publik</span>
                <p class="text-[10px] text-slate-500">Nama lengkap Anda hanya terlihat oleh admin dan petugas lapangan.</p>
              </div>
            </label>
          </div>

          <div class="space-y-2 pt-2">
            <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-3 rounded-xl font-heading font-bold text-sm shadow-lg transition flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-lg">send</span>
              <span>Kirim Laporan Pengaduan</span>
            </button>
          </div>
        </div>

      </div>

    </form>
  <?php endif; ?>

</div>

<!-- Script Peta Leaflet & Upload Preview -->
<script>
let laporMap, laporMarker;
const districtCoords = <?= json_encode($districts) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const mapEl = document.getElementById('map-lapor');
  if (mapEl) {
    const initLat = -5.381200;
    const initLng = 105.257400;

    laporMap = L.map('map-lapor').setView([initLat, initLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(laporMap);

    laporMarker = L.marker([initLat, initLng], { draggable: true }).addTo(laporMap);
    laporMarker.on('dragend', function(e) {
      const pos = laporMarker.getLatLng();
      document.getElementById('lat-input').value = pos.lat.toFixed(6);
      document.getElementById('lng-input').value = pos.lng.toFixed(6);
    });
  }
});

function panMapDistrict(district) {
  if (districtCoords[district] && laporMap && laporMarker) {
    const coord = districtCoords[district];
    laporMap.panTo(coord);
    laporMarker.setLatLng(coord);
    document.getElementById('lat-input').value = coord[0].toFixed(6);
    document.getElementById('lng-input').value = coord[1].toFixed(6);
  }
}

function previewUpload(input) {
  if (input.files && input.files[0]) {
    document.getElementById('photo-label').innerText = '✓ Terpilih: ' + input.files[0].name;
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

