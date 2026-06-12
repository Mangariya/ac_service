<?php
session_start();
include '../config/database.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan & FAQ - AC Service</title>
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

        /* FAQ Accordion Styling */
        .faq-container{ max-width:900px; margin:0 auto; }
        
        .accordion-faq .accordion-item{
            background:white; border:none; border-radius:16px; margin-bottom:15px;
            box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden;
            transition:0.3s;
        }
        
        .accordion-faq .accordion-item:hover{
            box-shadow:0 6px 20px rgba(37, 99, 235, 0.1);
        }
        
        .accordion-faq .accordion-button{
            background:white; color:#111827; font-weight:700; font-size:16px;
            border:none; padding:20px 25px; transition:0.3s;
        }
        
        .accordion-faq .accordion-button:not(.collapsed){
            background:#EFF6FF; color:#2563EB; box-shadow:none;
        }
        
        .accordion-faq .accordion-button:focus{
            border-color:none; box-shadow:none;
        }
        
        .accordion-faq .accordion-button::after{
            background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%232563EB' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            width:20px; height:20px;
        }
        
        .accordion-faq .accordion-button:not(.collapsed)::after{
            background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%232563EB' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 11l6-6 6 6'/%3e%3c/svg%3e");
        }
        
        .accordion-faq .accordion-body{
            background:white; color:#4B5563; font-size:15px; line-height:1.8;
            padding:20px 25px; border:none;
        }

        .faq-icon{
            width:50px; height:50px; background:#EFF6FF; color:#2563EB;
            border-radius:12px; display:flex; align-items:center; justify-content:center;
            font-size:24px; margin-right:15px;
        }

        .faq-header-item{
            display:flex; align-items:center; flex:1;
        }

        /* Category Tab */
        .faq-category-tabs{
            display:flex; gap:12px; margin-bottom:40px; flex-wrap:wrap; justify-content:center;
        }

        .faq-category-tab{
            padding:10px 20px; border-radius:12px; border:2px solid #E5E7EB;
            background:white; color:#6B7280; font-weight:600; cursor:pointer;
            transition:0.3s; text-decoration:none;
        }

        .faq-category-tab:hover{
            border-color:#2563EB; color:#2563EB;
        }

        .faq-category-tab.active{
            background:#2563EB; border-color:#2563EB; color:white;
        }

        /* Contact CTA */
        .contact-cta{
            background:linear-gradient(135deg, #2563EB 0%, #60A5FA 100%);
            border-radius:20px; padding:50px 40px; color:white; text-align:center;
            margin-top:60px;
        }

        .contact-cta h3{
            font-size:28px; font-weight:800; margin-bottom:15px;
        }

        .contact-cta p{
            font-size:16px; opacity:0.95; margin-bottom:25px;
        }

        .btn-contact{
            background:white; color:#2563EB; padding:12px 35px;
            border-radius:12px; text-decoration:none; font-weight:700;
            display:inline-block; transition:0.3s;
        }

        .btn-contact:hover{
            background:#f0f0f0; transform:translateY(-2px);
        }

        @media(max-width:768px){ 
            .navbar-custom{ padding:15px 20px; } 
            .section-padding{ padding:50px 20px; }
            .hero-content h1{ font-size:32px; }
            .faq-container{ margin:0 auto; }
            .accordion-faq .accordion-button{ font-size:14px; padding:15px 18px; }
            .contact-cta{ padding:30px 20px; }
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
                <li class="nav-item"><a class="nav-link" href="tentang-kami.php">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link active-menu" href="faq.php">Bantuan</a></li>
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
        <h1>Bantuan & FAQ</h1>
        <p>Temukan Jawaban atas Pertanyaan Umum Anda</p>
    </div>
</section>

<!-- FAQ Section -->
<section class="section-padding">
    <div class="section-title">
        <h2>Pertanyaan yang Sering Diajukan</h2>
        <p>Kami telah mengumpulkan pertanyaan paling umum dari pelanggan kami. Jika Anda tidak menemukan jawaban, jangan ragu untuk menghubungi kami.</p>
    </div>

    <div class="faq-container">
        <!-- Accordion FAQ -->
        <div class="accordion accordion-faq" id="faqAccordion">
            
            <!-- KATEGORI: UMUM -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-question-circle me-2"></i>Pertanyaan Umum
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        <i class="bi bi-info-circle me-3" style="font-size:20px;"></i>
                        Apa itu AC Service?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        AC Service adalah platform layanan perbaikan dan perawatan AC profesional yang menghubungkan pelanggan dengan teknisi bersertifikat terpercaya. Kami menyediakan layanan perbaikan AC, pembersihan, pengisian freon, dan perawatan rutin dengan harga transparan dan layanan berkualitas tinggi.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        <i class="bi bi-geo-alt me-3" style="font-size:20px;"></i>
                        Jangkauan wilayah layanan kami?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        AC Service melayani seluruh wilayah kota. Anda dapat melihat apakah alamat Anda terjangkau dengan memilih teknisi di platform kami. Untuk wilayah yang belum terjangkau, silakan hubungi admin untuk informasi lebih lanjut.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        <i class="bi bi-clock-history me-3" style="font-size:20px;"></i>
                        Berapa lama waktu tunggu teknisi tiba?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Waktu respons teknisi biasanya berkisar 30 menit - 2 jam setelah Anda melakukan booking, tergantung lokasi dan ketersediaan teknisi. Untuk area yang ramai, waktu tunggu mungkin lebih lama. Anda akan menerima notifikasi real-time tentang status teknisi.
                    </div>
                </div>
            </div>

            <!-- KATEGORI: BOOKING & LAYANAN -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-calendar-check me-2"></i>Booking & Layanan
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        <i class="bi bi-bookmark me-3" style="font-size:20px;"></i>
                        Bagaimana cara melakukan booking?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Langkah-langkah booking:</strong><br>
                        1. Login atau buat akun baru<br>
                        2. Klik menu "Layanan" dan pilih teknisi favorit Anda<br>
                        3. Pilih jenis layanan (cuci, freon, maintenance, dll)<br>
                        4. Atur jadwal dan lokasi (gunakan map untuk akurasi)<br>
                        5. Review biaya dan klik "Konfirmasi"<br>
                        6. Tunggu konfirmasi dari teknisi<br><br>
                        Booking Anda akan langsung masuk ke "Riwayat" untuk dipantau.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        <i class="bi bi-tools me-3" style="font-size:20px;"></i>
                        Layanan apa saja yang tersedia?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Layanan yang kami tawarkan:</strong><br>
                        • <strong>Pembersihan AC</strong> - Membersihkan filter, indoor, outdoor unit<br>
                        • <strong>Pengisian Freon</strong> - Untuk AC yang sudah berkurang cooling-nya<br>
                        • <strong>Perbaikan</strong> - Memperbaiki AC yang rusak/error<br>
                        • <strong>Maintenance Rutin</strong> - Perawatan berkala untuk performa optimal<br>
                        • <strong>Instalasi Baru</strong> - Pemasangan AC baru (hubungi admin untuk detail)<br><br>
                        Setiap layanan disesuaikan dengan kebutuhan AC Anda.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                        <i class="bi bi-list-check me-3" style="font-size:20px;"></i>
                        Apa yang harus saya siapkan sebelum teknisi datang?
                    </button>
                </h2>
                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Persiapan sebelum teknisi tiba:</strong><br>
                        ✓ Pastikan AC dapat diakses dengan mudah<br>
                        ✓ Matikan AC minimal 30 menit sebelum teknisi datang<br>
                        ✓ Siapkan area sekitar outdoor/indoor unit agar tidak terhalang<br>
                        ✓ Siapkan tempat untuk teknisi menaruh peralatan<br>
                        ✓ Persiapkan air minum untuk teknisi<br>
                        ✓ Catat jenis/model AC Anda jika memungkinkan<br><br>
                        Hal ini membantu proses servis menjadi lebih cepat dan efisien.
                    </div>
                </div>
            </div>

            <!-- KATEGORI: PEMBAYARAN -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-credit-card me-2"></i>Pembayaran & Biaya
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                        <i class="bi bi-wallet2 me-3" style="font-size:20px;"></i>
                        Bagaimana sistem pembayaran?
                    </button>
                </h2>
                <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Opsi Pembayaran:</strong><br>
                        • <strong>Transfer Bank</strong> - Sebelum teknisi datang (rekening akan diberikan saat booking)<br>
                        • <strong>Tunai</strong> - Bayar langsung ke teknisi saat servis selesai<br>
                        • <strong>E-Wallet</strong> - Transfer via GCash/Dana (jika tersedia)<br><br>
                        <strong>Untuk booking, Anda perlu membayar uang muka</strong> (DP) untuk mengamankan jadwal teknisi. DP akan dikurangi dari total biaya.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                        <i class="bi bi-tag me-3" style="font-size:20px;"></i>
                        Berapa harga layanan?
                    </button>
                </h2>
                <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Harga layanan bervariasi tergantung:<br>
                        • Jenis layanan yang dipilih<br>
                        • Tipe AC (split, window, central)<br>
                        • Lokasi (biaya perjalanan teknisi)<br>
                        • Kompleksitas masalah AC<br><br>
                        <strong>Harga transparan</strong> - Semua biaya akan ditampilkan di layar sebelum Anda mengkonfirmasi booking. Tidak ada biaya tersembunyi. Jika ada perbaikan tambahan, teknisi akan memberitahu terlebih dahulu.
                    </div>
                </div>
            </div>

            <!-- KATEGORI: PEMBATALAN -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-x-circle me-2"></i>Pembatalan & Pengembalian Dana
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                        <i class="bi bi-arrow-repeat me-3" style="font-size:20px;"></i>
                        Bagaimana cara membatalkan booking?
                    </button>
                </h2>
                <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Cara membatalkan booking:</strong><br>
                        1. Buka menu "Riwayat" di halaman profil Anda<br>
                        2. Cari booking yang ingin dibatalkan<br>
                        3. Klik tombol "Batalkan" (jika status masih "Menunggu")<br>
                        4. Masukkan alasan pembatalan<br>
                        5. Konfirmasi pembatalan<br><br>
                        <strong>Kebijakan Pembatalan:</strong><br>
                        • Pembatalan ≥24 jam sebelum jadwal: Pengembalian dana 100%<br>
                        • Pembatalan 6-24 jam sebelum jadwal: Pengembalian 50%<br>
                        • Pembatalan <6 jam: Tidak ada pengembalian dana
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                        <i class="bi bi-hand-thumbs-down me-3" style="font-size:20px;"></i>
                        Tidak puas dengan layanan, apa yang bisa saya lakukan?
                    </button>
                </h2>
                <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami menghargai feedback Anda. Jika Anda tidak puas dengan layanan:<br><br>
                        1. <strong>Hubungi Admin</strong> melalui tombol "Hubungi Kami" di bawah<br>
                        2. <strong>Jelaskan masalah</strong> dan sertakan foto/bukti jika perlu<br>
                        3. <strong>Kami akan investigasi</strong> dan memberikan solusi terbaik<br>
                        4. Opsi: Pengembalian dana, perbaikan ulang gratis, atau diskon layanan berikutnya<br><br>
                        Kepuasan Anda adalah prioritas kami. Kami berkomitmen untuk menyelesaikan masalah dalam 24 jam.
                    </div>
                </div>
            </div>

            <!-- KATEGORI: TEKNIS -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-hammer me-2"></i>Masalah Teknis & AC
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                        <i class="bi bi-exclamation-triangle me-3" style="font-size:20px;"></i>
                        AC saya mengeluarkan error E1, E2, E3, dll. Apa maksudnya?
                    </button>
                </h2>
                <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Kode error pada AC memiliki makna spesifik:</strong><br>
                        • <strong>E1/E2</strong> - Sensor indoor/outdoor bermasalah<br>
                        • <strong>E3/E4</strong> - Masalah komunikasi antara indoor & outdoor<br>
                        • <strong>E5/E6</strong> - Tekanan refrigeran abnormal<br>
                        • <strong>E7</strong> - Temperature sensor tidak normal<br><br>
                        Untuk diagnosis pasti, hubungi teknisi kami. Jangan mencoba perbaikan sendiri karena bisa membuat AC rusak lebih parah. Teknisi kami akan mendiagnosis dan memperbaiki dengan tepat.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                        <i class="bi bi-snowflake me-3" style="font-size:20px;"></i>
                        AC saya tidak dingin lagi. Apa penyebabnya?
                    </button>
                </h2>
                <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Penyebab AC tidak dingin:</strong><br>
                        1. <strong>Freon berkurang</strong> - Paling umum. Butuh pengisian freon<br>
                        2. <strong>Filter kotor</strong> - Pembersihan AC bisa mengatasi<br>
                        3. <strong>Outdoor unit rusak</strong> - Perlu perbaikan kompressor<br>
                        4. <strong>Kapasitor mati</strong> - Perlu penggantian<br>
                        5. <strong>Expansion valve bermasalah</strong> - Perlu perbaikan komponen<br><br>
                        Teknisi kami akan mendiagnosis masalah secara akurat dengan tools profesional dan memberikan solusi terbaik.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq13">
                        <i class="bi bi-droplets me-3" style="font-size:20px;"></i>
                        AC saya menetes air. Apa yang harus saya lakukan?
                    </button>
                </h2>
                <div id="faq13" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Penyebab AC menetes dan cara mengatasinya:</strong><br>
                        1. <strong>Saluran pembuangan air tersumbat</strong> - Perlu dibersihkan<br>
                        2. <strong>Slope/kemiringan pipa salah</strong> - Perlu penyesuaian<br>
                        3. <strong>Filter AC kotor</strong> - Pembersihan AC rutin<br>
                        4. <strong>Tekanan freon rendah</strong> - Perlu pengisian freon<br><br>
                        <strong>Sementara menunggu teknisi:</strong><br>
                        • Matikan AC untuk sementara<br>
                        • Letakkan wadah di bawah AC untuk menampung air<br>
                        • Jangan biarkan air menetes ke lantai agar tidak licin<br><br>
                        Booking teknisi sekarang untuk penanganan profesional.
                    </div>
                </div>
            </div>

            <!-- KATEGORI: GARANSI -->
            <h5 class="mt-5 mb-3" style="color:#2563EB; font-weight:700;">
                <i class="bi bi-shield-check me-2"></i>Garansi & Layanan Purna Jual
            </h5>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq14">
                        <i class="bi bi-award me-3" style="font-size:20px;"></i>
                        Apakah ada garansi untuk pekerjaan teknisi?
                    </button>
                </h2>
                <div id="faq14" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>Ya, kami memberikan garansi:</strong><br>
                        • <strong>Garansi 1 bulan</strong> untuk layanan perbaikan (jika AC rusak lagi karena penyebab yang sama, kami perbaiki gratis)<br>
                        • <strong>Garansi 2 minggu</strong> untuk layanan pembersihan<br>
                        • <strong>Garansi 6 bulan</strong> untuk penggantian komponen (kompressor, kapasitor, dll)<br><br>
                        Syarat garansi:<br>
                        • AC dirawat dengan baik sesuai saran teknisi<br>
                        • Tidak ada tindakan perbaikan dari pihak lain<br>
                        • Pembayaran harus sudah lunas<br><br>
                        Jika AC bermasalah lagi dalam masa garansi, hubungi kami dan kami akan ke lokasi Anda gratis.
                    </div>
                </div>
            </div>

            <!-- (Bagian 'Akun & Aplikasi' dihapus sesuai permintaan) -->

        </div>
    </div>

    <!-- Contact CTA -->
    <div class="contact-cta">
        <h3>Pertanyaan Anda Tidak Terjawab?</h3>
        <p>Hubungi tim support kami yang siap membantu Anda 24/7</p>
        <a href="mailto:support@acservice.com" class="btn-contact">
            <i class="bi bi-envelope me-2"></i>Hubungi Kami
        </a>
    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
