<?php
/**
 * Smart Complaint - Utility & Helper Functions
 * Sesuai Spesifikasi PRD v1.0
 */

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDateIndo($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    return "$tgl $bln $thn, $jam WIB";
}

function getStatusBadge($status) {
    $map = [
        'submitted' => [
            'label' => 'Terkirim',
            'bg' => 'bg-amber-100 text-amber-800 border-amber-300',
            'icon' => 'hourglass_top'
        ],
        'pending_verification' => [
            'label' => 'Menunggu Verifikasi',
            'bg' => 'bg-amber-100 text-amber-800 border-amber-300',
            'icon' => 'hourglass_top'
        ],
        'verified' => [
            'label' => 'Terverifikasi',
            'bg' => 'bg-blue-100 text-blue-800 border-blue-300',
            'icon' => 'verified'
        ],
        'assigned' => [
            'label' => 'Ditugaskan ke Regu',
            'bg' => 'bg-blue-100 text-blue-800 border-blue-300',
            'icon' => 'engineering'
        ],
        'in_progress' => [
            'label' => 'Sedang Diproses',
            'bg' => 'bg-blue-100 text-blue-800 border-blue-300',
            'icon' => 'sync'
        ],
        'awaiting_citizen_confirmation' => [
            'label' => 'Menunggu Konfirmasi Warga',
            'bg' => 'bg-amber-100 text-amber-900 border-amber-400',
            'icon' => 'notifications_active'
        ],
        'resolved' => [
            'label' => 'Selesai Tuntas',
            'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'icon' => 'task_alt'
        ],
        'needs_revision' => [
            'label' => 'Perlu Revisi',
            'bg' => 'bg-orange-100 text-orange-800 border-orange-300',
            'icon' => 'edit_note'
        ],
        'rejected' => [
            'label' => 'Ditolak',
            'bg' => 'bg-red-100 text-red-800 border-red-300',
            'icon' => 'cancel'
        ],
        'outside_area' => [
            'label' => 'Di Luar Wilayah',
            'bg' => 'bg-slate-100 text-slate-800 border-slate-300',
            'icon' => 'wrong_location'
        ]
    ];

    $info = $map[$status] ?? [
        'label' => ucfirst($status),
        'bg' => 'bg-slate-100 text-slate-700 border-slate-300',
        'icon' => 'info'
    ];

    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ' . $info['bg'] . '">
        <span class="material-symbols-outlined text-xs">' . $info['icon'] . '</span>
        <span>' . $info['label'] . '</span>
    </span>';
}

function generateTicketNumber($pdo) {
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports");
    $next = $stmt->fetchColumn() + 1;
    return sprintf('SC-%s-%05d', $year, $next);
}

function addReportHistory($pdo, $reportId, $actorName, $prevStatus, $newStatus, $notes = '') {
    $stmt = $pdo->prepare("INSERT INTO report_history (report_id, actor_name, previous_status, new_status, notes, created_at) VALUES (?, ?, ?, ?, ?, datetime('now', 'localtime'))");
    $stmt->execute([$reportId, $actorName, $prevStatus, $newStatus, $notes]);
}

function getBandarLampungDistricts() {
    return [
        'Kedaton' => [-5.3812, 105.2574],
        'Rajabasa' => [-5.3620, 105.2380],
        'Tanjung Karang Pusat' => [-5.4120, 105.2570],
        'Tanjung Karang Timur' => [-5.4150, 105.2750],
        'Tanjung Karang Barat' => [-5.4050, 105.2420],
        'Way Halim' => [-5.3880, 105.2780],
        'Sukarame' => [-5.3850, 105.3050],
        'Sukabumi' => [-5.4050, 105.3120],
        'Kemiling' => [-5.3950, 105.2150],
        'Teluk Betung Selatan' => [-5.4520, 105.2610],
        'Teluk Betung Utara' => [-5.4380, 105.2600],
        'Teluk Betung Barat' => [-5.4450, 105.2400],
        'Teluk Betung Timur' => [-5.4650, 105.2850],
        'Enggal' => [-5.4250, 105.2620],
        'Bumi Waras' => [-5.4380, 105.2750],
        'Panjang' => [-5.4750, 105.3200],
        'Tanjung Senang' => [-5.3650, 105.2850],
        'Labuhan Ratu' => [-5.3720, 105.2480],
        'Langkapura' => [-5.3950, 105.2350],
        'Kedamaian' => [-5.4120, 105.2900]
    ];
}

