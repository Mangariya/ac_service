<?php
session_start();
include '../config/database.php'; 

// HAPUSKAN PROTEKSI LOGIN AGAR GUEST BISA MASUK
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body{ background:#F5F7FB; }
        
        /* Navbar Styling */
        .navbar-custom{ 
            background:white; padding:18px 50px; border-bottom:2px solid #E5E7EB; 
            box-shadow:0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; 
        }
        .logo{ font-size:24px; font-weight:800; color:#111827 !important; text-decoration:none; display: flex; align-items: center; }
        .menu-navbar{ gap:15px; }
        .menu-navbar .nav-link{ color:#6B7280; font-size:15px; font-weight:600; padding:10px 22px; border-radius:12px; transition:0.3s; }
        .menu-navbar .nav-link:hover{ background:#EFF6FF; color:#2563EB; }
        .active-menu{ background:#2563EB; color:white !important; font-weight:700; }
        
        .user-icon-nav{ width:42px; height:42px; border-radius:50%; background:#DBEAFE; display:flex; align-items:center; justify-content:center; font-size:20px; color:#2563EB; }
        
        /* Tombol Login Navbar */
        .btn-login-nav { background: #2563EB; color: white !important; font-weight: 600; padding: 10px 25px; border-radius: 12px; text-decoration: none; transition: 0.3s; }
        .btn-login-nav:hover { background: #1d4ed8; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }

        /* Hero Section */
        .hero-banner{ 
            position:relative; width:100%; height:400px; 
            background: linear-gradient(135deg, #2563EB 0%, #60A5FA 100%); 
            display:flex; align-items:center; justify-content:center;
        }
        .hero-content{ text-align:center; color:white; }
        .hero-content h1{ font-size:48px; font-weight:800; margin-bottom:15px; }
        .hero-content p{ font-size:18px; opacity:0.95; }

        /* Section Global */
        .section-padding{ padding:80px 70px; }
        .section-title{ text-align:center; margin-bottom:50px; }
        .section-title h2{ font-size:38px; font-weight:700; color:#111827; }

        /* Card Teknisi */
        .service-card{ background:white; border-radius:24px; padding:35px 30px; text-align:center; transition:0.3s; box-shadow:0 5px 20px rgba(0,0,0,0.05); border: none; height: 100%; }
        .service-card:hover{ transform:translateY(-8px); }
        .btn-select{ background:#2563EB; color:white; padding:12px 24px; border-radius:12px; text-decoration:none; display:inline-block; font-weight:600; width: 100%; }

        /* Card Layanan Kami */
        .feature-card{ background:white; border-radius:24px; padding:40px 30px; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,0.05); border: none; height: 100%; }
        .feature-icon{ width:70px; height:70px; background:#EFF6FF; color:#2563EB; border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 25px; font-size:30px; }
        
        /* Benefit Section */
        .benefit-container{ background:#2563EB; border-radius:30px; padding:60px; color:white; }
        .benefit-box i{ font-size:42px; margin-bottom:20px; display: block; }

        @media(max-width:768px){ .navbar-custom{ padding:15px 20px; } .section-padding{ padding:50px 20px; } .hero-content h1{ font-size:32px; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand logo" href="home.php">
            <i class="bi bi-snow2 me-3" style="font-size:35px; color:#2563EB;"></i>
            <span>AC SERVICE</span>
        </a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto menu-navbar">
                <li class="nav-item"><a class="nav-link active-menu" href="home.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="layanan.php">Layanan</a></li>
                <li class="nav-item"><a class="nav-link" href="tentang-kami.php">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php">Bantuan</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
                <?php endif; ?>
            </ul>

            <div class="ms-lg-3 mt-3 mt-lg-0">
                <?php if(isset($_SESSION['user'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none d-flex align-items-center" data-bs-toggle="dropdown" style="gap:10px; font-weight:700; color:#1E293B;">
                            <div class="user-icon-nav"><i class="bi bi-person-fill"></i></div>
                            <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pelanggan') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="riwayat.php"><i class="bi bi-clock-history me-2"></i>Riwayat Booking</a></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-login-nav">Login</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>

<section class="hero-banner">
    <div class="hero-content">
        <h1>Halo, <?= isset($_SESSION['user']) ? explode(' ', $_SESSION['user']['nama'])[0] : 'Pengunjung'; ?>!</h1>
        <p>Butuh layanan service AC profesional? Kami siap membantu dengan teknisi terbaik.</p>
    </div>
</section>

<section class="section-padding" id="daftar-teknisi">
    <div class="section-title">
        <h2>Pilih Teknisi Profesional</h2>
        <p class="text-muted">Temukan teknisi ahli yang siap melayani di lokasi Anda</p>
    </div>
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php
            $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'teknisi' AND status_acc = 'approved'");
            $stmt->execute();
            $teknisi = $stmt->fetchAll();
            foreach($teknisi as $t):
            ?>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="user-icon-nav mx-auto mb-4" style="width:80px; height:80px; font-size:35px;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h4 class="fw-bold"><?= htmlspecialchars($t['nama']) ?></h4>
                    <p class="text-primary fw-600 mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['wilayah']) ?></p>
                    <p class="small text-muted mb-4"><?= htmlspecialchars($t['spesialisasi']) ?></p>
                    <a href="pilih_layanan.php?teknisi_id=<?= $t['id'] ?>" class="btn-select">Pilih Teknisi</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="section-title">
        <h2>Layanan Kami</h2>
        <p class="text-muted">Kami menyediakan berbagai layanan terbaik untuk AC Anda</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-wind"></i></div>
                    <h4 class="fw-bold">Cuci AC</h4>
                    <p class="text-muted">Membersihkan AC secara menyeluruh agar tetap dingin dan hemat listrik.</p>
                    <h5 class="text-primary fw-bold mt-3">Rp 75.000</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-tools"></i></div>
                    <h4 class="fw-bold">Perbaikan AC</h4>
                    <p class="text-muted">Perbaikan berbagai kerusakan AC dengan teknisi profesional dan cepat.</p>
                    <h5 class="text-primary fw-bold mt-3">Rp 150.000</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-gear"></i></div>
                    <h4 class="fw-bold">Isi Freon</h4>
                    <p class="text-muted">Pengisian freon AC agar performa pendingin kembali optimal.</p>
                    <h5 class="text-primary fw-bold mt-3">Rp 200.000</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="benefit-container">
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="benefit-box">
                        <i class="bi bi-shield-check"></i>
                        <h5>Teknisi Profesional</h5>
                        <p class="mb-0 opacity-75">Ditangani langsung oleh teknisi berpengalaman.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="benefit-box">
                        <i class="bi bi-clock-history"></i>
                        <h5>Pelayanan Cepat</h5>
                        <p class="mb-0 opacity-75">Proses booking mudah dan tepat waktu.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="benefit-box">
                        <i class="bi bi-cash-stack"></i>
                        <h5>Harga Terjangkau</h5>
                        <p class="mb-0 opacity-75">Harga transparan dengan kualitas terbaik.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="py-4 text-center text-muted">
    © 2026 AC Service - Platform Perawatan AC Terpercaya
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>