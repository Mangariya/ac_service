<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'teknisi'])) {
    header("Location: ../auth/login.php");
    exit;
}

$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';
$is_teknisi = ($_SESSION['user']['role'] ?? '') === 'teknisi';

$selected_teknisi_id = $_GET['teknisi_id'] ?? '';
$tanggal_mulai = $_GET['mulai'] ?? date('Y-m-d', strtotime('-6 days'));
$tanggal_selesai = $_GET['selesai'] ?? date('Y-m-d');

$daftar_teknisi = [];

if ($is_admin) {
    $stmt_teknisi = $conn->prepare("
        SELECT id, nama, email, spesialisasi, wilayah
        FROM users
        WHERE role = 'teknisi' OR email LIKE '%@teknisi.com'
        ORDER BY nama ASC
    ");
    $stmt_teknisi->execute();
    $daftar_teknisi = $stmt_teknisi->fetchAll(PDO::FETCH_ASSOC);
}

$where = ["b.tanggal BETWEEN ? AND ?"];
$params = [$tanggal_mulai, $tanggal_selesai];

if ($is_teknisi) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $_SESSION['user']['id'];
} elseif ($is_admin && !empty($selected_teknisi_id)) {
    $where[] = "b.teknisi_id = ?";
    $params[] = $selected_teknisi_id;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $conn->prepare("
    SELECT 
        b.*,
        u.nama AS nama_pelanggan,
        u.telepon AS telepon_pelanggan,
        t.nama AS nama_teknisi
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN users t ON b.teknisi_id = t.id
    $where_sql
    ORDER BY b.tanggal ASC, b.id ASC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function angkaHarga($harga)
{
    return (int) preg_replace('/[^0-9]/', '', (string) $harga);
}

function rupiah($angka)
{
    return 'Rp ' . number_format((int) $angka, 0, ',', '.');
}

function formatTanggal($tanggal)
{
    if (empty($tanggal)) return '-';
    return date('d M Y', strtotime($tanggal));
}

function statusLabel($status)
{
    if ($status === 'Selesai' || $status === 'completed') return 'Selesai';
    if ($status === 'Dibatalkan' || $status === 'rejected') return 'Dibatalkan';
    if ($status === 'Diproses' || $status === 'processing') return 'Diproses';
    return 'Menunggu';
}

$total_reservasi = count($bookings);
$total_pendapatan = 0;
$total_selesai = 0;
$total_batal = 0;

$pendapatan_per_tanggal = [];
$reservasi_per_tanggal = [];

$periode = new DatePeriod(
    new DateTime($tanggal_mulai),
    new DateInterval('P1D'),
    (new DateTime($tanggal_selesai))->modify('+1 day')
);

foreach ($periode as $hari) {
    $key = $hari->format('Y-m-d');
    $pendapatan_per_tanggal[$key] = 0;
    $reservasi_per_tanggal[$key] = 0;
}

foreach ($bookings as $row) {
    $status = $row['status'] ?? 'Menunggu';
    $tanggal = $row['tanggal'] ?? '';

    if (!empty($tanggal) && isset($reservasi_per_tanggal[$tanggal])) {
        $reservasi_per_tanggal[$tanggal]++;
    }

    if ($status === 'Selesai' || $status === 'completed') {
        $total_selesai++;
        $harga = angkaHarga($row['harga'] ?? 0);
        $total_pendapatan += $harga;

        if (!empty($tanggal) && isset($pendapatan_per_tanggal[$tanggal])) {
            $pendapatan_per_tanggal[$tanggal] += $harga;
        }
    }

    if ($status === 'Dibatalkan' || $status === 'rejected') {
        $total_batal++;
    }
}

$jumlah_hari = max(1, count($reservasi_per_tanggal));
$rata_rata = $total_pendapatan / $jumlah_hari;
$pendapatan_tertinggi = !empty($pendapatan_per_tanggal) ? max($pendapatan_per_tanggal) : 0;
$pendapatan_terendah = !empty($pendapatan_per_tanggal) ? min($pendapatan_per_tanggal) : 0;

$tanggal_tertinggi = '-';
$tanggal_terendah = '-';

foreach ($pendapatan_per_tanggal as $tanggal => $nilai) {
    if ($nilai == $pendapatan_tertinggi) {
        $tanggal_tertinggi = formatTanggal($tanggal);
    }

    if ($nilai == $pendapatan_terendah) {
        $tanggal_terendah = formatTanggal($tanggal);
    }
}

if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-ac-service.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Pelanggan', 'Teknisi', 'Layanan', 'Tanggal', 'Waktu', 'Status', 'Harga']);

    foreach ($bookings as $row) {
        fputcsv($output, [
            $row['id'],
            $row['nama_pelanggan'] ?? '-',
            $row['nama_teknisi'] ?? '-',
            $row['layanan'] ?? '-',
            $row['tanggal'] ?? '-',
            $row['waktu'] ?? '-',
            statusLabel($row['status'] ?? 'Menunggu'),
            angkaHarga($row['harga'] ?? 0)
        ]);
    }

    fclose($output);
    exit;
}

$chart_labels = [];
$chart_data = [];

foreach ($reservasi_per_tanggal as $tanggal => $jumlah) {
    $chart_labels[] = date('d M', strtotime($tanggal));
    $chart_data[] = $jumlah;
}

$query_download = [
    'mulai' => $tanggal_mulai,
    'selesai' => $tanggal_selesai,
    'download' => 'csv'
];

if ($is_admin && !empty($selected_teknisi_id)) {
    $query_download['teknisi_id'] = $selected_teknisi_id;
}

$download_url = 'laporan.php?' . http_build_query($query_download);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - AC Service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            gap: 20px;
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

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-form {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 8px 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }

        .date-form input {
            border: 0;
            outline: 0;
            font-size: 13px;
            font-weight: 700;
            color: #1E293B;
        }

        .btn-download {
            height: 42px;
            border: 0;
            border-radius: 10px;
            background: #0B73F6;
            color: white;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 7px 12px rgba(11, 115, 246, 0.28);
        }

        .content-area {
            padding: 48px 72px 44px;
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

        .stats-grid {
            max-width: 760px;
            margin: 0 auto 44px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px 72px;
        }

        .stat-card {
            min-height: 122px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.18);
            padding: 26px 34px;
            display: flex;
            align-items: center;
            gap: 26px;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .icon-blue {
            background: #E7EDFF;
            color: #2563EB;
        }

        .icon-green {
            background: #DDF8EC;
            color: #16A765;
        }

        .icon-red {
            background: #FFE4E4;
            color: #EF4444;
        }

        .stat-label {
            color: #64748B;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #050816;
            line-height: 1.1;
        }

        .report-grid {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 28px;
        }

        .chart-card,
        .summary-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.18);
            padding: 28px;
        }

        .chart-card h4,
        .summary-card h4 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 20px;
            color: #111827;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 0;
            border-bottom: 1px solid #E5E7EB;
            font-size: 13px;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-row span {
            color: #475569;
            font-weight: 700;
        }

        .summary-row strong {
            color: #111827;
            font-weight: 800;
            text-align: right;
        }

        .summary-row small {
            display: block;
            color: #64748B;
            margin-top: 4px;
            font-weight: 500;
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
            }

            .top-actions,
            .date-form {
                width: 100%;
                flex-wrap: wrap;
            }

            .content-area {
                padding: 24px 18px;
            }

            .teknisi-card {
                width: 100%;
            }

            .stats-grid,
            .report-grid {
                grid-template-columns: 1fr;
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
            <a href="reservasi.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>">
                <i class="bi bi-calendar-check"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php<?= $is_admin && !empty($selected_teknisi_id) ? '?teknisi_id=' . urlencode($selected_teknisi_id) : ''; ?>" class="active">
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
                <h3>Laporan</h3>
                <p>Lihat laporan reservasi & pendapatan</p>
            </div>
        </div>

        <div class="top-actions">
            <form method="GET" class="date-form">
                <?php if ($is_admin && !empty($selected_teknisi_id)): ?>
                    <input type="hidden" name="teknisi_id" value="<?= htmlspecialchars($selected_teknisi_id); ?>">
                <?php endif; ?>

                <i class="bi bi-calendar3 text-muted"></i>
                <input type="date" name="mulai" value="<?= htmlspecialchars($tanggal_mulai); ?>">
                <span class="fw-bold text-muted">-</span>
                <input type="date" name="selesai" value="<?= htmlspecialchars($tanggal_selesai); ?>">
                <button class="btn btn-sm btn-primary fw-bold" type="submit">Terapkan</button>
            </form>

            <a href="<?= htmlspecialchars($download_url); ?>" class="btn-download">
                <i class="bi bi-download"></i>
                Unduh Laporan
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php if ($is_admin): ?>
            <h2 class="section-title">Pilih Data Teknisi</h2>

            <div class="teknisi-grid">
                <?php
                    $query_semua = [
                        'mulai' => $tanggal_mulai,
                        'selesai' => $tanggal_selesai
                    ];
                ?>

                <a href="laporan.php?<?= http_build_query($query_semua); ?>" class="teknisi-card <?= empty($selected_teknisi_id) ? 'active' : ''; ?>">
                    <strong>Semua<br>Teknisi</strong>
                    <small>Semua data laporan</small>
                </a>

                <?php foreach ($daftar_teknisi as $teknisi): ?>
                    <?php
                        $query_teknisi = [
                            'teknisi_id' => $teknisi['id'],
                            'mulai' => $tanggal_mulai,
                            'selesai' => $tanggal_selesai
                        ];
                    ?>

                    <a href="laporan.php?<?= http_build_query($query_teknisi); ?>" class="teknisi-card <?= $selected_teknisi_id == $teknisi['id'] ? 'active' : ''; ?>">
                        <strong><?= htmlspecialchars($teknisi['nama'] ?? 'Teknisi'); ?></strong>
                        <small><?= htmlspecialchars(!empty($teknisi['spesialisasi']) ? $teknisi['spesialisasi'] : 'Teknisi AC'); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="bi bi-calendar3"></i>
                </div>

                <div>
                    <div class="stat-label">Total Reservasi</div>
                    <div class="stat-value"><?= $total_reservasi; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>

                <div>
                    <div class="stat-label">Pendapatan</div>
                    <div class="stat-value"><?= rupiah($total_pendapatan); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="bi bi-clipboard-check"></i>
                </div>

                <div>
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value"><?= $total_selesai; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="bi bi-x-circle"></i>
                </div>

                <div>
                    <div class="stat-label">Dibatalkan</div>
                    <div class="stat-value"><?= $total_batal; ?></div>
                </div>
            </div>
        </div>

        <div class="report-grid">
            <div class="chart-card">
                <h4>Grafik Reservasi</h4>
                <canvas id="grafikReservasi" height="190"></canvas>
            </div>

            <div class="summary-card">
                <h4>Ringkasan Pendapatan</h4>

                <div class="summary-row">
                    <span>Total Pendapatan</span>
                    <strong><?= rupiah($total_pendapatan); ?></strong>
                </div>

                <div class="summary-row">
                    <span>Rata-rata per Hari</span>
                    <strong><?= rupiah($rata_rata); ?></strong>
                </div>

                <div class="summary-row">
                    <span>Pendapatan Tertinggi</span>
                    <strong>
                        <?= rupiah($pendapatan_tertinggi); ?>
                        <small><?= $tanggal_tertinggi; ?></small>
                    </strong>
                </div>

                <div class="summary-row">
                    <span>Pendapatan Terendah</span>
                    <strong>
                        <?= rupiah($pendapatan_terendah); ?>
                        <small><?= $tanggal_terendah; ?></small>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('grafikReservasi');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels); ?>,
            datasets: [{
                data: <?= json_encode($chart_data); ?>,
                borderColor: '#0B73F6',
                backgroundColor: 'rgba(11, 115, 246, 0.08)',
                borderWidth: 3,
                pointBackgroundColor: '#0B73F6',
                pointBorderColor: '#0B73F6',
                pointRadius: 5,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            weight: '600'
                        }
                    },
                    grid: {
                        color: '#E5E7EB'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            weight: '600'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

</body>
</html>