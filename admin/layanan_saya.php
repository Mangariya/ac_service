<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'teknisi'])) {
    header("Location: ../auth/login.php");
    exit;
}

$is_admin   = ($_SESSION['user']['role'] ?? '') === 'admin';
$is_teknisi = ($_SESSION['user']['role'] ?? '') === 'teknisi';
$user_id    = $_SESSION['user']['id'];

// Admin bisa lihat layanan teknisi manapun
$target_teknisi_id = $user_id;
if ($is_admin && !empty($_GET['teknisi_id'])) {
    $target_teknisi_id = intval($_GET['teknisi_id']);
}

// ─── Buat tabel jika belum ada ────────────────────────────────────────
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS layanan_teknisi (
            id          SERIAL PRIMARY KEY,
            teknisi_id  INTEGER NOT NULL,
            nama        VARCHAR(200) NOT NULL,
            harga       INTEGER NOT NULL DEFAULT 0,
            durasi      VARCHAR(100) DEFAULT '30-60 Menit',
            deskripsi   TEXT,
            keunggulan  TEXT,
            icon        VARCHAR(50) DEFAULT 'bi-tools',
            warna       VARCHAR(20) DEFAULT '#2563EB',
            status      VARCHAR(20) DEFAULT 'aktif',
            urutan      INTEGER DEFAULT 0,
            created_at  TIMESTAMP DEFAULT NOW(),
            updated_at  TIMESTAMP DEFAULT NOW()
        )
    ");
} catch (PDOException $e) {}

// ─── Daftar icon pilihan ──────────────────────────────────────────────
$icon_list = [
    'bi-wind'           => 'Angin (Cuci AC)',
    'bi-tools'          => 'Obeng (Perbaikan)',
    'bi-moisture'       => 'Freon',
    'bi-thermometer'    => 'Suhu',
    'bi-lightning'      => 'Listrik',
    'bi-gear-fill'      => 'Gear (Instalasi)',
    'bi-snow2'          => 'Salju (AC)',
    'bi-droplet-fill'   => 'Tetes',
    'bi-wrench'         => 'Kunci',
    'bi-box-seam'       => 'Bongkar Pasang',
    'bi-cpu'            => 'PCB/Elektronik',
    'bi-fan'            => 'Fan/Kipas',
];
$warna_list = [
    '#2563EB' => 'Biru',
    '#10B981' => 'Hijau',
    '#F59E0B' => 'Kuning',
    '#EF4444' => 'Merah',
    '#8B5CF6' => 'Ungu',
    '#EC4899' => 'Pink',
    '#14B8A6' => 'Teal',
    '#F97316' => 'Oranye',
    '#64748B' => 'Abu',
    '#059669' => 'Emerald',
];
$warna_bg = [
    '#2563EB' => '#EFF6FF',
    '#10B981' => '#ECFDF5',
    '#F59E0B' => '#FFFBEB',
    '#EF4444' => '#FEF2F2',
    '#8B5CF6' => '#F5F3FF',
    '#EC4899' => '#FDF2F8',
    '#14B8A6' => '#F0FDFA',
    '#F97316' => '#FFF7ED',
    '#64748B' => '#F8FAFC',
    '#059669' => '#ECFDF5',
];

$success = '';
$error   = '';

// ─── PROSES FORM ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi       = $_POST['aksi'] ?? '';
    $layanan_id = intval($_POST['layanan_id'] ?? 0);

    // Hanya admin atau teknisi pemilik yang boleh edit
    if ($layanan_id > 0) {
        $cek = $conn->prepare("SELECT teknisi_id FROM layanan_teknisi WHERE id = ?");
        $cek->execute([$layanan_id]);
        $owner = $cek->fetchColumn();
        if ($owner != $target_teknisi_id && !$is_admin) {
            $error = 'Akses ditolak.';
            goto skip_post;
        }
    }

    // ── Tambah / Edit ────────────────────────────────────────────────
    if (in_array($aksi, ['tambah', 'edit'])) {
        $nama       = trim($_POST['nama'] ?? '');
        $harga      = intval(preg_replace('/[^0-9]/', '', $_POST['harga'] ?? '0'));
        $durasi     = trim($_POST['durasi'] ?? '30-60 Menit');
        $deskripsi  = trim($_POST['deskripsi'] ?? '');
        $icon       = trim($_POST['icon'] ?? 'bi-tools');
        $warna      = trim($_POST['warna'] ?? '#2563EB');
        $status     = trim($_POST['status'] ?? 'aktif');

        // Keunggulan (multiline) → simpan sebagai JSON array
        $keunggulan_raw = trim($_POST['keunggulan'] ?? '');
        $keunggulan_lines = array_filter(array_map('trim', explode("\n", $keunggulan_raw)));
        $keunggulan_json  = json_encode(array_values($keunggulan_lines));

        if (empty($nama)) { $error = 'Nama layanan wajib diisi.'; goto skip_post; }
        if ($harga < 0)   { $error = 'Harga tidak boleh negatif.'; goto skip_post; }

        if ($aksi === 'tambah') {
            // Cek batas layanan per teknisi (max 15)
            $cnt = $conn->prepare("SELECT COUNT(*) FROM layanan_teknisi WHERE teknisi_id = ?");
            $cnt->execute([$target_teknisi_id]);
            if ($cnt->fetchColumn() >= 15) {
                $error = 'Maksimal 15 layanan per teknisi.';
                goto skip_post;
            }
            $stmtI = $conn->prepare("
                INSERT INTO layanan_teknisi
                    (teknisi_id, nama, harga, durasi, deskripsi, keunggulan, icon, warna, status, urutan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(urutan),0)+1 FROM layanan_teknisi WHERE teknisi_id = ?))
            ");
            $stmtI->execute([
                $target_teknisi_id, $nama, $harga, $durasi, $deskripsi,
                $keunggulan_json, $icon, $warna, $status, $target_teknisi_id
            ]);
            $success = "Layanan <strong>$nama</strong> berhasil ditambahkan.";
        } else {
            $stmtU = $conn->prepare("
                UPDATE layanan_teknisi
                SET nama=?, harga=?, durasi=?, deskripsi=?, keunggulan=?, icon=?, warna=?, status=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmtU->execute([$nama, $harga, $durasi, $deskripsi, $keunggulan_json, $icon, $warna, $status, $layanan_id]);
            $success = "Layanan <strong>$nama</strong> berhasil diperbarui.";
        }
    }

    // ── Hapus ─────────────────────────────────────────────────────────
    if ($aksi === 'hapus') {
        $conn->prepare("DELETE FROM layanan_teknisi WHERE id = ?")->execute([$layanan_id]);
        $success = 'Layanan berhasil dihapus.';
    }

    // ── Toggle Status ─────────────────────────────────────────────────
    if ($aksi === 'toggle') {
        $cur = $conn->prepare("SELECT status FROM layanan_teknisi WHERE id = ?");
        $cur->execute([$layanan_id]);
        $cur_status = $cur->fetchColumn();
        $new_status = ($cur_status === 'aktif') ? 'nonaktif' : 'aktif';
        $conn->prepare("UPDATE layanan_teknisi SET status=?, updated_at=NOW() WHERE id=?")->execute([$new_status, $layanan_id]);
        $success = 'Status layanan berhasil diubah.';
    }

    // ── Pindah urutan ─────────────────────────────────────────────────
    if ($aksi === 'urut_up' || $aksi === 'urut_down') {
        $dir = ($aksi === 'urut_up') ? -1 : 1;
        $cur = $conn->prepare("SELECT urutan FROM layanan_teknisi WHERE id=?");
        $cur->execute([$layanan_id]);
        $cur_urut = (int) $cur->fetchColumn();
        $swap_urut = $cur_urut + $dir;
        $swap = $conn->prepare("SELECT id FROM layanan_teknisi WHERE teknisi_id=? AND urutan=?");
        $swap->execute([$target_teknisi_id, $swap_urut]);
        $swap_id = $swap->fetchColumn();
        if ($swap_id) {
            $conn->prepare("UPDATE layanan_teknisi SET urutan=? WHERE id=?")->execute([$swap_urut, $layanan_id]);
            $conn->prepare("UPDATE layanan_teknisi SET urutan=? WHERE id=?")->execute([$cur_urut, $swap_id]);
        }
    }
}
skip_post:

// ─── Ambil daftar layanan ─────────────────────────────────────────────
$stmtL = $conn->prepare("SELECT * FROM layanan_teknisi WHERE teknisi_id=? ORDER BY urutan ASC, id ASC");
$stmtL->execute([$target_teknisi_id]);
$layanan_list = $stmtL->fetchAll(PDO::FETCH_ASSOC);

// ─── Ambil info teknisi (untuk judul) ────────────────────────────────
$stmtT = $conn->prepare("SELECT nama, spesialisasi FROM users WHERE id=?");
$stmtT->execute([$target_teknisi_id]);
$info_teknisi = $stmtT->fetch(PDO::FETCH_ASSOC);

// ─── Admin: daftar teknisi untuk selector ────────────────────────────
$daftar_teknisi = [];
if ($is_admin) {
    $stmtDT = $conn->prepare("SELECT id, nama, spesialisasi FROM users WHERE role='teknisi' ORDER BY nama ASC");
    $stmtDT->execute();
    $daftar_teknisi = $stmtDT->fetchAll(PDO::FETCH_ASSOC);
}

// ─── Pending calon untuk badge sidebar ────────────────────────────────
$pending_calon = 0;
if ($is_admin) {
    $cek_tbl = $conn->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='pendaftaran_teknisi')");
    $cek_tbl->execute();
    if ((bool)$cek_tbl->fetchColumn()) {
        $cPend = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_teknisi WHERE status='menunggu'");
        $cPend->execute();
        $pending_calon = (int)$cPend->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Layanan Saya - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body { background:#DFF0FA; color:#1E293B; }

        /* Sidebar */
        .sidebar { height:100vh; width:260px; position:fixed; top:0; left:0; background:white; border-right:1px solid #E2E8F0; padding-top:20px; overflow-y:auto; }
        .sidebar-brand { padding:0 30px 30px; font-size:22px; font-weight:800; color:#1E293B; display:flex; align-items:center; gap:10px; }
        .sidebar-menu { list-style:none; padding:0; }
        .sidebar-menu li { margin-bottom:5px; }
        .sidebar-menu a { padding:12px 30px; display:flex; align-items:center; gap:15px; color:#64748B; text-decoration:none; font-weight:500; transition:0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background:#EFF6FF; color:#2563EB; border-right:4px solid #2563EB; }
        .logout-box { position:absolute; bottom:30px; width:100%; }
        .badge-notif-sb { background:#EF4444; color:white; border-radius:50px; padding:1px 7px; font-size:10px; font-weight:800; margin-left:auto; }

        /* Main */
        .main-content { margin-left:260px; min-height:100vh; }
        .topbar { height:90px; background:white; box-shadow:0 3px 10px rgba(15,23,42,0.18); display:flex; align-items:center; justify-content:space-between; padding:0 38px; }
        .top-title h3 { font-weight:800; margin-bottom:3px; }
        .top-title p { margin:0; color:#64748B; font-size:14px; }
        .admin-profile { display:flex; align-items:center; gap:12px; font-weight:700; }
        .profile-icon { width:40px; height:40px; border-radius:50%; background:#EAF4FF; color:#2563EB; display:flex; align-items:center; justify-content:center; font-size:22px; }
        .content-area { padding:32px 44px; }

        /* Alert */
        .alert-s { background:#D1FAE5; border:1.5px solid #6EE7B7; border-radius:12px; padding:13px 20px; font-size:14px; font-weight:600; color:#065F46; margin-bottom:20px; }
        .alert-e { background:#FEE2E2; border:1.5px solid #FCA5A5; border-radius:12px; padding:13px 20px; font-size:14px; font-weight:600; color:#991B1B; margin-bottom:20px; }

        /* Header bar */
        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:16px; flex-wrap:wrap; }
        .page-header-left h4 { font-size:20px; font-weight:800; color:#111827; margin:0; }
        .page-header-left p { font-size:13px; color:#64748B; margin:4px 0 0; }

        /* Add button */
        .btn-add { background:linear-gradient(135deg,#2563EB,#3B82F6); color:white; border:none; border-radius:12px; padding:12px 24px; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px; cursor:pointer; transition:0.2s; box-shadow:0 4px 14px rgba(37,99,235,0.3); }
        .btn-add:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(37,99,235,0.4); }

        /* Teknisi Selector */
        .teknisi-selector { background:white; border-radius:14px; padding:16px 20px; margin-bottom:24px; box-shadow:0 4px 14px rgba(15,23,42,0.08); display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .teknisi-selector label { font-size:13px; font-weight:700; color:#374151; }
        .teknisi-selector select { border:1.5px solid #E2E8F0; border-radius:10px; padding:8px 14px; font-size:13px; font-weight:600; color:#1E293B; background:#FAFAFA; transition:0.2s; }
        .teknisi-selector select:focus { border-color:#2563EB; outline:none; }

        /* Empty state */
        .empty-state { text-align:center; padding:60px 20px; background:white; border-radius:16px; box-shadow:0 4px 14px rgba(15,23,42,0.08); }
        .empty-icon { font-size:56px; color:#CBD5E1; margin-bottom:16px; display:block; }

        /* Cards Grid */
        .layanan-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:20px; }

        /* Layanan Card */
        .layanan-card {
            background:white;
            border-radius:18px;
            box-shadow:0 4px 14px rgba(15,23,42,0.08);
            overflow:hidden;
            transition:0.25s;
            border:2px solid transparent;
        }
        .layanan-card:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(15,23,42,0.14); }
        .layanan-card.nonaktif { opacity:0.65; border-color:#E2E8F0; }

        .card-top { padding:18px 20px 14px; display:flex; align-items:flex-start; gap:14px; border-bottom:1px solid #F1F5F9; }
        .svc-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .svc-name { font-size:16px; font-weight:800; color:#111827; margin-bottom:3px; }
        .svc-durasi { font-size:12px; color:#6B7280; font-weight:600; display:flex; align-items:center; gap:4px; }
        .svc-price { font-size:20px; font-weight:800; color:#2563EB; margin-left:auto; white-space:nowrap; }

        .card-body-area { padding:14px 20px; }
        .svc-desc { font-size:13px; color:#4B5563; line-height:1.6; margin-bottom:12px; }
        .svc-keunggulan { display:flex; flex-wrap:wrap; gap:6px; }
        .keung-tag { background:#F0F9FF; color:#0369A1; font-size:11px; font-weight:600; padding:4px 8px; border-radius:6px; display:flex; align-items:center; gap:3px; }
        .keung-tag i { font-size:10px; }

        .card-status-bar { display:flex; align-items:center; gap:8px; margin-top:14px; padding-top:14px; border-top:1px dashed #E5E7EB; }
        .pill-aktif { background:#D1FAE5; color:#059669; border-radius:50px; padding:3px 12px; font-size:11px; font-weight:800; }
        .pill-nonaktif { background:#FEE2E2; color:#DC2626; border-radius:50px; padding:3px 12px; font-size:11px; font-weight:800; }

        .card-actions { padding:14px 20px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
        .btn-edit { background:#EFF6FF; color:#2563EB; border:none; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:700; cursor:pointer; transition:0.2s; }
        .btn-edit:hover { background:#DBEAFE; }
        .btn-toggle { background:#FFFBEB; color:#D97706; border:none; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:700; cursor:pointer; transition:0.2s; }
        .btn-toggle:hover { background:#FEF3C7; }
        .btn-del { background:#FEE2E2; color:#DC2626; border:none; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:700; cursor:pointer; transition:0.2s; }
        .btn-del:hover { background:#FECACA; }
        .btn-urut { background:#F1F5F9; color:#64748B; border:none; border-radius:8px; padding:7px 10px; font-size:12px; font-weight:700; cursor:pointer; transition:0.2s; }
        .btn-urut:hover { background:#E2E8F0; }

        /* Urutan indicator */
        .urutan-badge { background:#F1F5F9; color:#94A3B8; border-radius:6px; padding:2px 8px; font-size:11px; font-weight:700; }

        /* Info box */
        .info-box { background:linear-gradient(135deg,#EFF6FF,#DBEAFE); border:1.5px solid #93C5FD; border-radius:14px; padding:16px 20px; margin-bottom:24px; display:flex; gap:14px; align-items:flex-start; }
        .info-box i { color:#2563EB; font-size:22px; flex-shrink:0; margin-top:2px; }
        .info-box p { font-size:13px; color:#1E40AF; font-weight:600; margin:0; line-height:1.6; }

        /* Modal */
        .modal-content { border:0; border-radius:20px; }
        .modal-header { border-bottom:1px solid #F1F5F9; padding:20px 24px; }
        .modal-body { padding:24px; }
        .modal-footer { border-top:1px solid #F1F5F9; padding:16px 24px; }
        .form-label { font-size:13px; font-weight:700; color:#374151; margin-bottom:6px; }
        .form-label .req { color:#EF4444; }
        .form-control, .form-select { border-radius:12px; padding:11px 15px; border:1.5px solid #E5E7EB; font-weight:500; font-size:14px; transition:0.2s; background:#FAFAFA; }
        .form-control:focus, .form-select:focus { border-color:#2563EB; box-shadow:0 0 0 3px rgba(37,99,235,0.1); background:white; }
        textarea.form-control { resize:vertical; min-height:90px; }

        /* Icon selector */
        .icon-grid { display:grid; grid-template-columns:repeat(6, 1fr); gap:8px; margin-top:8px; }
        .icon-opt { border:2px solid #E5E7EB; border-radius:10px; padding:10px 8px; text-align:center; cursor:pointer; transition:0.2s; font-size:20px; }
        .icon-opt:hover { border-color:#93C5FD; background:#EFF6FF; }
        .icon-opt.active { border-color:#2563EB; background:#EFF6FF; }
        .icon-opt-label { font-size:10px; color:#6B7280; margin-top:3px; display:block; }

        /* Warna selector */
        .warna-grid { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
        .warna-opt { width:36px; height:36px; border-radius:50%; border:3px solid transparent; cursor:pointer; transition:0.2s; }
        .warna-opt.active { border-color: white; box-shadow: 0 0 0 3px currentColor; }

        /* Harga input */
        .harga-wrapper { position:relative; }
        .harga-prefix { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-weight:700; color:#64748B; font-size:14px; }
        .harga-input { padding-left:48px !important; }

        /* Preview Card */
        .preview-card { border:2px solid #E5E7EB; border-radius:14px; overflow:hidden; }
        .preview-card-header { padding:14px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #F1F5F9; }
        .preview-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .preview-name { font-size:14px; font-weight:700; }
        .preview-durasi { font-size:12px; color:#6B7280; }
        .preview-price { font-size:17px; font-weight:800; color:#2563EB; margin-left:auto; }
        .preview-card-body { padding:12px 14px; font-size:12px; color:#4B5563; }

        /* Import preset */
        .preset-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px; }
        .preset-btn { border:2px solid #E5E7EB; border-radius:12px; padding:10px; cursor:pointer; text-align:left; transition:0.2s; background:white; }
        .preset-btn:hover { border-color:#93C5FD; background:#EFF6FF; }
        .preset-btn .p-name { font-size:13px; font-weight:700; color:#111827; }
        .preset-btn .p-price { font-size:12px; color:#2563EB; font-weight:600; }

        @media(max-width:992px) {
            .sidebar { position:static; width:100%; height:auto; }
            .logout-box { position:static; margin-top:20px; }
            .main-content { margin-left:0; }
            .content-area { padding:20px; }
            .layanan-grid { grid-template-columns:1fr; }
            .topbar { height:auto; padding:20px; flex-direction:column; align-items:flex-start; gap:14px; }
        }
    </style>
</head>
<body>

<!-- ── SIDEBAR ────────────────────────────────────────── -->
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-snow2 text-primary" style="font-size:28px;"></i>
        AC SERVICE
    </div>
    <ul class="sidebar-menu">
        <?php if ($is_admin): ?>
            <li><a href="index.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <?php endif; ?>
        <li><a href="manajemen_layanan.php"><i class="bi bi-briefcase"></i> Manajemen Layanan</a></li>
        <li><a href="layanan_saya.php" class="active"><i class="bi bi-bag-heart-fill"></i> Layanan Saya</a></li>
        <li><a href="reservasi.php"><i class="bi bi-calendar-check"></i> Reservasi</a></li>
        <li><a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a></li>
        <?php if ($is_admin): ?>
            <li><a href="teknisi.php"><i class="bi bi-person-plus"></i> Daftar Teknisi</a></li>
            <li>
                <a href="calon_teknisi.php">
                    <i class="bi bi-person-check"></i> Calon Teknisi
                    <?php if ($pending_calon > 0): ?>
                        <span class="badge-notif-sb"><?= $pending_calon ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>
        <li><a href="bantuan.php"><i class="bi bi-question-circle"></i> Bantuan</a></li>
    </ul>
    <div class="logout-box">
        <a href="../auth/logout.php" class="text-danger fw-bold" style="padding-left:30px; text-decoration:none;">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>
    </div>
</div>

<!-- ── MAIN CONTENT ───────────────────────────────────── -->
<div class="main-content">
    <div class="topbar">
        <div class="top-left d-flex align-items-center gap-3">
            <i class="bi bi-list" style="font-size:30px; color:#475569;"></i>
            <div class="top-title">
                <h3>Layanan Saya</h3>
                <p>Kelola layanan yang Anda tawarkan kepada pelanggan.</p>
            </div>
        </div>
        <div class="admin-profile">
            <div class="profile-icon"><i class="bi bi-person-fill"></i></div>
            <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Teknisi') ?></span>
            <i class="bi bi-chevron-down text-muted"></i>
        </div>
    </div>

    <div class="content-area">

        <?php if (!empty($success)): ?>
            <div class="alert-s"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-e"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Admin: Pilih teknisi -->
        <?php if ($is_admin && count($daftar_teknisi) > 0): ?>
        <div class="teknisi-selector">
            <i class="bi bi-person-gear text-primary" style="font-size:20px;"></i>
            <label>Lihat layanan teknisi:</label>
            <select onchange="window.location.href='layanan_saya.php?teknisi_id='+this.value">
                <?php foreach ($daftar_teknisi as $dt): ?>
                    <option value="<?= $dt['id'] ?>" <?= $target_teknisi_id == $dt['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dt['nama']) ?> — <?= htmlspecialchars($dt['spesialisasi'] ?? 'Teknisi AC') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Info box -->
        <div class="info-box">
            <i class="bi bi-lightbulb-fill"></i>
            <p>
                Layanan yang Anda tambahkan di sini akan <strong>tampil langsung di profil Anda</strong> ketika pelanggan memilih teknisi.
                Anda bisa menambahkan layanan baru, mengatur harga, durasi, dan deskripsi keunggulan layanan.
                Layanan yang dinonaktifkan tidak akan tampil ke pelanggan.
            </p>
        </div>

        <!-- Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h4>
                    <i class="bi bi-bag-heart-fill text-primary me-2"></i>
                    Layanan <?= htmlspecialchars($info_teknisi['nama'] ?? '') ?>
                </h4>
                <p><?= count($layanan_list) ?> layanan aktif dari maks. 15 layanan</p>
            </div>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-circle-fill"></i> Tambah Layanan
            </button>
        </div>

        <!-- Grid Layanan -->
        <?php if (empty($layanan_list)): ?>
            <div class="empty-state">
                <i class="bi bi-bag-plus empty-icon"></i>
                <h5 class="fw-bold mb-2">Belum ada layanan</h5>
                <p class="text-muted mb-4" style="font-size:14px;">Tambahkan layanan yang Anda tawarkan agar pelanggan bisa memesan.</p>
                <button class="btn-add mx-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Layanan Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="layanan-grid">
                <?php foreach ($layanan_list as $idx => $lyr): ?>
                    <?php
                        $keunggulan_arr = json_decode($lyr['keunggulan'] ?? '[]', true) ?: [];
                        $bg_color = $warna_bg[$lyr['warna']] ?? '#EFF6FF';
                    ?>
                    <div class="layanan-card <?= $lyr['status'] === 'nonaktif' ? 'nonaktif' : '' ?>">
                        <div class="card-top">
                            <div class="svc-icon" style="background:<?= $bg_color ?>; color:<?= htmlspecialchars($lyr['warna']) ?>;">
                                <i class="bi <?= htmlspecialchars($lyr['icon']) ?>"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div class="svc-name"><?= htmlspecialchars($lyr['nama']) ?></div>
                                <div class="svc-durasi"><i class="bi bi-clock"></i> <?= htmlspecialchars($lyr['durasi']) ?></div>
                                <div class="card-status-bar">
                                    <?php if ($lyr['status'] === 'aktif'): ?>
                                        <span class="pill-aktif"><i class="bi bi-circle-fill me-1" style="font-size:6px;"></i>Aktif</span>
                                    <?php else: ?>
                                        <span class="pill-nonaktif"><i class="bi bi-circle me-1" style="font-size:6px;"></i>Nonaktif</span>
                                    <?php endif; ?>
                                    <span class="urutan-badge ms-auto">#<?= $lyr['urutan'] ?: $idx+1 ?></span>
                                </div>
                            </div>
                            <div class="svc-price">Rp <?= number_format($lyr['harga'], 0, ',', '.') ?></div>
                        </div>

                        <div class="card-body-area">
                            <?php if (!empty($lyr['deskripsi'])): ?>
                                <p class="svc-desc"><?= htmlspecialchars($lyr['deskripsi']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($keunggulan_arr)): ?>
                                <div class="svc-keunggulan">
                                    <?php foreach (array_slice($keunggulan_arr, 0, 4) as $k): ?>
                                        <span class="keung-tag"><i class="bi bi-check2"></i><?= htmlspecialchars($k) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($keunggulan_arr) > 4): ?>
                                        <span class="keung-tag" style="background:#F8FAFC; color:#94A3B8;">+<?= count($keunggulan_arr)-4 ?> lainnya</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <!-- Urutan -->
                            <?php if ($idx > 0): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="urut_up">
                                <input type="hidden" name="layanan_id" value="<?= $lyr['id'] ?>">
                                <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>
                                <button type="submit" class="btn-urut" title="Pindah ke atas"><i class="bi bi-arrow-up"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($idx < count($layanan_list)-1): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="urut_down">
                                <input type="hidden" name="layanan_id" value="<?= $lyr['id'] ?>">
                                <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>
                                <button type="submit" class="btn-urut" title="Pindah ke bawah"><i class="bi bi-arrow-down"></i></button>
                            </form>
                            <?php endif; ?>

                            <!-- Edit -->
                            <button class="btn-edit"
                                data-bs-toggle="modal" data-bs-target="#modalEdit"
                                data-id="<?= $lyr['id'] ?>"
                                data-nama="<?= htmlspecialchars($lyr['nama'], ENT_QUOTES) ?>"
                                data-harga="<?= $lyr['harga'] ?>"
                                data-durasi="<?= htmlspecialchars($lyr['durasi'], ENT_QUOTES) ?>"
                                data-deskripsi="<?= htmlspecialchars($lyr['deskripsi'] ?? '', ENT_QUOTES) ?>"
                                data-keunggulan="<?= htmlspecialchars(implode("\n", $keunggulan_arr), ENT_QUOTES) ?>"
                                data-icon="<?= htmlspecialchars($lyr['icon'], ENT_QUOTES) ?>"
                                data-warna="<?= htmlspecialchars($lyr['warna'], ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($lyr['status'], ENT_QUOTES) ?>"
                            >
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            <!-- Toggle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="toggle">
                                <input type="hidden" name="layanan_id" value="<?= $lyr['id'] ?>">
                                <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>
                                <button type="submit" class="btn-toggle">
                                    <i class="bi bi-<?= $lyr['status'] === 'aktif' ? 'eye-slash' : 'eye' ?>"></i>
                                    <?= $lyr['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>

                            <!-- Hapus -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus layanan ini?')">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="layanan_id" value="<?= $lyr['id'] ?>">
                                <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>
                                <button type="submit" class="btn-del"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL: TAMBAH LAYANAN
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" onsubmit="collectKP('new')">
            <input type="hidden" name="aksi" value="tambah">
            <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Layanan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Preset pilihan cepat -->
                <div class="mb-4">
                    <label class="form-label mb-2">🚀 Pilih dari Template Cepat (opsional)</label>
                    <div class="preset-grid" id="presetGrid">
                        <?php
                        $presets = [
                            ['nama'=>'Cuci AC',         'harga'=>75000,  'icon'=>'bi-wind',        'warna'=>'#2563EB', 'durasi'=>'45-60 Menit', 'deskripsi'=>'Pembersihan menyeluruh unit indoor & outdoor. Menghilangkan debu, jamur, dan kotoran.', 'keunggulan'=>"Cuci filter udara\nSemprot kondensor & evaporator\nCek freon (tidak termasuk isi)\nUji performa dingin"],
                            ['nama'=>'Perbaikan AC',    'harga'=>150000, 'icon'=>'bi-tools',       'warna'=>'#F59E0B', 'durasi'=>'1-3 Jam',     'deskripsi'=>'Diagnosa dan perbaikan masalah mekanis & elektrikal.', 'keunggulan'=>"Diagnosa kerusakan\nPerbaikan sistem kelistrikan\nPenggantian komponen (spare part belum termasuk)\nTest fungsionalitas"],
                            ['nama'=>'Isi Freon',       'harga'=>200000, 'icon'=>'bi-moisture',    'warna'=>'#10B981', 'durasi'=>'30-45 Menit', 'deskripsi'=>'Pengisian ulang refrigeran untuk mengembalikan performa dingin.', 'keunggulan'=>"Cek tekanan freon\nPengisian sesuai kapasitas AC\nCek kebocoran pipa\nGaransi dingin 1 bulan"],
                            ['nama'=>'Bongkar Pasang',  'harga'=>250000, 'icon'=>'bi-box-seam',    'warna'=>'#8B5CF6', 'durasi'=>'2-3 Jam',     'deskripsi'=>'Bongkar dan pasang unit AC indoor maupun outdoor.', 'keunggulan'=>"Bongkar unit lama\nPasang unit baru\nSetting remote\nUji coba dingin"],
                            ['nama'=>'Cuci Steam',      'harga'=>120000, 'icon'=>'bi-droplet-fill','warna'=>'#14B8A6', 'durasi'=>'60-90 Menit', 'deskripsi'=>'Cuci AC menggunakan uap panas (steam cleaning) untuk hasil lebih bersih.', 'keunggulan'=>"Steam evaporator\nCuci filter\nBasmi bakteri & jamur\nAC lebih segar dan dingin"],
                            ['nama'=>'Cek & Servis',    'harga'=>100000, 'icon'=>'bi-thermometer', 'warna'=>'#F97316', 'durasi'=>'45 Menit',    'deskripsi'=>'Pemeriksaan kondisi AC secara menyeluruh dan servis ringan.', 'keunggulan'=>"Cek seluruh komponen\nBersihkan filter\nCek tekanan freon\nLaporan kondisi AC"],
                        ];
                        foreach ($presets as $idx => $p): ?>
                            <button type="button" class="preset-btn" onclick="applyPreset(<?= $idx ?>)">
                                <div class="p-name"><i class="bi <?= $p['icon'] ?> me-1"></i><?= $p['nama'] ?></div>
                                <div class="p-price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <hr style="border-color:#E5E7EB; margin:0 0 18px;">
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama Layanan <span class="req">*</span></label>
                        <input type="text" name="nama" id="new_nama" class="form-control" placeholder="Contoh: Cuci AC Split 1 PK" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga (Rp) <span class="req">*</span></label>
                        <div class="harga-wrapper">
                            <span class="harga-prefix">Rp</span>
                            <input type="text" name="harga" id="new_harga" class="form-control harga-input" placeholder="75000" oninput="formatHarga(this)" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estimasi Durasi</label>
                        <select name="durasi" id="new_durasi" class="form-select">
                            <option value="15-30 Menit">15-30 Menit</option>
                            <option value="30-45 Menit">30-45 Menit</option>
                            <option value="45-60 Menit" selected>45-60 Menit</option>
                            <option value="60-90 Menit">60-90 Menit</option>
                            <option value="1-2 Jam">1-2 Jam</option>
                            <option value="1-3 Jam">1-3 Jam</option>
                            <option value="2-4 Jam">2-4 Jam</option>
                            <option value="Sehari Penuh">Sehari Penuh</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deskripsi Layanan</label>
                        <textarea name="deskripsi" id="new_deskripsi" class="form-control" rows="3" placeholder="Jelaskan secara singkat apa yang termasuk dalam layanan ini..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keunggulan / Yang Termasuk <span class="text-muted fw-normal">(1 baris = 1 poin)</span></label>
                        <textarea name="keunggulan" id="new_keunggulan" class="form-control" rows="4" placeholder="Cuci filter udara&#10;Semprot kondensor&#10;Cek freon&#10;Garansi 1 bulan"></textarea>
                        <div class="form-text">Setiap baris akan menjadi satu tag keunggulan yang ditampilkan ke pelanggan.</div>
                    </div>

                    <!-- Icon Selector -->
                    <div class="col-12">
                        <label class="form-label">Pilih Ikon</label>
                        <div class="icon-grid" id="iconGrid_new">
                            <?php foreach ($icon_list as $ico => $label): ?>
                                <div class="icon-opt <?= $ico === 'bi-tools' ? 'active' : '' ?>"
                                     onclick="selectIcon('new', '<?= $ico ?>')"
                                     title="<?= htmlspecialchars($label) ?>">
                                    <i class="bi <?= $ico ?>"></i>
                                    <span class="icon-opt-label"><?= htmlspecialchars($label) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icon" id="new_icon" value="bi-tools">
                    </div>

                    <!-- Warna Selector -->
                    <div class="col-12">
                        <label class="form-label">Pilih Warna Tema</label>
                        <div class="warna-grid" id="warnaGrid_new">
                            <?php foreach ($warna_list as $hex => $label): ?>
                                <div class="warna-opt <?= $hex === '#2563EB' ? 'active' : '' ?>"
                                     style="background:<?= $hex ?>;"
                                     onclick="selectWarna('new', '<?= $hex ?>')"
                                     title="<?= htmlspecialchars($label) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="warna" id="new_warna" value="#2563EB">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-plus-circle me-1"></i> Simpan Layanan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL: EDIT LAYANAN
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" onsubmit="collectKP('edit')">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="layanan_id" id="edit_id">
            <?php if ($is_admin): ?><input type="hidden" name="teknisi_id" value="<?= $target_teknisi_id ?>"><?php endif; ?>

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-fill text-warning me-2"></i>Edit Layanan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama Layanan <span class="req">*</span></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga (Rp) <span class="req">*</span></label>
                        <div class="harga-wrapper">
                            <span class="harga-prefix">Rp</span>
                            <input type="text" name="harga" id="edit_harga" class="form-control harga-input" oninput="formatHarga(this)" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estimasi Durasi</label>
                        <select name="durasi" id="edit_durasi" class="form-select">
                            <option value="15-30 Menit">15-30 Menit</option>
                            <option value="30-45 Menit">30-45 Menit</option>
                            <option value="45-60 Menit">45-60 Menit</option>
                            <option value="60-90 Menit">60-90 Menit</option>
                            <option value="1-2 Jam">1-2 Jam</option>
                            <option value="1-3 Jam">1-3 Jam</option>
                            <option value="2-4 Jam">2-4 Jam</option>
                            <option value="Sehari Penuh">Sehari Penuh</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deskripsi Layanan</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keunggulan / Yang Termasuk <span class="text-muted fw-normal">(1 baris = 1 poin)</span></label>
                        <textarea name="keunggulan" id="edit_keunggulan" class="form-control" rows="4"></textarea>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="aktif">Aktif (Tampil ke pelanggan)</option>
                            <option value="nonaktif">Nonaktif (Disembunyikan)</option>
                        </select>
                    </div>

                    <!-- Icon -->
                    <div class="col-12">
                        <label class="form-label">Pilih Ikon</label>
                        <div class="icon-grid" id="iconGrid_edit">
                            <?php foreach ($icon_list as $ico => $label): ?>
                                <div class="icon-opt"
                                     onclick="selectIcon('edit', '<?= $ico ?>')"
                                     data-ico="<?= $ico ?>"
                                     title="<?= htmlspecialchars($label) ?>">
                                    <i class="bi <?= $ico ?>"></i>
                                    <span class="icon-opt-label"><?= htmlspecialchars($label) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icon" id="edit_icon" value="bi-tools">
                    </div>

                    <!-- Warna -->
                    <div class="col-12">
                        <label class="form-label">Pilih Warna Tema</label>
                        <div class="warna-grid" id="warnaGrid_edit">
                            <?php foreach ($warna_list as $hex => $label): ?>
                                <div class="warna-opt"
                                     style="background:<?= $hex ?>;"
                                     onclick="selectWarna('edit', '<?= $hex ?>')"
                                     data-hex="<?= $hex ?>"
                                     title="<?= htmlspecialchars($label) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="warna" id="edit_warna" value="#2563EB">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold px-4 text-white">
                    <i class="bi bi-pencil me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── Data preset ─────────────────────────────────────────────
const presets = <?= json_encode($presets, JSON_UNESCAPED_UNICODE) ?>;

function applyPreset(idx) {
    const p = presets[idx];
    document.getElementById('new_nama').value      = p.nama;
    document.getElementById('new_harga').value     = p.harga.toLocaleString('id-ID');
    document.getElementById('new_deskripsi').value = p.deskripsi;
    document.getElementById('new_keunggulan').value = p.keunggulan;

    // Durasi
    var dur = document.getElementById('new_durasi');
    for (var i = 0; i < dur.options.length; i++) {
        if (dur.options[i].value === p.durasi) { dur.selectedIndex = i; break; }
    }

    selectIcon('new', p.icon);
    selectWarna('new', p.warna);
}

// ─── Format harga ─────────────────────────────────────────────
function formatHarga(el) {
    var raw = el.value.replace(/[^0-9]/g, '');
    el.value = parseInt(raw || '0').toLocaleString('id-ID');
}

// ─── Icon selector ────────────────────────────────────────────
function selectIcon(prefix, ico) {
    document.querySelectorAll('#iconGrid_' + prefix + ' .icon-opt').forEach(function(el) {
        el.classList.remove('active');
        if (el.dataset.ico === ico || el.querySelector('i')?.className?.includes(ico)) {
            el.classList.add('active');
        }
    });
    document.getElementById(prefix + '_icon').value = ico;
}

// ─── Warna selector ───────────────────────────────────────────
function selectWarna(prefix, hex) {
    document.querySelectorAll('#warnaGrid_' + prefix + ' .warna-opt').forEach(function(el) {
        el.classList.remove('active');
        if (el.dataset.hex === hex || el.style.background === hex) {
            el.classList.add('active');
        }
    });
    document.getElementById(prefix + '_warna').value = hex;
}

// ─── Populate edit modal ──────────────────────────────────────
document.getElementById('modalEdit').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('edit_id').value          = b.dataset.id;
    document.getElementById('edit_nama').value        = b.dataset.nama;
    document.getElementById('edit_deskripsi').value   = b.dataset.deskripsi;
    document.getElementById('edit_keunggulan').value  = b.dataset.keunggulan;
    document.getElementById('edit_status').value      = b.dataset.status;

    // Harga
    var h = parseInt(b.dataset.harga || '0');
    document.getElementById('edit_harga').value = h.toLocaleString('id-ID');

    // Durasi
    var dur = document.getElementById('edit_durasi');
    var d = b.dataset.durasi;
    for (var i = 0; i < dur.options.length; i++) {
        if (dur.options[i].value === d) { dur.selectedIndex = i; break; }
    }

    // Icon & Warna
    selectIcon('edit', b.dataset.icon);
    selectWarna('edit', b.dataset.warna);
});

// ─── Sebelum submit: bersihkan format angka harga ─────────────
function collectKP(prefix) {
    var hEl = document.getElementById(prefix + '_harga');
    hEl.value = hEl.value.replace(/[^0-9]/g, '');
}

// ─── Format angka harga di edit modal ─────────────────────────
document.querySelectorAll('[oninput="formatHarga(this)"]').forEach(function(el) {
    el.addEventListener('focus', function() {
        // sudah diformat, ok
    });
});
</script>
</body>
</html>
