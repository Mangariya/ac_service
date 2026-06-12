<?php
session_start();
include '../config/database.php';

$teknisi_id = $_GET['teknisi_id'] ?? null;
if(!$teknisi_id){ header("Location: home.php"); exit; }

// Ambil info teknisi
$stmt = $conn->prepare("SELECT nama, wilayah, spesialisasi FROM users WHERE id = ?");
$stmt->execute([$teknisi_id]);
$teknisi = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Layanan - AC Service</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563EB;
            --bg-color: #F8FAFC;
            --text-dark: #1E293B;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-dark);
        }

        /* Hero Header Section */
        .header-section {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            padding: 60px 0 100px;
            color: white;
            border-radius: 0 0 50px 50px;
            margin-bottom: -60px;
        }

        /* Card Styling */
        .service-card { 
            background: white; 
            border-radius: 24px; 
            padding: 40px 30px; 
            text-align: center; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover { 
            transform: translateY(-15px); 
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
        }

        /* Icon Wrapper */
        .icon-box {
            width: 80px;
            height: 80px;
            background: #EFF6FF;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: 0.3s;
        }

        .service-card:hover .icon-box {
            background: var(--primary-color);
            transform: rotate(10deg);
        }

        .service-card:hover .icon-box i {
            color: white !important;
        }

        /* Pricing & Button */
        .price-tag {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 20px 0;
        }

        .btn-book { 
            background: var(--primary-color); 
            color: white; 
            border-radius: 15px; 
            padding: 14px 20px; 
            text-decoration: none; 
            display: block; 
            font-weight: 700;
            transition: 0.3s;
            border: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-book:hover { 
            background: #1D4ED8; 
            color: white;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        }

        .back-link {
            transition: 0.3s;
            font-weight: 600;
            color: #64748B !important;
        }

        .back-link:hover {
            color: var(--primary-color) !important;
            padding-left: -5px;
        }
    </style>
</head>
<body>

<div class="header-section text-center">
    <div class="container">
        <span class="badge bg-white text-primary px-3 py-2 rounded-pill mb-3 fw-bold">PRO TEKNISI</span>
        <h2 class="fw-extrabold display-6">Layanan dari <?= htmlspecialchars($teknisi['nama']) ?></h2>
        <p class="opacity-75">Spesialis: <?= htmlspecialchars($teknisi['spesialisasi'] ?? 'Teknisi Berpengalaman') ?></p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 justify-content-center">
        <div class="col-md-4">
            <div class="service-card">
                <div class="icon-box">
                    <i class="bi bi-wind text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Cuci AC</h4>
                <p class="text-muted small">Membersihkan unit agar udara segar, dingin maksimal, dan hemat pemakaian listrik.</p>
                <div class="price-tag">Rp 75.000</div>
                <a href="booking.php?layanan=Cuci AC&teknisi_id=<?= $teknisi_id ?>" class="btn-book">Pesan Sekarang</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="service-card">
                <div class="icon-box">
                    <i class="bi bi-tools text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Perbaikan AC</h4>
                <p class="text-muted small">Pengecekan mendalam dan perbaikan pada komponen unit yang mengalami kerusakan.</p>
                <div class="price-tag">Rp 150.000</div>
                <a href="booking.php?layanan=Perbaikan AC&teknisi_id=<?= $teknisi_id ?>" class="btn-book">Pesan Sekarang</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="service-card">
                <div class="icon-box">
                    <i class="bi bi-moisture text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Isi Freon</h4>
                <p class="text-muted small">Pengisian ulang gas pendingin untuk mengembalikan performa dingin unit AC Anda.</p>
                <div class="price-tag">Rp 200.000</div>
                <a href="booking.php?layanan=Isi Freon&teknisi_id=<?= $teknisi_id ?>" class="btn-book">Pesan Sekarang</a>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="home.php" class="back-link text-decoration-none">
            <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Daftar Teknisi
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>