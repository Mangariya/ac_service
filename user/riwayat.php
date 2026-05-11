<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

/* AMBIL DATA BOOKING */
$stmt = $conn->prepare("
    SELECT * FROM bookings 
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$user_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Riwayat Booking - AC Service</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>

/* GLOBAL */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#EAF6FF;
}

/* NAVBAR */
.navbar-custom{
    background:white;
    padding:18px 50px;
    border-bottom:2px solid #E5E7EB;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

.logo{
    font-size:24px;
    font-weight:800;
    color:#111827 !important;
    text-decoration:none;
}

.menu-navbar{
    gap:15px;
}

.menu-navbar .nav-link{
    color:#6B7280;
    font-size:15px;
    font-weight:600;
    padding:10px 22px;
    border-radius:12px;
    transition:0.3s;
}

.menu-navbar .nav-link:hover{
    background:#EFF6FF;
    color:#2563EB;
}

.active-menu{
    background:#2563EB;
    color:white !important;
    font-weight:700;
}

/* USER */
.dropdown-user{
    display:flex;
    align-items:center;
    gap:12px;
    border:none;
    background:none;
}

.user-icon{
    width:48px;
    height:48px;
    border-radius:50%;
    background:#DBEAFE;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    color:#2563EB;
}

.user-name{
    font-weight:600;
    color:#374151;
}

/* PAGE */
.container-page{
    padding:50px;
}

.title{
    font-size:32px;
    font-weight:700;
    margin-bottom:30px;
    color:#111827;
}

/* CARD RIWAYAT */
.card-riwayat{
    background:white;
    border-radius:18px;
    padding:20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
    margin-bottom:15px;
    transition:0.3s;
}

.card-riwayat:hover{
    transform:translateY(-3px);
}

.layanan{
    font-size:18px;
    font-weight:700;
    color:#111827;
}

.info{
    font-size:13px;
    color:#6B7280;
}

.status{
    padding:6px 12px;
    border-radius:10px;
    font-size:12px;
    font-weight:600;
}

.menunggu{
    background:#FEF3C7;
    color:#92400E;
}

.diproses{
    background:#DBEAFE;
    color:#1E40AF;
}

.selesai{
    background:#D1FAE5;
    color:#065F46;
}

.price{
    font-weight:700;
    color:#2563EB;
}

.empty{
    text-align:center;
    padding:50px;
    color:#6B7280;
}

</style>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">

<div class="container-fluid">

<a class="navbar-brand logo" href="#">
<i class="bi bi-snow2 me-2" style="color:#2563EB;"></i>
AC SERVICE
</a>

<ul class="navbar-nav mx-auto menu-navbar">
<li class="nav-item"><a class="nav-link" href="home.php">Beranda</a></li>
<li class="nav-item"><a class="nav-link" href="booking.php">Booking</a></li>
<li class="nav-item"><a class="nav-link active-menu" href="riwayat.php">Riwayat</a></li>
</ul>

<div class="dropdown">

<button class="btn dropdown-user dropdown-toggle" data-bs-toggle="dropdown">

<div class="user-icon">
<i class="bi bi-person-fill"></i>
</div>

<span class="user-name">
<?= $_SESSION['user']['nama']; ?>
</span>

</button>

<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
<li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
</ul>

</div>

</div>
</nav>

<!-- CONTENT -->
<div class="container-page">

<h1 class="title">Riwayat Booking</h1>

<?php if(count($data) == 0): ?>
<div class="empty">
    <i class="bi bi-inbox" style="font-size:50px;"></i>
    <p>Belum ada booking</p>
</div>
<?php endif; ?>

<?php foreach($data as $row): ?>

<div class="card-riwayat">

<div class="d-flex justify-content-between align-items-center">

<div>
    <div class="layanan">
        <?= $row['layanan']; ?>
    </div>

    <div class="info">
        <?= $row['tanggal']; ?> | <?= $row['waktu']; ?>
    </div>

    <div class="info">
        <?= $row['alamat']; ?>
    </div>
</div>

<div class="text-end">

<div class="price mb-2">
<?= $row['harga']; ?>
</div>

<?php if($row['status'] === 'Menunggu'): ?>
    <span class="status menunggu">Menunggu</span>

<?php elseif($row['status'] == 'Diproses'): ?>
    <span class="status diproses">Diproses</span>

<?php else: ?>
    <span class="status selesai">Selesai</span>
<?php endif; ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>