<?php
/**
 * S-15: Manajemen Master Data (OPD, Petugas & Kategori)
 * PRD FR-16, FR-34, FR-42
 */

$basePath = '../';
$pageTitle = 'Master Data Sistem – Smart Complaint';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin'], '../login.php?quick=admin');

$pdo = getDB();
$msg = '';

// Handle tambah OPD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['dept_name'] ?? '');
    $code = trim($_POST['dept_code'] ?? '');
    $desc = trim($_POST['dept_desc'] ?? '');
    if ($name && $code) {
        $ins = $pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?)");
        $ins->execute([$name, $code, $desc]);
        $msg = 'Unit / OPD baru berhasil ditambahkan.';
    }
}

// Handle tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $catName = trim($_POST['cat_name'] ?? '');
    $catDesc = trim($_POST['cat_desc'] ?? '');
    $catDept = (int)($_POST['default_dept_id'] ?? 1);
    if ($catName) {
        $ins = $pdo->prepare("INSERT INTO categories (name, description, default_department_id) VALUES (?, ?, ?)");
        $ins->execute([$catName, $catDesc, $catDept]);
        $msg = 'Kategori aduan baru berhasil ditambahkan.';
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();
$categories = $pdo->query("SELECT c.*, d.name as dept_name FROM categories c LEFT JOIN departments d ON c.default_department_id = d.id ORDER BY c.id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
  
  <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
    <div>
      <h2 class="font-heading font-extrabold text-2xl text-[#00163a]">Pengaturan Sistem & Master Data</h2>
      <p class="text-xs text-slate-500">Kelola OPD Penangan Simulasi, Kategori Laporan, dan Batasan Sistem (PRD FR-16, FR-34, FR-42)</p>
    </div>
    <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300">
      Kembali ke Pusat Kontrol
    </a>
  </div>

  <?php if ($msg): ?>
  <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-center gap-2">
    <span class="material-symbols-outlined text-base">check_circle</span>
    <span><?= sanitize($msg) ?></span>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- 1. Kelola Dinas & OPD (FR-16) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-heading font-bold text-sm text-[#00163a]">Daftar OPD Penangan Terdaftar</h3>
        <span class="text-xs text-slate-500"><?= count($departments) ?> Unit Aktif</span>
      </div>

      <div class="space-y-2 text-xs">
        <?php foreach ($departments as $d): ?>
        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
          <div>
            <p class="font-bold text-slate-800"><?= sanitize($d['name']) ?> (<?= $d['code'] ?>)</p>
            <p class="text-[10px] text-slate-500"><?= sanitize($d['description']) ?></p>
          </div>
          <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold text-[10px]">Aktif</span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Form Tambah OPD -->
      <form method="POST" action="master.php" class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs">
        <input type="hidden" name="add_department" value="1">
        <p class="font-bold text-slate-700 text-xs">+ Tambah Unit / OPD Baru</p>
        <div class="grid grid-cols-2 gap-2">
          <input type="text" name="dept_name" required placeholder="Nama Dinas..." class="px-2.5 py-1.5 border border-slate-300 rounded-lg">
          <input type="text" name="dept_code" required placeholder="Kode (misal: DISHUB)..." class="px-2.5 py-1.5 border border-slate-300 rounded-lg">
        </div>
        <input type="text" name="dept_desc" placeholder="Kewenangan / uraian bidang..." class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg">
        <button type="submit" class="w-full bg-[#005cbd] hover:bg-[#004791] text-white py-1.5 rounded-lg font-semibold text-xs transition">
          Simpan OPD Baru
        </button>
      </form>
    </div>

    <!-- 2. Kelola Kategori Aduan (FR-34) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-heading font-bold text-sm text-[#00163a]">Daftar Kategori Pengaduan</h3>
        <span class="text-xs text-slate-500"><?= count($categories) ?> Kategori</span>
      </div>

      <div class="space-y-2 text-xs max-h-60 overflow-y-auto">
        <?php foreach ($categories as $c): ?>
        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
          <div>
            <p class="font-bold text-slate-800"><?= sanitize($c['name']) ?></p>
            <p class="text-[10px] text-slate-500"><?= sanitize($c['description']) ?></p>
          </div>
          <span class="text-[10px] text-slate-400 font-mono"><?= sanitize($c['dept_name']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- 3. Batasan Sistem (FR-42 & NFR-02) -->
      <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-200 space-y-2 text-xs">
        <h4 class="font-bold text-blue-950 text-xs">Kebijakan Sistem & Privasi (FR-42)</h4>
        <div class="flex justify-between border-b border-blue-100 pb-1">
          <span class="text-slate-600">Batas Ukuran Foto:</span>
          <span class="font-mono font-bold text-slate-800">5 MB per foto</span>
        </div>
        <div class="flex justify-between border-b border-blue-100 pb-1">
          <span class="text-slate-600">Format yang Diizinkan:</span>
          <span class="font-mono text-slate-800">JPG, JPEG, PNG, WEBP</span>
        </div>
        <p class="text-[11px] text-emerald-800 pt-1">
          🔒 Perlindungan Privasi (PRD NFR-08): Tidak mewajibkan dan tidak menyimpan NIK warga pelapor.
        </p>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

