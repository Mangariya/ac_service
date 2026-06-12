<?php
session_start();

// Cek login
if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

// Menangkap data dari $_POST atau menggunakan default (simulasi)
$layanan = $_POST['layanan'] ?? 'Cuci AC';
$tanggal = $_POST['tanggal'] ?? '25 Mei 2024';
$waktu   = $_POST['waktu'] ?? '10:00 - 12:00';
$alamat  = $_POST['alamat'] ?? 'Jl. Melati No. 10, Denpasar, Bali';

// --- TIPS KE-3: LOGIKA PENENTUAN ESTIMASI HARGA ---
$estimasi_harga = 0;
if ($layanan == 'Cuci AC') {
    $estimasi_harga = 75000;
} elseif ($layanan == 'Perbaikan AC') {
    $estimasi_harga = 150000;
} elseif ($layanan == 'Isi Freon') { // Tambahkan baris ini
    $estimasi_harga = 200000;         // Sesuaikan harganya
} else {
    $estimasi_harga = 50000;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Booking - AC Service</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #EAF4FF; min-height: 100vh; }
        .navbar-custom { background: white; padding: 15px 50px; border-bottom: 1px solid #E5E7EB; }
        .navbar-brand { font-weight: 800; color: #111827; display: flex; align-items: center; gap: 10px; }
        .nav-link { font-weight: 600; color: #6B7280; margin: 0 10px; }
        .nav-link.active { color: #2563EB !important; }
        .user-profile { display: flex; align-items: center; gap: 10px; color: #4B5563; font-size: 14px; }
        .user-avatar { width: 40px; height: 40px; background: #F3F4F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; color: #2563EB; }
        .main-content { display: flex; justify-content: center; align-items: center; padding-top: 80px; }
        .confirmation-card { background: white; width: 100%; max-width: 650px; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .card-title { font-weight: 700; font-size: 22px; color: #111827; margin-bottom: 30px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 15px; }
        .info-label { color: #6B7280; }
        .info-value { font-weight: 600; color: #111827; text-align: right; max-width: 300px; }
        
        /* Highlight Harga */
        .price-row { border-top: 2px dashed #E5E7EB; padding-top: 20px; margin-top: 10px; }
        .price-value { color: #2563EB; font-size: 20px; font-weight: 800; }

        .alert-info-custom { background-color: #EFF6FF; border: none; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; color: #1E40AF; font-size: 14px; margin-top: 20px; margin-bottom: 30px; }
        .btn-back { border: 1px solid #D1D5DB; background: white; color: #2563EB; font-weight: 600; padding: 12px 0; border-radius: 10px; width: 48%; transition: 0.3s; }
        .btn-confirm { background: #2563EB; color: white; font-weight: 600; padding: 12px 0; border-radius: 10px; width: 48%; border: none; transition: 0.3s; cursor: pointer; }
        
        .swal2-popup { border-radius: 20px !important; padding: 2em !important; }
        .swal-text-left { text-align: left !important; background: #F9FAFB; border-radius: 12px; padding: 15px; margin-top: 15px; }
        .swal-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .swal-label { color: #6B7280; font-weight: 500; }
        .swal-value { color: #111827; font-weight: 600; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-snow2 text-primary"></i> AC SERVICE</a>
            <div class="collapse navbar-collapse justify-content-center">
                <div class="navbar-nav">
                    <a class="nav-link" href="home.php">BERANDA</a>
                    <a class="nav-link active" href="booking.php">BOOKING</a>
                    <a class="nav-link" href="riwayat.php">RIWAYAT</a>
                </div>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><i class="bi bi-person"></i></div>
                <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pelanggan') ?></span>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <div class="confirmation-card">
            <h4 class="card-title">Ringkasan Booking</h4>

            <div class="info-row">
                <span class="info-label">Layanan</span>
                <span class="info-value"><?= htmlspecialchars($layanan) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span class="info-value"><?= htmlspecialchars($tanggal) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Waktu</span>
                <span class="info-value"><?= htmlspecialchars($waktu) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Alamat</span>
                <span class="info-value"><?= htmlspecialchars($alamat) ?></span>
            </div>

            <div class="info-row price-row">
                <span class="info-label" style="font-weight:700; color:#111827;">Total Biaya</span>
                <span class="price-value">Rp <?= number_format($estimasi_harga, 0, ',', '.') ?></span>
            </div>

            <div class="alert-info-custom">
                <i class="bi bi-info-circle-fill"></i>
                <span>Pastikan semua data sudah benar sebelum melakukan konfirmasi.</span>
            </div>

            <div class="d-flex justify-content-between">
                <button onclick="history.back()" class="btn-back">Kembali</button>
                <button type="button" id="btnKonfirmasi" class="btn-confirm">Konfirmasi Booking</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btnKonfirmasi').addEventListener('click', function() {
            const formData = new FormData();
            formData.append('layanan', '<?= $layanan ?>');
            formData.append('tanggal', '<?= $tanggal ?>');
            formData.append('waktu', '<?= $waktu ?>');
            formData.append('alamat', '<?= $alamat ?>');

            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang menyimpan data booking Anda',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('proses-simpan-db.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '<span style="font-weight:700;">Booking Berhasil!</span>',
                        html: `
                            <p style="color:#6B7280; font-size:14px; margin-bottom: 20px;">Terima kasih! Teknisi kami akan datang sesuai jadwal.</p>
                            <div class="swal-text-left">
                                <div class="swal-info-row">
                                    <span class="swal-label">Layanan</span>
                                    <span class="swal-value"><?= htmlspecialchars($layanan) ?></span>
                                </div>
                                <div class="swal-info-row">
                                    <span class="swal-label">Total Biaya</span>
                                    <span class="swal-value text-primary" style="font-weight:800;">Rp <?= number_format($estimasi_harga, 0, ',', '.') ?></span>
                                </div>
                                <div class="swal-info-row" style="margin-bottom:0;">
                                    <span class="swal-label">Alamat</span>
                                    <span class="swal-value" style="text-align:right; max-width:60%;"><?= htmlspecialchars($alamat) ?></span>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Lihat Riwayat Saya',
                        confirmButtonColor: '#2563EB'
                    }).then(() => { window.location.href = 'riwayat.php'; });
                } else {
                    Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error' });
                }
            });
        });
    </script>
</body>
</html>