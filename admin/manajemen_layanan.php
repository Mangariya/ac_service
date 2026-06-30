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
        SELECT id, nama, email, telepon, spesialisasi, wilayah
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

$where = [];
$params = [];

if ($is_teknisi) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $_SESSION['user']['id'];
} elseif ($is_admin && !empty($selected_teknisi_id)) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $selected_teknisi_id;
}

$where_sql = '';

if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$stmt = $conn->prepare("
    SELECT
        b.*,
        u.nama AS nama_pelanggan,
        u.email AS email_pelanggan,
        u.telepon AS telepon_pelanggan,
        t.nama AS nama_teknisi
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN users t ON b.teknisi_id = t.id
    $where_sql
    ORDER BY b.tanggal DESC NULLS LAST, b.id DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_booking = count($bookings);

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

function ambilTeleponPelanggan($row)
{
    if (!empty($row['telepon_pelanggan'])) {
        return $row['telepon_pelanggan'];
    }

    $catatan = $row['catatan'] ?? '';

    if (preg_match('/Telp:\s*([^|]+)/i', $catatan, $matches)) {
        return trim($matches[1]);
    }

    return '-';
}

function nomorWhatsapp($telepon)
{
    $nomor = preg_replace('/[^0-9]/', '', $telepon);

    if (empty($nomor)) {
        return '';
    }

    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    return $nomor;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Layanan - AC Service</title>

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

        .summary-card {
            width: 310px;
            min-height: 118px;
            margin: 0 auto 34px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 20px;
        }

        .summary-icon {
            width: 58px;
            height: 58px;
            border-radius: 15px;
            background: #E0F2FE;
            color: #0369A1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }

        .summary-card small {
            color: #64748B;
            font-weight: 700;
        }

        .summary-card h3 {
            font-weight: 800;
            margin: 3px 0;
        }

        .table-card {
            max-width: 1180px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.16);
        }

        .table-wrapper {
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #F8FAFC;
            color: #1E293B;
            font-size: 13px;
            font-weight: 800;
            padding: 16px;
            border-bottom: 1px solid #E5E7EB;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            font-size: 13px;
            font-weight: 600;
            border-bottom: 1px solid #F1F5F9;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 12px;
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

        .btn-wa {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #22C55E;
            color: white;
            border-radius: 8px;
            padding: 7px 11px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            margin-top: 6px;
        }

        .btn-wa:hover {
            background: #16A34A;
            color: white;
        }

        .bottom-info {
            max-width: 1180px;
            margin: 18px auto 0;
            color: #64748B;
            font-size: 14px;
        }

        @media (max-width: 992px) {
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

            .teknisi-card {
                width: 100%;
            }

            .summary-card {
                width: 100%;
                max-width: 310px;
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
            <a href="manajemen_layanan.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>" class="active">
                <i class="bi bi-briefcase"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="layanan_saya.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>">
                <i class="bi bi-bag-heart-fill"></i>
                Layanan Saya
            </a>
        </li>

        <li>
            <a href="reservasi.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>">
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
                <h3>Manajemen Layanan</h3>
                <p>Kelola data layanan pelanggan berdasarkan teknisi.</p>
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
                <a href="manajemen_layanan.php" class="teknisi-card <?= empty($selected_teknisi_id) ? 'active' : ''; ?>">
                    <strong>Semua<br>Teknisi</strong>
                    <small>Semua data layanan</small>
                </a>

                <?php foreach ($daftar_teknisi as $teknisi): ?>
                    <a href="manajemen_layanan.php?teknisi_id=<?= $teknisi['id']; ?>" class="teknisi-card <?= $selected_teknisi_id == $teknisi['id'] ? 'active' : ''; ?>">
                        <strong><?= htmlspecialchars($teknisi['nama'] ?? 'Teknisi'); ?></strong>
                        <small><?= htmlspecialchars(!empty($teknisi['spesialisasi']) ? $teknisi['spesialisasi'] : 'Teknisi AC'); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="summary-card">
            <div class="summary-icon">
                <i class="bi bi-calendar3"></i>
            </div>

            <div>
                <small>Total Booking</small>
                <h3><?= $total_booking; ?></h3>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID Reservasi</th>
                            <th>Pelanggan</th>
                            <th>No Telepon</th>
                            <th>Teknisi</th>
                            <th>Layanan</th>
                            <th>Tanggal & Waktu</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>WhatsApp</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $row): ?>
                                <?php
                                    $status_row = $row['status'] ?? 'Menunggu';
                                    $telepon = ambilTeleponPelanggan($row);
                                    $wa = nomorWhatsapp($telepon);
                                ?>

                                <tr>
                                    <td><?= kodeReservasi($row['id'], $row['tanggal'] ?? null); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($telepon); ?></td>
                                    <td><?= htmlspecialchars($row['nama_teknisi'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['layanan'] ?? '-'); ?></td>
                                    <td><?= formatTanggal($row['tanggal'] ?? null); ?> - <?= htmlspecialchars($row['waktu'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-pill <?= statusClass($status_row); ?>">
                                            <?= statusLabel($status_row); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($wa)): ?>
                                            <a href="https://wa.me/<?= htmlspecialchars($wa); ?>" target="_blank" class="btn-wa">
                                                <i class="bi bi-whatsapp"></i>
                                                Chat
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Belum ada data booking untuk teknisi ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bottom-info">
            Menampilkan 1 - <?= $total_booking; ?> dari <?= $total_booking; ?> data booking
        </div>
    </div>
</div>

</body>
</html>