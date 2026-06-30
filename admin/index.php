<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$teknisi_id = $_GET['teknisi_id'] ?? '';

$stmt = $conn->prepare("
    SELECT id, nama, email, telepon, spesialisasi, wilayah
    FROM users
    WHERE role = 'teknisi' OR email LIKE '%@teknisi.com'
    ORDER BY nama ASC
");
$stmt->execute();
$teknisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$where_teknisi = "";
$params = [];

if (!empty($teknisi_id)) {
    $where_teknisi = " WHERE b.teknisi_id = ? ";
    $params[] = $teknisi_id;
}

$total_res = $conn->prepare("SELECT COUNT(*) FROM bookings b $where_teknisi");
$total_res->execute($params);
$total_res = $total_res->fetchColumn();

$hari_ini_query = "
    SELECT COUNT(*)
    FROM bookings b
    $where_teknisi
    " . (empty($where_teknisi) ? " WHERE " : " AND ") . " b.tanggal = CURRENT_DATE
";
$hari_ini_stmt = $conn->prepare($hari_ini_query);
$hari_ini_stmt->execute($params);
$hari_ini = $hari_ini_stmt->fetchColumn();

$selesai_query = "
    SELECT COUNT(*)
    FROM bookings b
    $where_teknisi
    " . (empty($where_teknisi) ? " WHERE " : " AND ") . " b.status IN ('Selesai', 'completed')
";
$selesai_stmt = $conn->prepare($selesai_query);
$selesai_stmt->execute($params);
$selesai = $selesai_stmt->fetchColumn();

$batal_query = "
    SELECT COUNT(*)
    FROM bookings b
    $where_teknisi
    " . (empty($where_teknisi) ? " WHERE " : " AND ") . " b.status IN ('Dibatalkan', 'rejected')
";
$batal_stmt = $conn->prepare($batal_query);
$batal_stmt->execute($params);
$batal = $batal_stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT
        b.*,
        u.nama AS nama_pelanggan,
        t.nama AS nama_teknisi
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN users t ON b.teknisi_id = t.id
    $where_teknisi
    ORDER BY b.tanggal DESC NULLS LAST, b.id DESC
    LIMIT 5
");
$stmt->execute($params);
$reservasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nama_teknisi_aktif = 'Semua Teknisi';

if (!empty($teknisi_id)) {
    foreach ($teknisi as $item) {
        if ($item['id'] == $teknisi_id) {
            $nama_teknisi_aktif = $item['nama'];
            break;
        }
    }
}

// Hitung calon teknisi menunggu
$cek_tbl = $conn->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='pendaftaran_teknisi')");
$cek_tbl->execute();
$pending_calon = 0;
if ((bool) $cek_tbl->fetchColumn()) {
    $cPend = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_teknisi WHERE status='menunggu'");
    $cPend->execute();
    $pending_calon = (int) $cPend->fetchColumn();
}

function statusClass($status)
{
    if ($status === 'Selesai' || $status === 'completed') return 'status-selesai';
    if ($status === 'Dibatalkan' || $status === 'rejected') return 'status-batal';
    if ($status === 'Diproses' || $status === 'processing') return 'status-proses';
    return 'status-menunggu';
}

function statusLabel($status)
{
    if ($status === 'Selesai' || $status === 'completed') return 'Selesai';
    if ($status === 'Dibatalkan' || $status === 'rejected') return 'Dibatalkan';
    if ($status === 'Diproses' || $status === 'processing') return 'Diproses';
    return 'Menunggu';
}

function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d M Y', strtotime($tanggal));
}

function kodeReservasi($id, $tanggal)
{
    $tahun = !empty($tanggal) ? date('Y', strtotime($tanggal)) : date('Y');
    return 'RSV-' . $tahun . '-' . sprintf('%03d', $id);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AC Service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #DFF0FA;
            color: #0F172A;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background: white;
            border-right: 1px solid #E2E8F0;
            padding-top: 20px;
        }

        .sidebar-brand {
            padding: 0 30px 30px;
            font-size: 22px;
            font-weight: 800;
            color: #1E293B;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #64748B;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #EFF6FF;
            color: #2563EB;
            border-right: 4px solid #2563EB;
        }

        .badge-notif-sb {
            background: #EF4444;
            color: white;
            border-radius: 50px;
            padding: 1px 7px;
            font-size: 10px;
            font-weight: 800;
            margin-left: auto;
        }

        .logout-box {
            position: absolute;
            bottom: 30px;
            width: 100%;
        }

        .main {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            height: 90px;
            background: #fff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 38px;
        }

        .top-left {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .hamburger {
            font-size: 30px;
            color: #475569;
        }

        .top-title h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .top-title p {
            margin: 0;
            color: #6B7280;
            font-size: 14px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .notif {
            position: relative;
            font-size: 24px;
            color: #475569;
        }

        .notif span {
            position: absolute;
            top: -7px;
            right: -7px;
            background: #EF4444;
            color: white;
            width: 18px;
            height: 18px;
            font-size: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #EAF4FF;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563EB;
            font-size: 22px;
        }

        .content {
            padding: 34px 58px 44px;
        }

        .section-title {
            text-align: center;
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 28px;
            color: #050816;
        }

        .teknisi-grid {
            max-width: 940px;
            margin: 0 auto 50px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 24px;
        }

        .teknisi-card {
            width: 220px;
            min-height: 118px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, .18);
            padding: 22px 24px;
            text-decoration: none;
            color: #050816;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: .2s;
        }

        .teknisi-card:hover,
        .teknisi-card.active {
            transform: translateY(-4px);
            background: #118BFA;
            color: #fff;
        }

        .teknisi-card strong {
            font-size: 26px;
            line-height: 1.12;
            word-break: break-word;
        }

        .teknisi-card small {
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            opacity: .8;
        }

        .teknisi-card.active small,
        .teknisi-card:hover small {
            color: #EAF4FF;
        }

        .stats-title {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 22px;
        }

        .stats {
            max-width: 920px;
            margin: 0 auto 48px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .stat-card {
            min-height: 100px;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, .16);
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .orange { background: #FFF0D4; color: #F59E0B; }
        .blue { background: #E7ECFF; color: #2563EB; }
        .green { background: #DDF8EC; color: #16A765; }
        .red { background: #FFE1E5; color: #EF2438; }

        .stat-info small {
            color: #737B91;
            font-size: 11px;
            font-weight: 700;
        }

        .stat-info h3 {
            font-size: 22px;
            font-weight: 800;
            margin: 3px 0;
            color: #080C28;
        }

        .stat-info p {
            margin: 0;
            font-size: 10px;
            font-weight: 700;
        }

        .table-card {
            max-width: 1080px;
            margin: 0 auto;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, .18);
            padding: 22px 16px;
        }

        .table-card h5 {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .table {
            font-size: 12px;
        }

        .table thead th {
            background: #F8FAFC;
            font-size: 11px;
            font-weight: 800;
            padding: 12px;
        }

        .table tbody td {
            padding: 13px 12px;
            vertical-align: middle;
            font-weight: 600;
        }

        .status-pill {
            display: inline-flex;
            justify-content: center;
            min-width: 76px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
        }

        .status-menunggu {
            background: #FFF3D8;
            color: #F59E0B;
        }

        .status-proses {
            background: #E7EDFF;
            color: #3366FF;
        }

        .status-selesai {
            background: #DFF6EA;
            color: #22A866;
        }

        .status-batal {
            background: #FFE4E4;
            color: #EF4444;
        }

        .btn-detail {
            border: 1px solid #3B74FF;
            color: #2563EB;
            background: white;
            border-radius: 5px;
            padding: 5px 15px;
            font-weight: 800;
            font-size: 11px;
            text-decoration: none;
        }

        @media(max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .logout-box {
                position: static;
                margin-top: 20px;
            }

            .main {
                margin-left: 0;
            }

            .content {
                padding: 24px 18px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .teknisi-card {
                width: 100%;
            }

            .topbar {
                height: auto;
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
                padding: 24px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-snow2 text-primary" style="font-size: 28px;"></i>
        AC SERVICE
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php<?= !empty($teknisi_id) ? '?teknisi_id=' . urlencode($teknisi_id) : ''; ?>" class="active">
                <i class="bi bi-grid-fill"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="manajemen_layanan.php<?= !empty($teknisi_id) ? '?teknisi_id=' . urlencode($teknisi_id) : ''; ?>">
                <i class="bi bi-briefcase"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="reservasi.php<?= !empty($teknisi_id) ? '?teknisi_id=' . urlencode($teknisi_id) : ''; ?>">
                <i class="bi bi-calendar-check"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php<?= !empty($teknisi_id) ? '?teknisi_id=' . urlencode($teknisi_id) : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                Laporan
            </a>
        </li>

        <li>
            <a href="teknisi.php">
                <i class="bi bi-person-plus"></i>
                Teknisi
            </a>
        </li>

        <li>
            <a href="calon_teknisi.php">
                <i class="bi bi-person-check"></i>
                Calon Teknisi
                <?php if ($pending_calon > 0): ?>
                    <span class="badge-notif-sb"><?= $pending_calon ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li>
            <a href="bantuan.php">
                <i class="bi bi-question-circle"></i>
                Bantuan
            </a>
        </li>
    </ul>

    <div class="logout-box">
        <a href="../auth/logout.php" class="text-danger fw-bold" style="padding-left: 30px; text-decoration:none;">
            <i class="bi bi-box-arrow-left me-2"></i>
            Logout
        </a>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <div class="top-left">
            <i class="bi bi-list hamburger"></i>

            <div class="top-title">
                <h1>Dashboard</h1>
                <p>Selamat datang, Admin! Berikut ringkasan sistem AC Service.</p>
            </div>
        </div>

        <div class="top-right">
            <div class="notif">
                <i class="bi bi-bell"></i>
                <span>5</span>
            </div>

            <div class="profile">
                <div class="profile-img">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin'); ?></span>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
    </header>

    <section class="content">
        <h2 class="section-title">Pilih Data Teknisi</h2>

        <div class="teknisi-grid">
            <a href="index.php" class="teknisi-card <?= empty($teknisi_id) ? 'active' : ''; ?>">
                <strong>Semua<br>Teknisi</strong>
                <small>Semua data reservasi</small>
            </a>

            <?php foreach ($teknisi as $row): ?>
                <a href="index.php?teknisi_id=<?= $row['id']; ?>" class="teknisi-card <?= $teknisi_id == $row['id'] ? 'active' : ''; ?>">
                    <strong><?= htmlspecialchars($row['nama'] ?? 'Teknisi'); ?></strong>
                    <small><?= htmlspecialchars(!empty($row['spesialisasi']) ? $row['spesialisasi'] : 'Teknisi AC'); ?></small>
                </a>
            <?php endforeach; ?>
        </div>

        <h2 class="stats-title">Akumulasi Data <?= htmlspecialchars($nama_teknisi_aktif); ?></h2>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="bi bi-clock"></i></div>
                <div class="stat-info">
                    <small>Reservasi Hari Ini</small>
                    <h3><?= $hari_ini; ?></h3>
                    <p style="color:#F59E0B;">Berlangsung hari ini</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-calendar3"></i></div>
                <div class="stat-info">
                    <small>Total Reservasi</small>
                    <h3><?= $total_res; ?></h3>
                    <p style="color:#22C879;">+15% dari minggu lalu</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <small>Selesai</small>
                    <h3><?= $selesai; ?></h3>
                    <p style="color:#22C879;">+20% dari minggu lalu</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
                <div class="stat-info">
                    <small>Dibatalkan</small>
                    <h3><?= $batal; ?></h3>
                    <p style="color:#EF4444;">-10% dari minggu lalu</p>
                </div>
            </div>
        </div>

        <div class="table-card">
            <h5>Reservasi Terbaru</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID Reservasi</th>
                            <th>Pelanggan</th>
                            <th>Teknisi</th>
                            <th>Layanan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($reservasi) > 0): ?>
                            <?php foreach ($reservasi as $row): ?>
                                <tr>
                                    <td><?= kodeReservasi($row['id'], $row['tanggal'] ?? null); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['nama_teknisi'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['layanan'] ?? '-'); ?></td>
                                    <td><?= formatTanggal($row['tanggal'] ?? null); ?></td>
                                    <td><?= htmlspecialchars($row['waktu'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-pill <?= statusClass($row['status'] ?? 'Menunggu'); ?>">
                                            <?= statusLabel($row['status'] ?? 'Menunggu'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="reservasi.php<?= !empty($teknisi_id) ? '?teknisi_id=' . urlencode($teknisi_id) : ''; ?>" class="btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Belum ada reservasi untuk <?= htmlspecialchars($nama_teknisi_aktif); ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

</body>
</html>