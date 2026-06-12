<?php
session_start();
include '../config/database.php';

// HAPUSKAN PROTEKSI LOGIN AGAR GUEST BISA MASUK COBA LIHAT ALUR & TEKNISI
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'teknisi' AND status_acc = 'approved'");
$stmt->execute();
$daftar_teknisi = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan & Teknisi - AC Service</title>
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

        /* Section Global */
        .section-padding{ padding:60px 70px; }
        .section-title{ text-align:center; margin-bottom:40px; }
        .section-title h2{ font-size:36px; font-weight:700; color:#111827; }

        /* Hero Section */
        .hero-banner{ 
            position:relative; width:100%; height:400px; 
            background: linear-gradient(135deg, #2563EB 0%, #60A5FA 100%); 
            display:flex; align-items:center; justify-content:center;
        }
        .hero-content{ text-align:center; color:white; }
        .hero-content h1{ font-size:48px; font-weight:800; margin-bottom:15px; }
        .hero-content p{ font-size:18px; opacity:0.95; }

        /* Step Alur Card */
        .step-card { border: none; background: white; border-radius: 20px; padding: 30px 25px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.04); height: 100%; transition: 0.3s; }
        .step-card:hover { transform: translateY(-5px); }
        .step-icon { width: 65px; height: 65px; background: #EFF6FF; color: #2563EB; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.6rem; }
        
        /* Card Teknisi */
        .service-card{ background:white; border-radius:24px; padding:35px 30px; text-align:center; transition:0.3s; box-shadow:0 5px 20px rgba(0,0,0,0.05); border: none; height: 100%; }
        .service-card:hover{ transform:translateY(-8px); }
        .btn-select{ background:#2563EB; color:white; padding:12px 24px; border-radius:12px; text-decoration:none; display:inline-block; font-weight:600; width: 100%; transition: 0.3s; }
        .btn-select:hover{ background:#1d4ed8; }

        .problem-card,
        .detail-card{ background:white; border-radius:24px; padding:28px 30px; box-shadow:0 10px 25px rgba(0,0,0,0.05); border:none; height:100%; }
        .problem-card:hover,
        .detail-card:hover{ transform:translateY(-6px); }
        .problem-card .icon-box,
        .detail-card .icon-box{ width:60px; height:60px; border-radius:18px; display:flex; align-items:center; justify-content:center; margin-bottom:18px; font-size:24px; color:#2563EB; background:#EFF6FF; }
        .problem-card h5,
        .detail-card h5{ font-size:20px; font-weight:700; margin-bottom:12px; color:#111827; }
        .problem-card p,
        .detail-card p{ color:#4B5563; line-height:1.8; }
        .detail-card ul{ padding-left:18px; color:#4B5563; line-height:1.8; margin-top:10px; }
        .detail-card ul li{ margin-bottom:10px; }

        @media(max-width:768px){ .navbar-custom{ padding:15px 20px; } .section-padding{ padding:40px 20px; } }
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
                <li class="nav-item"><a class="nav-link active-menu" href="layanan.php">Layanan</a></li>
                <li class="nav-item"><a class="nav-link" href="tentang-kami.php">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php">Bantuan</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
                <?php endif; ?>
            </ul>
            

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
        <h1>Layanan AC Service</h1>
        <p>Pilih Teknisi Profesional Terpercaya untuk Perbaikan AC Anda</p>
    </div>
</section>

<section class="section-padding">
    <div class="section-title">
        <h2>Alur Cara Pemesanan</h2>
        <p class="text-muted">Ikuti 3 langkah mudah berikut untuk mendatangkan teknisi ke rumah Anda</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <h4 class="fw-bold mb-2">1. Pilih Teknisi</h4>
                    <p class="text-muted small mb-0">Lihat dan tentukan teknisi profesional pilihan Anda dari daftar di bawah ini.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <h4 class="fw-bold mb-2">2. Isi Detail & Lokasi</h4>
                    <p class="text-muted small mb-0">Pilih tipe pengerjaan, atur jadwal kunjungan, serta tandai koordinat maps rumah Anda.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <h4 class="fw-bold mb-2">3. Selesai & Konfirmasi</h4>
                    <p class="text-muted small mb-0">Periksa ringkasan biaya transaksi, klik konfirmasi, dan tunggu teknisi tiba.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background:#F8FAFC;">
    <div class="section-title">
        <h2>Kendala Umum AC & Layanan yang Direkomendasikan</h2>
        <p class="text-muted">Contoh kerusakan AC sering ditemui dan layanan AC Service yang paling tepat untuk mengatasinya.</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-droplet-fill"></i></div>
                    <h5>AC Tidak Dingin</h5>
                    <p>Penyebab umum: freon berkurang, filter kotor, atau komponen pendingin bermasalah.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Pengisian Freon & Pembersihan AC</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-water"></i></div>
                    <h5>AC Menetes Air</h5>
                    <p>Penyebab umum: saluran pembuangan tersumbat, pemasangan pipa tidak tepat, atau evaporator kotor.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Pembersihan AC & Pemeriksaan Drainase</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-exclamation-triangle"></i></div>
                    <h5>AC Error / Mati Mendadak</h5>
                    <p>Penyebab umum: kompresor rusak, kapasitor bermasalah, atau gangguan listrik.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Perbaikan AC & Diagnosa Teknis</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-brush"></i></div>
                    <h5>AC Bau Tidak Sedap</h5>
                    <p>Penyebab umum: jamur dan debu menumpuk pada filter atau evaporator.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Pembersihan AC Mendalam</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-lightning-charge"></i></div>
                    <h5>AC Sering Mati Nyala Sendiri</h5>
                    <p>Penyebab umum: sensor suhu rusak atau sirkuit listrik tidak stabil.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Diagnosa Teknis & Perbaikan Komponen</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="problem-card">
                    <div class="icon-box"><i class="bi bi-gear"></i></div>
                    <h5>AC Kurang Dingin Meski Baru Diservis</h5>
                    <p>Penyebab umum: kebocoran freon, ukuran ruangan terlalu besar, atau kompresor lemah.</p>
                    <p><strong>Layanan direkomendasikan:</strong> Pengisian Freon & Penggantian Komponen</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white" style="border-top: 1px solid #E5E7EB;">
    <div class="section-title">
        <h2>Detail Layanan Kami</h2>
        <p class="text-muted">Penjelasan lengkap setiap layanan agar Anda tahu apa yang akan dilakukan teknisi.</p>
    </div>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="icon-box"><i class="bi bi-brush"></i></div>
                    <h5>Pembersihan AC (Cuci AC)</h5>
                    <p>Layanan ini mencakup pembersihan filter, evaporator, dan kondensor untuk menghilangkan debu, kotoran, dan jamur.</p>
                    </ul>

                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="icon-box"><i class="bi bi-droplet-half"></i></div>
                    <h5>Pengisian Freon</h5>
                    <p>Layanan ini ditujukan untuk AC dengan freon berkurang atau tidak stabil, sehingga suhu dingin kembali optimal.</p>
                    <ul>
                        <li>Diagnosa tekanan freon</li>
                        <li>Pengisian ulang sesuai tipe freon AC</li>
                        <li>Pengecekan kebocoran setelah pengisian</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="icon-box"><i class="bi bi-tools"></i></div>
                    <h5>Perbaikan AC</h5>
                    <p>Layanan perbaikan mencakup perbaikan masalah mekanis atau elektrikal pada kompresor, kapasitor, kipas, dan sensor.</p>
                    <ul>
                        <li>Diagnosa error atau gangguan kerja</li>
                        <li>Perbaikan sistem kelistrikan dan komponen</li>
                        <li>Penggantian suku cadang apabila diperlukan</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="icon-box"><i class="bi bi-check-circle"></i></div>
                    <h5>Maintenance Rutin</h5>
                    <p>Layanan ini ideal untuk merawat AC secara berkala agar tetap hemat energi dan berumur panjang.</p>
                    <ul>
                        <li>Pengecekan keseluruhan sistem AC</li>
                        <li>Pembersihan ringan dan pelumasan komponen</li>
                        <li>Rekomendasi tindakan preventif</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white" style="border-top: 1px solid #E5E7EB;">
    <div class="section-title">
        <h2>Pilih Teknisi Profesional</h2>
        <p class="text-muted">Temukan teknisi ahli yang siap melayani di lokasi Anda</p>
    </div>
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php if(!empty($daftar_teknisi)): ?>
                <?php foreach($daftar_teknisi as $t): ?>
                <div class="col-md-4">
                    <div class="service-card">
                        <div class="user-icon-nav mx-auto mb-4" style="width:80px; height:80px; font-size:35px;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <h4 class="fw-bold"><?= htmlspecialchars($t['nama']) ?></h4>
                        <p class="text-primary fw-600 mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['wilayah']) ?></p>
                        <p class="small text-muted mb-4"><?= htmlspecialchars($t['spesialisasi']) ?></p>
                        <a href="booking.php?teknisi_id=<?= $t['id'] ?>" class="btn-select">Pilih Teknisi</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Belum ada data teknisi yang tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="py-4 text-center text-muted">
    © 2026 AC Service - Platform Perawatan AC Terpercaya
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>