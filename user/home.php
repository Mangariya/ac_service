<?php
session_start();

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Beranda - AC Service</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#F5F7FB;
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

.logo span{
    letter-spacing:1px;
}

/* MENU NAVBAR */

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

/* USER DROPDOWN */

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

.dropdown-menu{

    border:none;

    border-radius:16px;

    padding:10px;

    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

.dropdown-item{

    border-radius:10px;

    padding:10px 15px;

    font-weight:500;
}

.dropdown-item:hover{
    background:#F3F4F6;
}

/* HERO */

.hero{
    padding:0;
}

.hero-banner{

    position:relative;

    width:100%;
    height:520px;

    background:
    linear-gradient(
        rgba(255,255,255,0.45),
        rgba(255,255,255,0.45)
    ),
    url('../assets/images/reapir-ac.jpg');

    background-size:cover;
    background-position:center;

    display:flex;
    align-items:center;
}

.hero-content{

    padding-left:100px;

    max-width:550px;
}

.hero-content h1{

    font-size:58px;
    font-weight:800;

    color:#111827;

    margin-bottom:25px;

    line-height:1.2;
}

.hero-content p{

    font-size:20px;

    color:#374151;

    line-height:1.8;

    margin-bottom:35px;
}

.hero-btn{

    background:#2563EB;

    color:white;

    padding:16px 32px;

    border-radius:14px;

    text-decoration:none;

    font-weight:600;

    font-size:17px;

    transition:0.3s;
}

.hero-btn:hover{
    background:#1D4ED8;
}

/* SERVICES */

.services{
    padding:80px 70px;
}

.section-title{
    text-align:center;
    margin-bottom:50px;
}

.section-title h2{
    font-size:38px;
    font-weight:700;
    color:#111827;
}

.section-title p{
    color:#6B7280;
    margin-top:10px;
}

.service-card{
    background:white;
    border-radius:24px;
    padding:35px 30px;
    text-align:center;
    transition:0.3s;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
    height:100%;
}

.service-card:hover{
    transform:translateY(-8px);
}

.service-icon{
    width:80px;
    height:80px;
    background:#DBEAFE;
    color:#2563EB;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 25px;
    font-size:35px;
}

.service-card h4{
    font-weight:700;
    margin-bottom:15px;
}

.service-card p{
    color:#6B7280;
    line-height:1.7;
    margin-bottom:20px;
}

.price{
    color:#2563EB;
    font-size:24px;
    font-weight:700;
    margin-bottom:20px;
}

.btn-service{
    background:#2563EB;
    color:white;
    padding:12px 24px;
    border-radius:12px;
    text-decoration:none;
    display:inline-block;
    font-weight:600;
}

.btn-service:hover{
    background:#1D4ED8;
}

/* BENEFIT */

.benefit{
    padding:0 70px 80px;
}

.benefit-container{
    background:#2563EB;
    border-radius:30px;
    padding:60px;
    color:white;
}

.benefit-box{
    text-align:center;
}

.benefit-box i{
    font-size:42px;
    margin-bottom:20px;
}

.benefit-box h5{
    font-size:22px;
    font-weight:700;
    margin-bottom:10px;
}

.benefit-box p{
    opacity:0.9;
}

/* FOOTER */

.footer{
    background:white;
    padding:30px;
    text-align:center;
    color:#6B7280;
}

/* RESPONSIVE */

@media(max-width:992px){

    .hero-banner{
        height:450px;
    }

    .hero-content{
        padding:40px;
    }

    .hero-content h1{
        font-size:42px;
    }

}

@media(max-width:768px){

    .navbar-custom{
        padding:15px 20px;
    }

    .services,
    .benefit{
        padding-left:20px;
        padding-right:20px;
    }

    .hero-banner{
        height:400px;
    }

    .hero-content{
        padding:25px;
    }

    .hero-content h1{
        font-size:34px;
    }

    .hero-content p{
        font-size:16px;
    }

}

</style>

</head>
<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-custom">

<div class="container-fluid">

<!-- LOGO -->
<a class="navbar-brand d-flex align-items-center logo" href="#">

<i class="bi bi-snow2 me-3" style="font-size:45px; color:#2563EB;"></i>

<span>AC SERVICE</span>

</a>

<!-- TOGGLER -->
<button
class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarNav">

<span class="navbar-toggler-icon"></span>

</button>

<!-- MENU -->
<div class="collapse navbar-collapse" id="navbarNav">

<ul class="navbar-nav mx-auto menu-navbar">

<li class="nav-item">
<a class="nav-link active-menu" href="home.php">
Beranda
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="booking.php">
Booking
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="riwayat.php">
Riwayat
</a>
</li>

</ul>

<!-- USER DROPDOWN -->
<div class="dropdown">

<button
class="btn dropdown-user dropdown-toggle"
type="button"
data-bs-toggle="dropdown"
aria-expanded="false">

<div class="user-icon">
<i class="bi bi-person-fill"></i>
</div>

<span class="user-name">
<?= $_SESSION['user']['nama']; ?>
</span>

</button>

<ul class="dropdown-menu dropdown-menu-end">

<li>
<a class="dropdown-item" href="#">
<i class="bi bi-person-circle me-2"></i>
Profil
</a>
</li>

<li>
<a class="dropdown-item" href="riwayat.php">
<i class="bi bi-clock-history me-2"></i>
Riwayat Booking
</a>
</li>

<li><hr class="dropdown-divider"></li>

<li>
<a class="dropdown-item text-danger" href="../auth/logout.php">
<i class="bi bi-box-arrow-right me-2"></i>
Logout
</a>
</li>

</ul>

</div>

</div>

</div>

</nav>

<!-- HERO -->

<section class="hero">

<div class="hero-banner">

<div class="hero-content">

<h1>
Halo, Pelanggan!
</h1>

<p>
Butuh layanan service AC profesional?
Kami siap membantu dengan teknisi terbaik dan pelayanan cepat langsung ke rumah Anda.
</p>

<a href="booking.php" class="hero-btn">
<i class="bi bi-calendar-check me-2"></i>
Booking Sekarang
</a>

</div>

</div>

</section>

<!-- SERVICES -->

<section class="services">

<div class="section-title">
<h2>Layanan Kami</h2>
<p>Kami menyediakan berbagai layanan terbaik untuk AC Anda</p>
</div>

<div class="row g-4">

<div class="col-md-4">

<div class="service-card">

<div class="service-icon">
<i class="bi bi-wind"></i>
</div>

<h4>Cuci AC</h4>

<p>
Membersihkan AC secara menyeluruh agar tetap dingin dan hemat listrik.
</p>

<div class="price">
Rp 75.000
</div>

<a href="booking.php?layanan=cuci" class="btn-service">
Pesan Sekarang
</a>

</div>

</div>

<div class="col-md-4">

<div class="service-card">

<div class="service-icon">
<i class="bi bi-tools"></i>
</div>

<h4>Perbaikan AC</h4>

<p>
Perbaikan berbagai kerusakan AC dengan teknisi profesional dan cepat.
</p>

<div class="price">
Rp 150.000
</div>

<a href="booking.php?layanan=perbaikan" class="btn-service">
Pesan Sekarang
</a>

</div>

</div>

<div class="col-md-4">

<div class="service-card">

<div class="service-icon">
<i class="bi bi-gear"></i>
</div>

<h4>Isi Freon</h4>

<p>
Pengisian freon AC agar performa pendingin kembali optimal.
</p>

<div class="price">
Rp 200.000
</div>

<a href="booking.php?layanan=freon" class="btn-service">
Pesan Sekarang
</a>

</div>

</div>

</div>

</section>

<!-- BENEFIT -->

<section class="benefit">

<div class="benefit-container">

<div class="row g-4">

<div class="col-md-4">
<div class="benefit-box">
<i class="bi bi-shield-check"></i>
<h5>Teknisi Profesional</h5>
<p>Ditangani langsung oleh teknisi berpengalaman dan terpercaya.</p>
</div>
</div>

<div class="col-md-4">
<div class="benefit-box">
<i class="bi bi-clock-history"></i>
<h5>Pelayanan Cepat</h5>
<p>Proses booking mudah dan teknisi datang tepat waktu.</p>
</div>
</div>

<div class="col-md-4">
<div class="benefit-box">
<i class="bi bi-cash-stack"></i>
<h5>Harga Terjangkau</h5>
<p>Harga transparan dengan kualitas pelayanan terbaik.</p>
</div>
</div>

</div>

</div>

</section>

<!-- FOOTER -->

<footer class="footer">
© 2026 AC Service. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>