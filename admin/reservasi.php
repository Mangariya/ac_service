<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'teknisi'])) {
    header("Location: ../auth/login.php");
    exit;
}

$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';
$is_teknisi = ($_SESSION['user']['role'] ?? '') === 'teknisi';

$selected_teknisi_id = '';

if ($is_admin) {
    $selected_teknisi_id = $_GET['teknisi_id'] ?? '';

    $stmt_teknisi = $conn->prepare("
        SELECT id, nama, email, spesialisasi, wilayah
        FROM users
        WHERE role = 'teknisi' OR email LIKE '%@teknisi.com'
        ORDER BY nama ASC
    ");
    $stmt_teknisi->execute();
    $daftar_teknisi = $stmt_teknisi->fetchAll(PDO::FETCH_ASSOC);
} else {
    $selected_teknisi_id = $_SESSION['user']['id'];
    $daftar_teknisi = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $booking_id = $_POST['booking_id'] ?? '';

    $status_baru = null;

    if ($aksi === 'accept') {
        $status_baru = 'Diproses';
    } elseif ($aksi === 'batalkan') {
        $status_baru = 'Dibatalkan';
    } elseif ($aksi === 'selesai') {
        $status_baru = 'Selesai';
    }

    if (!empty($booking_id) && !empty($status_baru)) {
        if ($is_teknisi) {
            $stmt = $conn->prepare("
                UPDATE bookings
                SET status = ?
                WHERE id = ?
                AND teknisi_id = ?
            ");
            $stmt->execute([$status_baru, $booking_id, $_SESSION['user']['id']]);
        } else {
            $stmt = $conn->prepare("
                UPDATE bookings
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$status_baru, $booking_id]);
        }

        $redirect_query = [];

        if ($is_admin && !empty($selected_teknisi_id)) {
            $redirect_query['teknisi_id'] = $selected_teknisi_id;
        }

        if (!empty($_GET['status']) && $_GET['status'] !== 'Semua') {
            $redirect_query['status'] = $_GET['status'];
        }

        $redirect = 'reservasi.php';

        if (!empty($redirect_query)) {
            $redirect .= '?' . http_build_query($redirect_query);
        }

        header("Location: $redirect");
        exit;
    }
}

$status_filter = $_GET['status'] ?? 'Semua';

$where = [];
$params = [];

if ($is_teknisi) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $_SESSION['user']['id'];
} elseif ($is_admin && !empty($selected_teknisi_id)) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $selected_teknisi_id;
}

if ($status_filter !== 'Semua') {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
}

$where_sql = '';

if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$stmt = $conn->prepare("
    SELECT 
        b.*,
        u.nama AS nama_pelanggan,
        t.nama AS nama_teknisi
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN users t ON b.teknisi_id = t.id
    $where_sql
    ORDER BY b.tanggal DESC NULLS LAST, b.id DESC
");
$stmt->execute($params);
$reservasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_reservasi = count($reservasi);

$menunggu_antrian = 0;
$sedang_dilayani = 0;
$selesai_hari_ini = 0;

foreach ($reservasi as $row) {
    $status_row = $row['status'] ?? 'Menunggu';

    if ($status_row === 'Menunggu') {
        $menunggu_antrian++;
    }

    if ($status_row === 'Diproses' || $status_row === 'Sedang Dilayani' || $status_row === 'processing') {
        $sedang_dilayani++;
    }

    if (
        ($status_row === 'Selesai' || $status_row === 'completed') &&
        !empty($row['tanggal']) &&
        $row['tanggal'] === date('Y-m-d')
    ) {
        $selesai_hari_ini++;
    }
}

function statusClass($status)
{
    if ($status === 'Selesai' || $status === 'completed') return 'status-selesai';
    if ($status === 'Dibatalkan' || $status === 'rejected') return 'status-batal';
    if ($status === 'Diproses' || $status === 'Sedang Dilayani' || $status === 'processing') return 'status-proses';
    return 'status-menunggu';
}

function statusLabel($status)
{
    if ($status === 'Selesai' || $status === 'completed') return 'Selesai';
    if ($status === 'Dibatalkan' || $status === 'rejected') return 'Dibatalkan';
    if ($status === 'Diproses' || $status === 'Sedang Dilayani' || $status === 'processing') return 'Diproses';
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
    <title>Reservasi - AC Service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #DFF0FA;
            color: #1E293B;
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

        .logout-box {
            position: absolute;
            bottom: 30px;
            width: 100%;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            height: 90px;
            background: white;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        .top-title h3 {
            font-weight: 800;
            margin-bottom: 3px;
        }

        .top-title p {
            margin: 0;
            color: #64748B;
            font-size: 14px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .notification {
            position: relative;
            font-size: 24px;
            color: #475569;
        }

        .notification span {
            position: absolute;
            top: -7px;
            right: -7px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #EF4444;
            color: white;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #EAF4FF;
            color: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .content-area {
            padding: 42px 72px 44px;
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
            margin: 0 auto 42px;
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

        .queue-stats {
            max-width: 960px;
            margin: 0 auto 22px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 42px;
        }

        .queue-card {
            background: white;
            border-radius: 10px;
            min-height: 112px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.18);
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .queue-icon {
            width: 62px;
            height: 62px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .queue-icon.blue {
            background: #E8EDFF;
            color: #1D4ED8;
        }

        .queue-icon.green {
            background: #DDF8EC;
            color: #16A765;
        }

        .queue-label {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }

        .queue-value {
            font-size: 34px;
            font-weight: 800;
            color: #050816;
            line-height: 1;
        }

        .combined-card {
            max-width: 960px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.18);
        }

        .combined-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .combined-header h4 {
            font-size: 21px;
            font-weight: 800;
            margin: 0;
            color: #111827;
        }

        .combined-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .combined-select,
        .combined-button {
            height: 42px;
            border-radius: 8px;
            border: 1px solid #64748B;
            background: white;
            padding: 0 14px;
            font-weight: 700;
            color: #1E293B;
        }

        .combined-button {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .combined-table-wrapper {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            overflow: hidden;
        }

        .combined-table {
            margin: 0;
        }

        .combined-table thead th {
            background: #F8FAFC;
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            padding: 13px 14px;
        }

        .combined-table tbody td {
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 1px solid #E5E7EB;
        }

        .combined-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
        }

        .status-menunggu {
            background: #FFF3D8;
            color: #F59E0B;
        }

        .status-proses {
            background: #E7EDFF;
            color: #2563EB;
        }

        .status-selesai {
            background: #DFF6EA;
            color: #16A765;
        }

        .status-batal {
            background: #FFE4E4;
            color: #EF4444;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            border: 2px solid #2563EB;
            color: #2563EB;
            background: white;
            border-radius: 7px;
            padding: 5px 12px;
            font-weight: 800;
            font-size: 13px;
        }

        .btn-action:hover {
            background: #2563EB;
            color: white;
        }

        .btn-cancel {
            border-color: #EF4444;
            color: #EF4444;
        }

        .btn-cancel:hover {
            background: #EF4444;
            color: white;
        }

        .btn-finish {
            border-color: #16A765;
            color: #16A765;
        }

        .btn-finish:hover {
            background: #16A765;
            color: white;
        }

        .btn-muted {
            border: 1px solid #CBD5E1;
            color: #94A3B8;
            background: #F8FAFC;
            border-radius: 7px;
            padding: 5px 12px;
            font-weight: 800;
            font-size: 13px;
        }

        .combined-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #1F2937;
            font-size: 14px;
            margin-top: 12px;
        }

        .combined-pagination {
            display: flex;
            align-items: center;
            gap: 18px;
            font-weight: 800;
        }

        .active-page {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #2563EB;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media(max-width: 992px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .logout-box {
                position: static;
                margin-top: 20px;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                height: auto;
                padding: 24px;
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .content-area {
                padding: 24px 18px;
            }

            .queue-stats {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .combined-header,
            .combined-footer,
            .combined-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .teknisi-card {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-snow2 text-primary" style="font-size: 28px;"></i>
        AC SERVICE
    </div>

    <ul class="sidebar-menu">
        <?php if ($is_admin): ?>
            <li>
                <a href="index.php">
                    <i class="bi bi-grid-fill"></i>
                    Dashboard
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="manajemen_layanan.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>">
                <i class="bi bi-briefcase"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="reservasi.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>" class="active">
                <i class="bi bi-calendar-check"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                Laporan
            </a>
        </li>

        <?php if ($is_admin): ?>
            <li>
                <a href="teknisi.php">
                    <i class="bi bi-person-plus"></i>
                    Teknisi
                </a>
            </li>
        <?php endif; ?>

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
</div>

<div class="main-content">
    <div class="topbar">
        <div class="top-left">
            <i class="bi bi-list hamburger"></i>

            <div class="top-title">
                <h3>Reservasi</h3>
                <p>Kelola semua reservasi pelanggan.</p>
            </div>
        </div>

        <div class="top-right">
            <div class="notification">
                <i class="bi bi-bell"></i>
                <span>5</span>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin'); ?></span>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
    </div>

    <div class="content-area">
        <?php if ($is_admin): ?>
            <h2 class="section-title">Pilih Data Teknisi</h2>

            <div class="teknisi-grid">
                <a href="reservasi.php<?= $status_filter !== 'Semua' ? '?status=' . urlencode($status_filter) : ''; ?>" class="teknisi-card <?= empty($selected_teknisi_id) ? 'active' : ''; ?>">
                    <strong>Semua<br>Teknisi</strong>
                    <small>Semua data reservasi</small>
                </a>

                <?php foreach ($daftar_teknisi as $teknisi): ?>
                    <?php
                        $query = ['teknisi_id' => $teknisi['id']];

                        if ($status_filter !== 'Semua') {
                            $query['status'] = $status_filter;
                        }
                    ?>

                    <a href="reservasi.php?<?= http_build_query($query); ?>" class="teknisi-card <?= $selected_teknisi_id == $teknisi['id'] ? 'active' : ''; ?>">
                        <strong><?= htmlspecialchars($teknisi['nama'] ?? 'Teknisi'); ?></strong>
                        <small><?= htmlspecialchars(!empty($teknisi['spesialisasi']) ? $teknisi['spesialisasi'] : 'Teknisi AC'); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="queue-stats">
            <div class="queue-card">
                <div class="queue-icon blue">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="queue-label">Menunggu Antrian</div>
                    <div class="queue-value"><?= $menunggu_antrian; ?></div>
                </div>
            </div>

            <div class="queue-card">
                <div class="queue-icon blue">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="queue-label">Sedang Dilayani</div>
                    <div class="queue-value"><?= $sedang_dilayani; ?></div>
                </div>
            </div>

            <div class="queue-card">
                <div class="queue-icon green">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div>
                    <div class="queue-label">Selesai Hari Ini</div>
                    <div class="queue-value"><?= $selesai_hari_ini; ?></div>
                </div>
            </div>
        </div>

        <div class="combined-card">
            <div class="combined-header">
                <h4>Daftar Reservasi & Antrian Gabungan</h4>

                <form method="GET" class="combined-actions">
                    <?php if ($is_admin && !empty($selected_teknisi_id)): ?>
                        <input type="hidden" name="teknisi_id" value="<?= htmlspecialchars($selected_teknisi_id); ?>">
                    <?php endif; ?>

                    <select name="status" class="combined-select">
                        <?php foreach (['Semua', 'Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'] as $status): ?>
                            <option value="<?= $status; ?>" <?= $status_filter === $status ? 'selected' : ''; ?>>
                                <?= $status === 'Semua' ? 'Semua Status' : $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="combined-button" type="submit">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>

                    <a href="reservasi.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>" class="combined-button">
                        <i class="bi bi-arrow-clockwise"></i>
                        Refresh
                    </a>
                </form>
            </div>

            <div class="combined-table-wrapper table-responsive">
                <table class="table combined-table align-middle">
                    <thead>
                        <tr>
                            <th>ID/No</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Tanggal & Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                            <th>Asal Data</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($reservasi) > 0): ?>
                            <?php foreach ($reservasi as $row): ?>
                                <?php
                                    $status_row = $row['status'] ?? 'Menunggu';
                                    $asal_data = !empty($row['user_id']) ? 'Reservasi' : 'Antrian';
                                ?>

                                <tr>
                                    <td><?= kodeReservasi($row['id'], $row['tanggal'] ?? null); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['layanan'] ?? '-'); ?></td>
                                    <td><?= formatTanggal($row['tanggal'] ?? null); ?> - <?= htmlspecialchars($row['waktu'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-pill <?= statusClass($status_row); ?>">
                                            <?= statusLabel($status_row); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($status_row === 'Menunggu'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="aksi" value="accept">
                                                    <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                                                    <button class="btn-action" type="submit">Accept</button>
                                                </form>

                                                <form method="POST" class="d-inline" onsubmit="return confirm('Batalkan booking ini?')">
                                                    <input type="hidden" name="aksi" value="batalkan">
                                                    <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                                                    <button class="btn-action btn-cancel" type="submit">Batalkan</button>
                                                </form>

                                            <?php elseif ($status_row === 'Diproses'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="aksi" value="selesai">
                                                    <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                                                    <button class="btn-action btn-finish" type="submit">Selesai</button>
                                                </form>

                                                <form method="POST" class="d-inline" onsubmit="return confirm('Batalkan booking ini?')">
                                                    <input type="hidden" name="aksi" value="batalkan">
                                                    <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                                                    <button class="btn-action btn-cancel" type="submit">Batalkan</button>
                                                </form>

                                            <?php elseif ($status_row === 'Selesai'): ?>
                                                <button class="btn-muted" type="button" disabled>Selesai</button>

                                            <?php elseif ($status_row === 'Dibatalkan'): ?>
                                                <button class="btn-muted" type="button" disabled>Dibatalkan</button>

                                            <?php else: ?>
                                                <button class="btn-muted" type="button" disabled><?= htmlspecialchars($status_row); ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= $asal_data; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada data reservasi.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="combined-footer">
                <div>Menampilkan 1-<?= min(10, $total_reservasi); ?> dari <?= $total_reservasi; ?> data</div>

                <div class="combined-pagination">
                    <i class="bi bi-chevron-left"></i>
                    <span class="active-page">1</span>
                    <span>2</span>
                    <span>...</span>
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>