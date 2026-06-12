<?php
session_start();
include '../config/database.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - AC Service</title>
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
        .section-title p{ font-size:18px; color:#6B7280; }

        /* About Section */
        .about-text{ font-size:16px; line-height:1.8; color:#4B5563; }
        .about-section{ display:flex; gap:50px; align-items:center; }
        .about-left{ flex:1; }
        .about-right{ flex:1; }
        .about-icon{ width:80px; height:80px; background:#EFF6FF; color:#2563EB; border-radius:20px; display:flex; align-items:center; justify-content:center; font-size:35px; margin-bottom:20px; }

        /* Stats Section */
        .stat-card{ background:white; border-radius:20px; padding:40px 30px; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
        .stat-number{ font-size:42px; font-weight:800; color:#2563EB; }
        .stat-label{ font-size:16px; color:#6B7280; margin-top:10px; }

        /* Benefit Section */
        .benefit-item{ background:white; border-radius:20px; padding:40px 30px; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,0.05); height:100%; }
        .benefit-icon{ width:70px; height:70px; background:#EFF6FF; color:#2563EB; border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:32px; }
        .benefit-item h5{ font-weight:700; color:#111827; margin-bottom:15px; }
        .benefit-item p{ color:#6B7280; font-size:15px; }

        /* Garansi Section */
        .garansi-container{ background:linear-gradient(135deg, #2563EB 0%, #60A5FA 100%); border-radius:30px; padding:60px; color:white; }
        .garansi-item{ padding:30px; border-left:4px solid rgba(255,255,255,0.3); margin-bottom:25px; }
        .garansi-item h4{ font-weight:700; margin-bottom:10px; }
        .garansi-item p{ opacity:0.9; margin-bottom:0; }

        /* Visi Misi */
        .visi-misi-card{ background:white; border-radius:20px; padding:40px 30px; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
        .visi-misi-card h4{ color:#2563EB; font-weight:700; margin-bottom:15px; }
        .visi-misi-card p{ color:#4B5563; line-height:1.8; }

        /* CTA Button */
        .btn-cta{ background:#2563EB; color:white; padding:15px 40px; border-radius:12px; text-decoration:none; font-weight:600; display:inline-block; transition:0.3s; border:none; cursor:pointer; }
        .btn-cta:hover{ background:#1d4ed8; box-shadow:0 4px 12px rgba(37, 99, 235, 0.2); color:white; }

        @media(max-width:768px){ 
            .navbar-custom{ padding:15px 20px; } 
            .section-padding{ padding:50px 20px; } 
            .about-section{ flex-direction:column; gap:30px; }
            .hero-content h1{ font-size:32px; }
        }
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
                <li class="nav-item"><a class="nav-link" href="home.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="layanan.php">Layanan</a></li>
                <li class="nav-item"><a class="nav-link active-menu" href="tentang-kami.php">Tentang Kami</a></li>
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

<!-- Hero Section -->
<section class="hero-banner">
    <div class="hero-content">
        <h1>Tentang AC Service</h1>
        <p>Layanan Perbaikan AC Profesional Terpercaya Sejak 2018</p>
    </div>
</section>

<!-- Profil Perusahaan -->
<section class="section-padding">
    <div class="section-title">
        <h2>Siapa Kami?</h2>
    </div>
    <div class="container">
        <div class="about-section">
            <div class="about-left">
                <p class="about-text">
                    AC Service adalah platform layanan perbaikan dan perawatan AC terpercaya yang telah melayani ribuan pelanggan di seluruh kota. Kami memahami betapa pentingnya kenyamanan thermal bagi rumah dan kantor Anda, khususnya di iklim tropis.
                </p>
                <p class="about-text mt-3">
                    Dengan pengalaman lebih dari 5 tahun, kami menghubungkan pelanggan dengan teknisi AC bersertifikat profesional yang terlatih dan berpengalaman. Setiap teknisi telah melewati seleksi ketat untuk memastikan kualitas layanan terbaik.
                </p>
                <p class="about-text mt-3">
                    Komitmen kami adalah memberikan solusi AC yang cepat, reliable, dan dengan harga yang transparan tanpa biaya tersembunyi.
                </p>
            </div>
            <div class="about-right">
                <div>
                    <div class="about-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 style="color:#111827; font-weight:700;">Respon Cepat</h5>
                    <p style="color:#6B7280;">Teknisi siap melayani dalam 1-2 jam setelah pemesanan</p>
                </div>
                <div style="margin-top:30px;">
                    <div class="about-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h5 style="color:#111827; font-weight:700;">Terverifikasi</h5>
                    <p style="color:#6B7280;">Semua teknisi telah melalui verifikasi identitas dan sertifikat</p>
                </div>
                <div style="margin-top:30px;">
                    <div class="about-icon">
                        <i class="bi bi-heart"></i>
                    </div>
                    <h5 style="color:#111827; font-weight:700;">Komitmen</h5>
                    <p style="color:#6B7280;">Kepuasan pelanggan adalah prioritas utama kami</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="section-padding bg-white">
    <div class="section-title">
        <h2>Mencapai Kepercayaan Pelanggan</h2>
        <p>Statistik yang membuktikan kualitas layanan kami</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">5000+</div>
                    <div class="stat-label">Pelanggan Puas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">85+</div>
                    <div class="stat-label">Teknisi Bersertifikat</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">15000+</div>
                    <div class="stat-label">Perbaikan Sukses</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">4.9/5</div>
                    <div class="stat-label">Rating Kepuasan</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Visi & Misi -->
<section class="section-padding">
    <div class="section-title">
        <h2>Visi & Misi Kami</h2>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="visi-misi-card">
                    <h4><i class="bi bi-lightbulb me-2"></i>Visi</h4>
                    <p>
                        Menjadi platform layanan AC terdepan yang menghubungkan pelanggan dengan teknisi profesional terpercaya, memberikan solusi pendingin AC yang berkualitas, terjangkau, dan mudah diakses oleh setiap keluarga Indonesia.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="visi-misi-card">
                    <h4><i class="bi bi-target me-2"></i>Misi</h4>
                    <p>
                        Memberikan layanan perbaikan dan perawatan AC berkualitas tinggi dengan respon cepat, harga transparan, dan teknisi bersertifikat profesional. Kami berkomitmen membangun kepercayaan melalui transparansi, kejujuran, dan kepuasan pelanggan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Keunggulan Kami -->
<section class="section-padding bg-white">
    <div class="section-title">
        <h2>Mengapa Memilih AC Service?</h2>
        <p>Kami menawarkan keunggulan yang tidak akan Anda temukan di tempat lain</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <h5>Teknisi Bersertifikat</h5>
                    <p>Semua teknisi kami memiliki sertifikat dari lembaga resmi dan terus mengikuti pelatihan terbaru</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5>Garansi Layanan</h5>
                    <p>Garansi 3 bulan untuk hasil perbaikan dan jaminan uang kembali jika tidak puas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h5>Harga Transparan</h5>
                    <p>Tidak ada biaya tersembunyi, semua harga sudah termasuk service dan spare parts</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h5>Respon Cepat</h5>
                    <p>Booking mudah melalui aplikasi dan teknisi siap datang dalam 1-2 jam</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h5>Jangkauan Luas</h5>
                    <p>Kami melayani di berbagai wilayah kota dengan jaringan teknisi yang tersebar</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <h5>Customer Support 24/7</h5>
                    <p>Tim support kami siap membantu Anda kapan saja untuk menjawab pertanyaan</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Garansi Section -->
<section class="section-padding">
    <div class="section-title">
        <h2 style="color:white;">Jaminan & Garansi Kami</h2>
        <p style="color:rgba(255,255,255,0.9);">Kami memberikan jaminan kepuasan 100% untuk setiap layanan</p>
    </div>
    <div class="container">
        <div class="garansi-container">
            <div class="garansi-item">
                <h4><i class="bi bi-calendar-check me-2"></i>Garansi 3 Bulan</h4>
                <p>Jika AC Anda mengalami masalah yang sama dalam 3 bulan, kami akan perbaiki tanpa biaya tambahan</p>
            </div>
            <div class="garansi-item">
                <h4><i class="bi bi-currency-dollar me-2"></i>Jaminan Uang Kembali 100%</h4>
                <p>Jika Anda tidak puas dengan layanan kami, kami akan mengembalikan pembayaran Anda sepenuhnya</p>
            </div>
            <div class="garansi-item">
                <h4><i class="bi bi-tools me-2"></i>Spare Parts Original</h4>
                <p>Kami hanya menggunakan spare parts original atau setara, dengan garansi resmi dari pabrikan</p>
            </div>
            <div class="garansi-item">
                <h4><i class="bi bi-certificate me-2"></i>Sertifikat Garansi</h4>
                <p>Setiap layanan dilengkapi dengan sertifikat garansi yang sah dan dapat ditukarkan kapan saja</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section-padding bg-white">
    <div class="section-title">
        <h2>Siap Melayani Anda</h2>
        <p>Jangan biarkan AC Anda terus bermasalah, hubungi teknisi profesional kami sekarang</p>
    </div>
    <div class="container text-center">
        <a href="home.php#daftar-teknisi" class="btn-cta">
            <i class="bi bi-search me-2"></i>Cari Teknisi Sekarang
        </a>
    </div>
</section>

<!-- Footer -->
<footer style="background:#111827; color:white; padding:40px; text-align:center;">
    <p style="margin:0;">&copy; 2024 AC Service. Semua hak dilindungi. | Layanan perbaikan AC profesional terpercaya</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
