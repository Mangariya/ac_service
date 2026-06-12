<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'teknisi'])) {
    header("Location: ../auth/login.php");
    exit;
}

$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan - AC Service</title>

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
            padding: 46px 72px;
        }

        .help-wrapper {
            max-width: 980px;
            margin: 0 auto;
        }

        .help-title {
            text-align: center;
            font-size: 38px;
            font-weight: 800;
            color: #050816;
            margin-bottom: 12px;
        }

        .help-subtitle {
            text-align: center;
            color: #64748B;
            font-weight: 500;
            margin-bottom: 38px;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .help-card {
            background: white;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.16);
        }

        .help-icon {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            background: #E7EDFF;
            color: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 18px;
        }

        .help-card h5 {
            font-weight: 800;
            color: #111827;
            margin-bottom: 10px;
        }

        .help-card p {
            color: #64748B;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
        }

        .step-list {
            margin: 0;
            padding-left: 18px;
            color: #64748B;
            font-size: 14px;
            line-height: 1.9;
        }

        .note-box {
            margin-top: 28px;
            background: #EFF6FF;
            border-left: 5px solid #2563EB;
            border-radius: 12px;
            padding: 20px 24px;
            color: #1E3A8A;
            font-weight: 600;
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

            .help-grid {
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
            <a href="manajemen_layanan.php">
                <i class="bi bi-briefcase"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="reservasi.php">
                <i class="bi bi-calendar-check"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php">
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
            <a href="bantuan.php" class="active">
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
                <h3>Bantuan</h3>
                <p>Panduan teknisi dalam mengelola layanan pelanggan.</p>
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
        <div class="help-wrapper">
            <h2 class="help-title">Panduan Pengelolaan Teknisi</h2>
            <p class="help-subtitle">Ikuti panduan berikut untuk mengelola reservasi, layanan, laporan, dan pekerjaan pelanggan.</p>

            <div class="help-grid">
                <div class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h5>Manajemen Layanan</h5>
                    <p>
                        Halaman ini digunakan untuk melihat data booking pelanggan.
                        Teknisi dapat melihat nama pelanggan, nomor telepon, teknisi yang dipilih, layanan, tanggal, alamat, status, dan tombol WhatsApp untuk menghubungi pelanggan.
                    </p>
                </div>

                <div class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5>Reservasi</h5>
                    <ol class="step-list">
                        <li>Teknisi melihat daftar booking pelanggan.</li>
                        <li>Tekan Accept untuk menerima booking.</li>
                        <li>Status berubah dari Menunggu menjadi Diproses.</li>
                        <li>Tekan Selesai jika pekerjaan sudah selesai.</li>
                        <li>Tekan Batalkan jika booking tidak bisa dilayani.</li>
                    </ol>
                </div>

                <div class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </div>
                    <h5>Laporan</h5>
                    <p>
                        Halaman laporan digunakan untuk melihat jumlah reservasi, pendapatan, pekerjaan selesai, dan booking yang dibatalkan.
                        Admin dapat melihat semua laporan, sedangkan teknisi hanya melihat laporan sesuai data booking miliknya.
                    </p>
                </div>

                <div class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <h5>Menghubungi Pelanggan</h5>
                    <p>
                        Pada halaman manajemen layanan, tekan tombol WhatsApp untuk langsung menghubungi pelanggan.
                        Pastikan nomor telepon pelanggan sudah benar agar komunikasi berjalan lancar.
                    </p>
                </div>
            </div>

            <div class="note-box">
                Catatan: Admin dapat melihat seluruh data teknisi dan booking, sedangkan teknisi hanya dapat melihat data booking yang dipilih untuk dirinya sendiri.
            </div>
        </div>
    </div>
</div>

</body>
</html>