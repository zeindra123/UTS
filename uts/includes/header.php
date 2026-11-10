<?php
/**
 * Smart Complaint - Shared Header Component
 * PRD UI-LOGO-01/02 & Desain Antarmuka
 */

if (!isset($basePath)) {
    $basePath = '';
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = currentUser();
$pageTitle = $pageTitle ?? 'Smart Complaint – Sistem Pengaduan Masyarakat Kota Bandar Lampung';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($pageTitle) ?></title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  
  <!-- Material Symbols Outlined -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <!-- Leaflet Map CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'sc-primary': '#00163a',
            'sc-primary-container': '#0b2a5b',
            'sc-secondary': '#005cbd',
            'sc-secondary-container': '#4e92fe',
            'sc-secondary-fixed': '#d7e2ff',
            'sc-surface': '#f9f9ff',
            'sc-surface-low': '#f0f3ff',
            'sc-surface-container': '#e7eeff',
            'sc-surface-high': '#dee8ff',
            'sc-text-main': '#111c2d',
            'sc-text-variant': '#44474f',
            'sc-outline': '#c4c6d0',
            'sc-error': '#ba1a1a',
            'sc-error-container': '#ffdad6',
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            heading: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          }
        }
      }
    }
  </script>

  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      vertical-align: middle;
    }
    .material-symbols-outlined.fill-1 {
      font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .leaflet-pane { z-index: 10 !important; }
    .leaflet-top, .leaflet-bottom { z-index: 11 !important; }

    @media print {
      body * { visibility: hidden; }
      #print-area, #print-area * { visibility: visible; }
      #print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body class="bg-[#f9f9ff] text-[#111c2d] font-sans min-h-screen flex flex-col antialiased selection:bg-[#4e92fe] selection:text-white">

  <!-- ========================================== -->
  <!-- 0. TOP DEMO CONTROLLER (UNTUK EVALUASI UTS) -->
  <!-- ========================================== -->
  <aside aria-label="Demo Bar" class="no-print bg-slate-900 text-white text-xs px-3 py-2 border-b border-slate-700 sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400 text-slate-950 uppercase tracking-wider">
          LIVE CODING PPSI
        </span>
        <span class="font-semibold text-slate-200 hidden sm:inline">Smart Complaint (PHP + SQLite Dinamis):</span>
      </div>

      <!-- Quick Role Switcher -->
      <div class="flex items-center gap-1.5 text-xs">
        <span class="text-slate-400 text-[10px] hidden md:inline">Ganti Peran Demonstrasi:</span>
        <a href="<?= $basePath ?>login.php?quick=warga" class="px-2 py-0.5 rounded bg-blue-700 hover:bg-blue-600 text-white text-[11px] font-medium transition">Demo Warga</a>
        <a href="<?= $basePath ?>login.php?quick=petugas" class="px-2 py-0.5 rounded bg-amber-700 hover:bg-amber-600 text-white text-[11px] font-medium transition">Demo Petugas</a>
        <a href="<?= $basePath ?>login.php?quick=admin" class="px-2 py-0.5 rounded bg-emerald-700 hover:bg-emerald-600 text-white text-[11px] font-medium transition">Demo Admin</a>
      </div>
    </div>
  </aside>

  <!-- ========================================== -->
  <!-- BANNER PROTOTIPE AKADEMIK (PRD 1.3)        -->
  <!-- ========================================== -->
  <div class="no-print bg-amber-50 border-b border-amber-200 text-amber-900 px-4 py-2 text-xs text-center font-medium flex items-center justify-center gap-2">
    <span class="material-symbols-outlined text-amber-700 text-base">school</span>
    <span><strong>Prototipe Akademik – Bukan Kanal Resmi:</strong> Dibuat untuk pemenuhan tugas kuliah PPSI & demonstrasi simulasi proses penanganan aduan masyarakat Kota Bandar Lampung.</span>
  </div>

  <!-- ========================================== -->
  <!-- MAIN NAVBAR RESMI (PRD UI-LOGO-01/02)      -->
  <!-- ========================================== -->
  <header class="no-print bg-[#0b2a5b] text-white shadow-lg sticky top-[37px] z-40 border-b border-[#00163a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      
      <!-- Brand & Dua Logo Berdampingan (PRD UI-LOGO-01 & UI-LOGO-02) -->
      <a href="<?= $basePath ?>index.php" class="flex items-center gap-3 group focus:outline-none">
        <div class="flex items-center gap-2 bg-white/10 p-1.5 rounded-lg border border-white/15">
          <!-- Logo 1: Pemkot Bandar Lampung -->
          <img src="<?= $basePath ?>logo/logo_pemda.png" alt="Logo Pemda Kota Bandar Lampung" class="h-8 w-auto object-contain drop-shadow" onerror="this.src='<?= $basePath ?>logo/LOGO%20PEMDA%20BANDAR%20LAMPUNG.png'">

          <!-- Divider -->
          <div class="h-7 w-[1px] bg-white/30"></div>

          <!-- Logo 2: Universitas Teknokrat Indonesia (UTI) -->
          <img src="<?= $basePath ?>logo/logo_teknokrat.png" alt="Logo Universitas Teknokrat Indonesia" class="h-8 w-auto object-contain drop-shadow" onerror="this.src='<?= $basePath ?>logo/LOGO%20TEKNOKRAT.png'">
        </div>

        <div>
          <div class="flex items-center gap-1.5">
            <span class="font-heading font-extrabold text-base tracking-tight text-white group-hover:text-blue-200 transition">SMART COMPLAINT</span>
            <span class="px-1.5 py-0.2 text-[10px] bg-emerald-500/30 text-emerald-200 border border-emerald-400/40 rounded">PHP + SQLITE</span>
          </div>
          <p class="text-[11px] text-blue-200 leading-none">Kota Bandar Lampung • Riset UTI</p>
        </div>
      </a>

      <!-- Desktop Navigation Menu -->
      <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
        <a href="<?= $basePath ?>index.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-white transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">home</span> Beranda
        </a>
        <a href="<?= $basePath ?>lapor.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-blue-100 transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">campaign</span> Buat Laporan
        </a>
        <a href="<?= $basePath ?>lacak.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-blue-100 transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">search</span> Lacak Aduan
        </a>

        <?php if ($user && $user['role'] === 'warga'): ?>
        <a href="<?= $basePath ?>dashboard.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-blue-100 transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">account_circle</span> Dashboard Saya
        </a>
        <?php endif; ?>

        <?php if ($user && ($user['role'] === 'petugas' || $user['role'] === 'admin')): ?>
        <a href="<?= $basePath ?>petugas/index.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-amber-200 transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">engineering</span> Panel Petugas
        </a>
        <?php endif; ?>

        <?php if ($user && $user['role'] === 'admin'): ?>
        <a href="<?= $basePath ?>admin/index.php" class="px-3 py-2 rounded-lg hover:bg-white/10 text-emerald-200 transition flex items-center gap-1.5">
          <span class="material-symbols-outlined text-lg">admin_panel_settings</span> Pusat Kontrol Admin
        </a>
        <?php endif; ?>
      </nav>

      <!-- Auth Action / User Profile -->
      <div class="flex items-center gap-2">
        <?php if ($user): ?>
          <div class="flex items-center gap-2 bg-blue-900/60 border border-blue-400/40 rounded-full px-3 py-1 text-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-blue-100 font-medium"><?= sanitize($user['name']) ?> (<?= ucfirst($user['role']) ?>)</span>
          </div>
          <a href="<?= $basePath ?>logout.php" class="bg-red-700 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow transition flex items-center gap-1">
            <span class="material-symbols-outlined text-base">logout</span>
            <span>Keluar</span>
          </a>
        <?php else: ?>
          <a href="<?= $basePath ?>login.php" class="bg-[#005cbd] hover:bg-[#4e92fe] text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow transition flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">login</span>
            <span>Masuk</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <!-- Main Body Container -->
  <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 py-6">

