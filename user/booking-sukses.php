<?php
session_start();
if (!isset($_SESSION['last_booking'])) {
    header("Location: booking.php");
    exit;
}
$data = $_SESSION['last_booking'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #EAF6FF; }
        .navbar-custom { background: white; padding: 15px 50px; border-bottom: 2px solid #E5E7EB; }
        .success-card { 
            background: white; border-radius: 20px; padding: 40px; 
            max-width: 600px; margin: 50px auto; text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .check-icon { 
            font-size: 60px; color: #10B981; background: #D1FAE5;
            width: 100px; height: 100px; line-height: 100px;
            border-radius: 50%; margin: 0 auto 20px;
        }
        .info-table { text-align: left; background: #F9FAFB; border-radius: 12px; padding: 20px; margin: 25px 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .label { color: #6B7280; font-weight: 500; }
        .value { color: #111827; font-weight: 600; }
        .btn-history { 
            background: #2563EB; color: white; border: none; width: 100%; 
            padding: 12px; border-radius: 10px; font-weight: 600; text-decoration: none; display: block;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom">
    <div class="container-fluid">
        <span class="fw-bold"><i class="bi bi-snow2 text-primary me-2"></i>AC SERVICE</span>
    </div>
</nav>

<div class="container">
    <div class="success-card">
        <div class="check-icon"><i class="bi bi-check-lg"></i></div>
        <h3 class="fw-bold">Booking Berhasil!</h3>
        <p class="text-muted small">Terima kasih! Booking layanan Anda telah kami terima.<br>Teknisi kami akan datang sesuai jadwal yang dipilih.</p>

        <div class="info-table">
            <div class="info-row">
                <span class="label">ID Booking</span>
                <span class="value"><?= $data['id']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Layanan</span>
                <span class="value"><?= $data['layanan']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Tanggal</span>
                <span class="value"><?= $data['tanggal']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Waktu</span>
                <span class="value"><?= $data['waktu']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Alamat</span>
                <span class="value text-end" style="max-width: 60%;"><?= $data['alamat']; ?></span>
            </div>
        </div>

        <a href="home.php" class="btn-history">Kembali ke Beranda</a>
    </div>
</div>

</body>
</html>