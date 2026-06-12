<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $password = $_POST['password'] ?? '';
        $spesialisasi = trim($_POST['spesialisasi'] ?? '');
        $wilayah = trim($_POST['wilayah'] ?? '');

        if ($nama && $email && $password && substr(strtolower($email), -10) === '@admin.com') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (nama, email, telepon, password, role, status_acc, spesialisasi, wilayah)
                VALUES (?, ?, ?, ?, 'admin', 'approved', ?, ?)
            ");
            $stmt->execute([$nama, $email, $telepon, $password_hash, $spesialisasi, $wilayah]);
            $_SESSION['admin_message'] = 'Admin teknisi berhasil ditambahkan.';
        } else {
            $_SESSION['admin_message'] = 'Email admin wajib memakai akhiran @admin.com.';
        }

        header('Location: kelola_admin.php');
        exit;
    }

    if ($aksi === 'update') {
        $id = $_POST['id'] ?? '';
        $status_acc = $_POST['status_acc'] ?? 'approved';
        $spesialisasi = trim($_POST['spesialisasi'] ?? '');
        $wilayah = trim($_POST['wilayah'] ?? '');

        $stmt = $conn->prepare("
            UPDATE users
            SET status_acc = ?, spesialisasi = ?, wilayah = ?
            WHERE id = ? AND role = 'admin'
        ");
        $stmt->execute([$status_acc, $spesialisasi, $wilayah, $id]);
        $_SESSION['admin_message'] = 'Data admin teknisi berhasil diperbarui.';

        header('Location: kelola_admin.php');
        exit;
    }
}

$stmt = $conn->prepare("
    SELECT id, nama, email, telepon, status_acc, spesialisasi, wilayah, created_at
    FROM users
    WHERE role = 'admin'
    ORDER BY id DESC
");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Admin - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
        body{background:#DFF0FA;color:#0F172A}
        .sidebar{width:300px;height:100vh;background:#fff;position:fixed;left:0;top:0;border-right:1px solid #D8E0E8;padding:32px 26px;display:flex;flex-direction:column}
        .brand{display:flex;align-items:center;gap:18px;margin-bottom:48px}
        .brand i{font-size:58px;color:#38AEEA}.brand span{font-size:26px;font-weight:800;color:#050816}
        .menu{list-style:none;padding:0;margin:0}.menu li{margin-bottom:14px}
        .menu a{display:flex;align-items:center;gap:16px;height:54px;padding:0 22px;border-radius:10px;color:#6B7280;text-decoration:none;font-size:16px;font-weight:500}
        .menu a i{font-size:22px;color:#8A95A8}.menu a.active{background:#118BFA;color:#fff}.menu a.active i{color:#fff}
        .logout{margin-top:auto;padding:18px 22px;display:flex;align-items:center;gap:16px;color:#374151;text-decoration:none;font-size:18px;font-weight:700}
        .logout i{font-size:32px}.main{margin-left:300px;min-height:100vh}
        .topbar{height:90px;background:#fff;box-shadow:0 3px 10px rgba(15,23,42,.18);display:flex;justify-content:space-between;align-items:center;padding:0 38px}
        .top-title h1{font-size:22px;font-weight:800;margin-bottom:4px}.top-title p{margin:0;color:#6B7280;font-size:14px}
        .content{padding:42px}.card-box{background:#fff;border-radius:16px;box-shadow:0 8px 13px rgba(15,23,42,.14);padding:26px}
        .form-control,.form-select{border-radius:10px;padding:11px 13px}.btn-primary{border-radius:10px;font-weight:700}
        .table thead th{background:#F8FAFC;font-size:12px;color:#475569}.table td{vertical-align:middle;font-size:13px}
        .status-pill{border-radius:999px;padding:7px 11px;font-size:12px;font-weight:800;background:#DCFCE7;color:#15803D}
        @media(max-width:992px){.sidebar{position:static;width:100%;height:auto}.main{margin-left:0}.topbar{height:auto;padding:24px}.content{padding:22px}}
    </style>
</head>
<body>
<aside class="sidebar">

    <div class="brand">
        <i class="bi bi-snow2"></i>
        <span>AC SERVICE</span>
    </div>

    <ul class="menu">

        <li>
            <a href="index.php">
                <i class="bi bi-house-door"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="manajemen_layanan.php">
                <i class="bi bi-clipboard2-check"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="reservasi.php">
                <i class="bi bi-journal-bookmark"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php">
                <i class="bi bi-bar-chart"></i>
                Laporan
            </a>
        </li>

        <li>
            <a href="bantuan.php">
                <i class="bi bi-inbox"></i>
                Bantuan
            </a>
        </li>

        <li>
            <a href="kelola_admin.php" class="active">
                <i class="bi bi-person-gear"></i>
                Manajemen Admin
            </a>
        </li>

    </ul>

    <a href="../auth/logout.php" class="logout">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</aside>