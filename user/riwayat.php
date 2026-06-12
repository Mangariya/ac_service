<?php
session_start();
include '../config/database.php';

$user = $_SESSION['user'] ?? null;
$user_id = $user['id'] ?? null;
$email_filter = trim($_GET['email'] ?? '');

$filter = $_GET['filter'] ?? 'Semua';
$tanggal_filter = $_GET['tanggal'] ?? '';

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare(
        "SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ? AND column_name = ?
        )"
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

$bookings = [];
$search_email_required = false;

if (!$user_id && empty($email_filter)) {
    $search_email_required = true;
} else {
    if ($user_id) {
        $query_str = "SELECT * FROM bookings WHERE user_id = ? ";
        $params = [$user_id];
    } else {
        $has_email_column = columnExists($conn, 'bookings', 'email');

        if ($has_email_column) {
            $query_str = "SELECT * FROM bookings WHERE email = ? ";
            $params = [$email_filter];
        } else {
            $query_str = "SELECT * FROM bookings WHERE catatan ILIKE ? ";
            $params = ['%' . $email_filter . '%'];
        }
    }

    if ($filter == 'Selesai') {
        $query_str .= " AND status = 'Selesai'";
    } elseif ($filter == 'Dijadwalkan') {
        $query_str .= " AND status = 'Menunggu'";
    } elseif ($filter == 'Diproses') {
        $query_str .= " AND status = 'Diproses'";
    } elseif ($filter == 'Dibatalkan') {
        $query_str .= " AND status = 'Dibatalkan'";
    }

    if (!empty($tanggal_filter)) {
        $query_str .= " AND tanggal = ?";
        $params[] = $tanggal_filter;
    }

    $query_str .= " ORDER BY tanggal DESC";

    $stmt = $conn->prepare($query_str);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatusClass($status) {
    switch ($status) {
        case 'Selesai':
            return 'status-selesai';
        case 'Diproses':
            return 'status-proses';
        case 'Dibatalkan':
            return 'status-batal';
        default:
            return 'status-jadwal';
    }
}

function getStatusLabel($status) {
    if ($status === 'Menunggu') {
        return 'Dijadwalkan';
    }

    return $status ?: 'Dijadwalkan';
}

function formatHarga($harga) {
    $angka = preg_replace('/[^0-9]/', '', (string) $harga);
    return number_format((float) $angka, 0, ',', '.');
}

function safeDate($tanggal) {
    if (empty($tanggal)) {
        return '-';
    }

    return date('d M Y', strtotime($tanggal));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body{ background:#EAF6FF; }

        .navbar-custom{ background:white; padding:18px 50px; border-bottom:2px solid #E5E7EB; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .logo{ font-size:24px; font-weight:800; color:#111827 !important; text-decoration:none; }
        .menu-navbar{ gap:15px; }
        .menu-navbar .nav-link{ color:#6B7280; font-size:15px; font-weight:600; padding:10px 22px; border-radius:12px; transition:0.3s; }
        .menu-navbar .nav-link:hover{ background:#EFF6FF; color:#2563EB; }
        .active-menu{ background:#2563EB; color:white !important; font-weight:700; }

        .dropdown-user{ display:flex; align-items:center; gap:12px; border:none; background:none; }
        .user-icon{ width:48px; height:48px; border-radius:50%; background:#DBEAFE; display:flex; align-items:center; justify-content:center; font-size:24px; color:#2563EB; }
        .user-name{ font-weight:600; color:#374151; }

        .container-riwayat { margin-top: 50px; margin-bottom: 50px; padding: 0 50px; }
        .header-title { font-weight: 700; color: #111827; font-size: 32px; }
        .header-subtitle { color: #6B7280; margin-bottom: 30px; }
        .card-main { background: white; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 35px; }

        .filter-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F3F4F6;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-tabs-custom {
            border: none;
            display: flex;
            gap: 10px;
            list-style: none;
            padding: 0;
            flex-wrap: wrap;
        }

        .nav-tabs-custom .nav-link-filter {
            border: none;
            padding: 10px 15px;
            color: #6B7280;
            font-weight: 500;
            position: relative;
            transition: 0.3s;
            text-decoration: none;
        }

        .nav-tabs-custom .nav-link-filter.active {
            color: #2563EB;
            background: none;
            font-weight: 700;
        }

        .nav-tabs-custom .nav-link-filter.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #2563EB;
            border-radius: 3px;
        }

        .date-input {
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 12px;
            color: #4B5563;
            font-size: 14px;
            outline: none;
        }

        .booking-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .booking-item:last-child { border-bottom: none; }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 20px;
            background: #EFF6FF;
            color: #2563EB;
        }

        .booking-info { flex-grow: 1; }

        .booking-name {
            font-weight: 700;
            color: #111827;
            font-size: 17px;
            margin-bottom: 2px;
        }

        .booking-details {
            display: flex;
            gap: 15px;
            color: #6B7280;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
            min-width: 95px;
            text-align: center;
        }

        .status-selesai { background: #DCFCE7; color: #166534; }
        .status-jadwal { background: #FEF3C7; color: #92400E; }
        .status-proses { background: #E7EDFF; color: #2563EB; }
        .status-batal { background: #FEE2E2; color: #991B1B; }

        .btn-detail {
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-detail:hover {
            background: #F9FAFB;
            border-color: #2563EB;
            color: #2563EB;
        }

        .hero-banner{
            position:relative;
            width:100%;
            height:400px;
            background: linear-gradient(135deg, #2563EB 0%, #60A5FA 100%);
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .hero-content{ text-align:center; color:white; }
        .hero-content h1{ font-size:48px; font-weight:800; margin-bottom:15px; }
        .hero-content p{ font-size:18px; opacity:0.95; }
        .hero-content .hero-help { margin-top: 10px; font-size: 16px; color: rgba(255,255,255,0.9); }
        .hero-content .hero-help a { color: rgba(255,255,255,0.95); text-decoration: underline; font-weight: 600; }

        @media(max-width: 768px) {
            .navbar-custom{ padding:15px 20px; }
            .container-riwayat{ padding:0 18px; }
            .booking-item{ align-items:flex-start; flex-direction:column; gap:16px; }
            .text-end{ text-align:left !important; }
            .hero-content h1{ font-size:34px; }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center logo" href="home.php">
            <i class="bi bi-snow2 me-3" style="font-size:45px; color:#2563EB;"></i>
            <span>AC SERVICE</span>
        </a>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto menu-navbar">
                <li class="nav-item"><a class="nav-link" href="home.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="layanan.php">Layanan</a></li>
                <li class="nav-item"><a class="nav-link" href="tentang-kami.php">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php">Bantuan</a></li>
                <li class="nav-item"><a class="nav-link active-menu" href="riwayat.php">Riwayat</a></li>
            </ul>

            <?php if ($user_id): ?>
                <div class="dropdown">
                    <button class="btn dropdown-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <div class="user-icon"><i class="bi bi-person-fill"></i></div>
                        <span class="user-name"><?= htmlspecialchars($user['nama'] ?? 'User'); ?></span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <div>
                    <a href="../auth/login.php" class="btn btn-outline-primary">Masuk</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="hero-banner">
    <div class="hero-content">
        <h1>Riwayat Booking</h1>
        <p>Kelola dan Pantau Jadwal Servis AC Anda</p>
    </div>
</section>

<div class="container-riwayat">
    <?php if (!$user_id): ?>
        <div class="alert alert-info">
            <strong>Cari riwayat dengan email Anda.</strong> Masukkan email yang digunakan saat booking, lalu tekan Cari.
        </div>

        <form action="riwayat.php" method="GET" class="row g-2 mb-4">
            <div class="col-md-6">
                <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" value="<?= htmlspecialchars($email_filter); ?>" required>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Cari Riwayat</button>
            </div>
        </form>

        <?php if (!empty($email_filter)): ?>
            <div class="alert alert-secondary">
                Menampilkan riwayat untuk email <strong><?= htmlspecialchars($email_filter); ?></strong>.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card card-main">
        <div class="filter-wrapper">
            <ul class="nav-tabs-custom">
                <?php
                $statuses = ['Semua', 'Selesai', 'Dijadwalkan', 'Diproses', 'Dibatalkan'];

                foreach($statuses as $st):
                    $url = "riwayat.php?filter=" . urlencode($st);

                    if (!empty($tanggal_filter)) {
                        $url .= "&tanggal=" . urlencode($tanggal_filter);
                    }

                    if (!empty($email_filter)) {
                        $url .= "&email=" . urlencode($email_filter);
                    }
                ?>
                    <li class="nav-item">
                        <a class="nav-link-filter <?= $filter == $st ? 'active' : ''; ?>" href="<?= $url; ?>">
                            <?= $st; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="date-filter-box">
                <form action="riwayat.php" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">

                    <?php if (!empty($email_filter)): ?>
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email_filter); ?>">
                    <?php endif; ?>

                    <input type="date" name="tanggal" class="date-input" value="<?= htmlspecialchars($tanggal_filter); ?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        <div class="booking-list">
            <?php if(empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 40px;"></i>
                    <p class="mt-3 text-muted">
                        <?php if ($search_email_required): ?>
                            Masukkan email di atas untuk melihat riwayat booking Anda.
                        <?php else: ?>
                            Tidak ada riwayat booking.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach($bookings as $row): ?>
                    <div class="booking-item">
                        <div class="icon-box">
                            <i class="bi <?= (strpos($row['layanan'] ?? '', 'Cuci') !== false) ? 'bi-snow' : ((strpos($row['layanan'] ?? '', 'Freon') !== false) ? 'bi-droplet-fill' : 'bi-tools'); ?>"></i>
                        </div>

                        <div class="booking-info">
                            <div class="booking-name"><?= htmlspecialchars($row['layanan'] ?? '-'); ?></div>

                            <div class="booking-details">
                                <div class="detail-item">
                                    <i class="bi bi-calendar3"></i>
                                    <?= safeDate($row['tanggal'] ?? null); ?>
                                </div>

                                <div class="detail-item ms-3">
                                    <i class="bi bi-clock"></i>
                                    <?= htmlspecialchars($row['waktu'] ?? '-'); ?>
                                </div>

                                <div class="detail-item ms-3">
                                    <i class="bi bi-geo-alt"></i>
                                    <?php
                                        $alamat = $row['alamat'] ?? '-';
                                        echo htmlspecialchars(strlen($alamat) > 45 ? substr($alamat, 0, 45) . '...' : $alamat);
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="text-end me-4">
                            <div class="fw-bold text-primary mb-1">
                                Rp <?= formatHarga($row['harga'] ?? 0); ?>
                            </div>

                            <span class="badge-custom <?= getStatusClass($row['status'] ?? 'Menunggu'); ?>">
                                <?= htmlspecialchars(getStatusLabel($row['status'] ?? 'Menunggu')); ?>
                            </span>
                        </div>

                        <div class="action">
                            <a href="detail_riwayat.php?id=<?= urlencode($row['id']); ?><?= !empty($email_filter) ? '&email=' . urlencode($email_filter) : ''; ?>" class="btn-detail">
                                Detail <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    <?php if (isset($_SESSION['update_success'])): ?>
        Swal.fire({
            title: "Berhasil!",
            text: "Perubahan berhasil disimpan!",
            icon: "success",
            confirmButtonColor: "#2563EB"
        });
        <?php unset($_SESSION['update_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['cancel_success'])): ?>
        Swal.fire({
            title: "Dibatalkan",
            text: "Booking berhasil dibatalkan.",
            icon: "warning",
            confirmButtonColor: "#DC2626"
        });
        <?php unset($_SESSION['cancel_success']); ?>
    <?php endif; ?>
</script>

</body>
</html>