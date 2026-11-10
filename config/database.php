<?php
/**
 * Smart Complaint Kota Bandar Lampung
 * Database Configuration & Auto-Initialization (SQLite / MySQL)
 * Turunan dari PRD Smart Complaint v1.0
 */

define('DB_DRIVER', 'sqlite'); // 'sqlite' (otomatis zero-config) atau 'mysql'
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_complaint');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = DB_DRIVER;
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    $sqliteFile = $dataDir . '/smart_complaint.sqlite';

    // Coba MySQL jika dipilih, atau fallback ke SQLite
    if ($driver === 'mysql') {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            initDatabase($pdo);
            return $pdo;
        } catch (Exception $e) {
            $driver = 'sqlite';
        }
    }

    // Driver SQLite (Zero-Config, langsung jalan)
    $dsn = "sqlite:" . $sqliteFile;
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("PRAGMA foreign_keys = ON;");
    initDatabase($pdo);

    return $pdo;
}

function initDatabase($pdo) {
    // 1. Tabel users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'warga', -- 'warga', 'petugas', 'admin'
        status TEXT NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    // 2. Tabel departments (OPD Penangan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        code TEXT NOT NULL UNIQUE,
        description TEXT,
        is_active INTEGER DEFAULT 1
    );");

    // 3. Tabel categories (Kategori Laporan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        icon TEXT DEFAULT 'construction',
        description TEXT,
        default_department_id INTEGER,
        is_active INTEGER DEFAULT 1,
        FOREIGN KEY (default_department_id) REFERENCES departments(id)
    );");

    // 4. Tabel reports (Data Pengaduan Fasilitas Publik)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_number TEXT NOT NULL UNIQUE,
        citizen_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        department_id INTEGER,
        assigned_officer_id INTEGER,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        district TEXT NOT NULL, -- Kecamatan di Kota Bandar Lampung
        address TEXT,
        latitude REAL NOT NULL,
        longitude REAL NOT NULL,
        is_anonymous INTEGER DEFAULT 1,
        status TEXT NOT NULL DEFAULT 'submitted', 
        -- submitted, pending_verification, verified, assigned, in_progress, awaiting_citizen_confirmation, resolved, rejected, needs_revision, outside_area
        rejection_reason TEXT,
        revision_notes TEXT,
        photo_before TEXT,
        photo_after TEXT,
        action_notes TEXT,
        materials_used TEXT,
        citizen_rating INTEGER, -- 1-5 bintang
        citizen_feedback TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (citizen_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (department_id) REFERENCES departments(id),
        FOREIGN KEY (assigned_officer_id) REFERENCES users(id)
    );");

    // 5. Tabel report_history (Audit Trail Perubahan Status)
    $pdo->exec("CREATE TABLE IF NOT EXISTS report_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_id INTEGER NOT NULL,
        actor_id INTEGER,
        actor_name TEXT,
        previous_status TEXT,
        new_status TEXT NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES reports(id)
    );");

    // Seed Data Awal jika tabel users masih kosong
    seedInitialData($pdo);
}

function seedInitialData($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $row = $stmt->fetch();
    if ($row['cnt'] > 0) {
        return; // Sudah ada data
    }

    $passHash = password_hash('password123', PASSWORD_BCRYPT);

    // 1. Seed Users (Warga, Petugas, Admin)
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Budi Santoso', 'budi@warga.com', '081278901234', $passHash, 'warga']);
    $stmt->execute(['Hendra Wijaya', 'petugas@bandarlampung.go.id', '081398765432', $passHash, 'petugas']);
    $stmt->execute(['Ir. Rian Ardiansyah, M.T.', 'admin@bandarlampung.go.id', '081122334455', $passHash, 'admin']);

    // 2. Seed Departments (OPD)
    $deptStmt = $pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?)");
    $deptStmt->execute(['Dinas Bina Marga dan Bina Konstruksi', 'BMBK', 'Kewenangan jalan kota, trotoar, dan jembatan']);
    $deptStmt->execute(['Dinas Lingkungan Hidup', 'DLH', 'Kewenangan pengelolaan sampah liar, TPS, dan kebersihan']);
    $deptStmt->execute(['Dinas Perumahan dan Kawasan Permukiman', 'DISPERKIM', 'Kewenangan penerangan jalan umum (PJU) dan taman']);
    $deptStmt->execute(['Badan Penanggulangan Bencana Daerah', 'BPBD', 'Kewenangan tanggap darurat banjir dan pohon tumbang']);

    // 3. Seed Categories
    $catStmt = $pdo->prepare("INSERT INTO categories (name, icon, description, default_department_id) VALUES (?, ?, ?, ?)");
    $catStmt->execute(['Jalan Rusak', 'traffic', 'Aspal berlubang, jalan amblas, trotoar rusak', 1]);
    $catStmt->execute(['Lampu PJU Padam', 'light', 'Penerangan jalan umum mati atau tiang roboh', 3]);
    $catStmt->execute(['Sampah Liar', 'delete', 'Tumpukan sampah di luar TPS, bau menyengat', 2]);
    $catStmt->execute(['Banjir & Drainase', 'flood', 'Saluran air tersumbat, gorong-gorong meluap', 1]);
    $catStmt->execute(['Gangguan Lingkungan', 'park', 'Pohon rawan tumbang, fasilitas publik rusak', 4]);

    // 4. Seed Sample Reports
    $repStmt = $pdo->prepare("INSERT INTO reports 
        (ticket_number, citizen_id, category_id, department_id, assigned_officer_id, title, description, district, address, latitude, longitude, is_anonymous, status, photo_before, photo_after, action_notes, materials_used, citizen_rating, citizen_feedback, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Tiket 1: Menunggu Konfirmasi Warga (Kedaton)
    $repStmt->execute([
        'SC-2026-11001', 1, 1, 1, 2,
        'Jalan Berlubang Parah Membahayakan Pemotor',
        'Lubang berdiameter 80 cm dengan kedalaman 15 cm di lajur kiri dekat halte bus kampus Teknokrat.',
        'Kedaton', 'Jl. ZA Pagar Alam No. 9', -5.381200, 105.257400, 1,
        'awaiting_citizen_confirmation',
        'https://images.unsplash.com/photo-1515162816999-a0c47dc192f7?auto=format&fit=crop&w=600&q=80',
        'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=600&q=80',
        'Pembersihan pecahan aspal, pemadatan agregat dasar, dan penambalan dengan aspal penetrasi setebal 15 cm.',
        'Aspal Dingin (Cold Mix) 5 Sak + Pasir Silika',
        5, 'Pengerjaan cepat dan aspal rata kembali, terima kasih!',
        '2026-11-10 08:30:00'
    ]);

    // Tiket 2: Sedang Diproses Petugas (Tanjung Karang Pusat)
    $repStmt->execute([
        'SC-2026-11002', 1, 2, 3, 2,
        'Lampu PJU Padam Sepanjang 200 Meter',
        'Sepanjang 200 meter jalan gelap gulita karena lampu PJU mati dari malam kemarin.',
        'Tanjung Karang Pusat', 'Jl. Raden Intan No. 45', -5.412000, 105.257000, 1,
        'in_progress',
        'https://images.unsplash.com/photo-1509114397022-ed747cca3f65?auto=format&fit=crop&w=600&q=80',
        null, 'Petugas teknisi sedang melakukan pengecekan sekring panel dan penggantian bohlam LED 120W.',
        'Bohlam LED PJU 120W + Kabel NYY',
        null, null,
        '2026-11-10 09:15:00'
    ]);

    // Tiket 3: Menunggu Verifikasi Admin (Way Halim)
    $repStmt->execute([
        'SC-2026-11003', 1, 4, null, null,
        'Saluran Drainase Mampet Meluap Saat Hujan Deras',
        'Gorong-gorong tertutup tumpukan sampah dan lumpur sehingga air meluap setinggi 30 cm ke badan jalan.',
        'Way Halim', 'Jl. Arif Rahman Hakim', -5.388100, 105.278200, 1,
        'pending_verification',
        'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=600&q=80',
        null, null, null, null, null,
        '2026-11-10 10:20:00'
    ]);

    // Tiket 4: Selesai Tuntas (Sukarame)
    $repStmt->execute([
        'SC-2026-11004', 1, 3, 2, 2,
        'Tumpukan Sampah Liar di Pinggir Jalan Protokol',
        'Sampah menumpuk berbau menyengat di dekat lahan kosong.',
        'Sukarame', 'Jl. Ryacudu', -5.385000, 105.305000, 0,
        'resolved',
        'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80',
        'https://images.unsplash.com/photo-1611288875691-6453efd0a7a3?auto=format&fit=crop&w=600&q=80',
        'Pengangkutan sampah dengan truk armada DLH nomor 04 dan penyemprotan disinfektan.',
        'Truk Pengangkut Sampah DLH 1 Unit',
        5, 'Lokasi sudah bersih dari sampah dan bau.',
        '2026-11-09 14:00:00'
    ]);

    // 5. Seed History untuk Tiket 1 (Audit Trail)
    $histStmt = $pdo->prepare("INSERT INTO report_history (report_id, actor_name, previous_status, new_status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $histStmt->execute([1, 'Budi Santoso (Warga)', null, 'submitted', 'Laporan dikirimkan dengan koordinat GPS dan foto bukti.', '2026-11-10 08:30:00']);
    $histStmt->execute([1, 'Ir. Rian Ardiansyah (Admin)', 'submitted', 'verified', 'Laporan valid, lokasi di dalam wilayah jalan kota.', '2026-11-10 09:45:00']);
    $histStmt->execute([1, 'Ir. Rian Ardiansyah (Admin)', 'verified', 'assigned', 'Diteruskan ke Dinas BMBK dan ditugaskan ke Regu 02.', '2026-11-10 11:15:00']);
    $histStmt->execute([1, 'Hendra Wijaya (Petugas)', 'assigned', 'in_progress', 'Regu tiba di lokasi dan memulai pengerjaan penambalan.', '2026-11-10 13:00:00']);
    $histStmt->execute([1, 'Hendra Wijaya (Petugas)', 'in_progress', 'awaiting_citizen_confirmation', 'Pekerjaan selesai, BAP diunggah dan menunggu konfirmasi warga.', '2026-11-10 15:10:00']);
}

