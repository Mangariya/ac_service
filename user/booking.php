<?php
session_start();

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

/* AMBIL PARAMETER LAYANAN DARI HOME */
$selectedLayanan = $_GET['layanan'] ?? 'cuci';

$layananNama = 'Cuci AC';
$layananHarga = 'Rp 75.000';

if($selectedLayanan == 'perbaikan'){
    $layananNama = 'Perbaikan AC';
    $layananHarga = 'Rp 150.000';
}

if($selectedLayanan == 'freon'){
    $layananNama = 'Isi Freon';
    $layananHarga = 'Rp 200.000';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Service - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body{ background:#EAF6FF; }

        /* NAVBAR */
        .navbar-custom{ background:white; padding:18px 50px; border-bottom:2px solid #E5E7EB; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .logo{ font-size:24px; font-weight:800; color:#111827 !important; text-decoration:none; }
        .menu-navbar{ gap:15px; }
        .menu-navbar .nav-link{ color:#6B7280; font-size:15px; font-weight:600; padding:10px 22px; border-radius:12px; transition:0.3s; }
        .menu-navbar .nav-link:hover{ background:#EFF6FF; color:#2563EB; }
        .active-menu{ background:#2563EB; color:white !important; font-weight:700; }

        /* USER */
        .dropdown-user{ display:flex; align-items:center; gap:12px; border:none; background:none; }
        .user-icon{ width:48px; height:48px; border-radius:50%; background:#DBEAFE; display:flex; align-items:center; justify-content:center; font-size:24px; color:#2563EB; }
        .user-name{ font-weight:600; color:#374151; }

        /* BOOKING */
        .booking-section{ padding:50px; }
        .booking-container{ background:white; border-radius:24px; padding:35px; box-shadow:0 10px 30px rgba(0,0,0,0.05); }
        .booking-title{ font-size:32px; font-weight:700; color:#111827; margin-bottom:10px; }
        .booking-subtitle{ color:#6B7280; margin-bottom:40px; }

        /* SERVICE CARD */
        .service-card{ border:2px solid #E5E7EB; border-radius:20px; overflow:hidden; cursor:pointer; transition:0.3s; background:white; }
        .service-card:hover{ border-color:#2563EB; transform:translateY(-5px); }
        .service-card.active{ border-color:#2563EB; background:#EFF6FF; }
        .service-card img{ width:100%; height:180px; object-fit:cover; }
        .service-body{ padding:20px; }
        .service-title{ font-size:18px; font-weight:700; margin-bottom:10px; }
        .service-price{ color:#2563EB; font-weight:700; font-size:20px; }

        /* FORM */
        .form-label{ font-weight:600; margin-bottom:10px; color:#111827; }
        .form-control, .form-select{ height:55px; border-radius:14px; border:1px solid #D1D5DB; }
        textarea.form-control{ height:120px; resize:none; }

        /* MAP */
        #map{ width:100%; height:250px; border-radius:18px; overflow:hidden; border:1px solid #D1D5DB; }
        .btn-location{ width:100%; margin-top:12px; border:none; height:50px; border-radius:14px; background:#2563EB; color:white; font-weight:600; transition:0.3s; }
        .btn-location:hover{ background:#1D4ED8; }

        /* SUMMARY */
        .summary-card{ background:#F9FAFB; border-radius:24px; padding:30px; position:sticky; top:20px; }
        .summary-title{ font-size:24px; font-weight:700; margin-bottom:25px; }
        .summary-item{ display:flex; justify-content:space-between; margin-bottom:18px; color:#4B5563; }
        .total-price{ font-size:28px; font-weight:700; color:#2563EB; }
        .btn-booking{ width:100%; height:55px; border:none; border-radius:14px; background:#2563EB; color:white; font-weight:600; margin-top:25px; transition:0.3s; }
        .btn-booking:hover{ background:#1D4ED8; }

        @media(max-width:992px){ .booking-section{ padding:20px; } .summary-card{ margin-top:30px; } }
        
        /* ALERT */
        .alert-message{ border-radius:14px; border:none; margin-bottom:20px; font-weight:500; }
        .alert-success{ background:#D1FAE5; color:#065F46; }
        .alert-error{ background:#FEE2E2; color:#7F1D1D; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center logo" href="#">
            <i class="bi bi-snow2 me-3" style="font-size:45px; color:#2563EB;"></i>
            <span>AC SERVICE</span>
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto menu-navbar">
                <li class="nav-item"><a class="nav-link" href="home.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link active-menu" href="booking.php">Booking</a></li>
                <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
            </ul>
            <div class="dropdown">
                <button class="btn dropdown-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <div class="user-icon"><i class="bi bi-person-fill"></i></div>
                    <span class="user-name"><?= $_SESSION['user']['nama']; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<section class="booking-section">
    <div class="booking-container">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-message alert-error" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <h1 class="booking-title">Booking Service AC</h1>
        <p class="booking-subtitle">Pilih layanan dan jadwalkan service AC Anda dengan mudah.</p>

        <div class="row">
            <div class="col-lg-8">
                <h5 class="mb-4 fw-bold">Pilih Layanan</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="service-card <?= $selectedLayanan == 'cuci' ? 'active' : ''; ?>" data-layanan="Cuci AC" data-harga="Rp 75.000">
                            <img src="../assets/images/reapir-ac.jpg">
                            <div class="service-body">
                                <div class="service-title">Cuci AC</div>
                                <div class="service-price">Rp 75.000</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card <?= $selectedLayanan == 'perbaikan' ? 'active' : ''; ?>" data-layanan="Perbaikan AC" data-harga="Rp 150.000">
                            <img src="../assets/images/reapir-ac.jpg">
                            <div class="service-body">
                                <div class="service-title">Perbaikan AC</div>
                                <div class="service-price">Rp 150.000</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card <?= $selectedLayanan == 'freon' ? 'active' : ''; ?>" data-layanan="Isi Freon" data-harga="Rp 200.000">
                            <img src="../assets/images/reapir-ac.jpg">
                            <div class="service-body">
                                <div class="service-title">Isi Freon</div>
                                <div class="service-price">Rp 200.000</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Pilih Tanggal</label>
                        <input type="date" class="form-control" id="input-tanggal" onchange="updateSummaryDate(this.value)" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Pilih Waktu</label>
                        <select class="form-select" id="input-waktu" onchange="updateSummaryTime(this.value)">
                            <option value="09:00 WIB">09:00 WIB</option>
                            <option value="12:00 WIB">12:00 WIB</option>
                            <option value="15:00 WIB">15:00 WIB</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" placeholder="Masukkan alamat lengkap..." id="alamat"></textarea>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <label class="form-label">Lokasi Peta</label>
                        <div id="map"></div>
                        <button type="button" class="btn-location" id="btnLokasi"><i class="bi bi-geo-alt-fill me-2"></i>Ubah Lokasi Peta</button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea class="form-control" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <h4 class="summary-title">Ringkasan Booking</h4>
                    <div class="summary-item">
                        <span>Layanan</span>
                        <span id="summary-layanan"><?= $layananNama; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Tanggal</span>
                        <span id="summary-tanggal">-</span>
                    </div>
                    <div class="summary-item">
                        <span>Waktu</span>
                        <span id="summary-waktu">09:00 WIB</span>
                    </div>
                    <hr>
                    <div class="summary-item">
                        <span class="fw-bold">Total Harga</span>
                        <span class="total-price" id="summary-harga"><?= $layananHarga; ?></span>
                    </div>
                    <button type="button" class="btn-booking" id="btn-booking">
                        <i class="bi bi-check-circle me-2"></i>Lanjutkan Booking
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
/* UPDATE SUMMARY SAAT INPUT BERUBAH */
function updateSummaryDate(val) {
    document.getElementById('summary-tanggal').innerText = val;
}

function updateSummaryTime(val) {
    document.getElementById('summary-waktu').innerText = val;
}

/* PILIH LAYANAN */
const serviceCards = document.querySelectorAll('.service-card');
const layananText = document.getElementById('summary-layanan');
const hargaText = document.getElementById('summary-harga');

serviceCards.forEach(card => {
    card.addEventListener('click', function(){
        serviceCards.forEach(item => item.classList.remove('active'));
        this.classList.add('active');
        layananText.innerText = this.getAttribute('data-layanan');
        hargaText.innerText = this.getAttribute('data-harga');
    });
});

/* MAP */
let map = L.map('map').setView([-6.200000, 106.816666], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OpenStreetMap' }).addTo(map);
let marker = L.marker([-6.200000, 106.816666], { draggable:true }).addTo(map);

function ambilLokasi(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            function(position){
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            },
            function(){ alert('Gagal mengambil lokasi.'); }
        );
    } else { alert('Browser tidak mendukung GPS.'); }
}

document.getElementById('btnLokasi').addEventListener('click', ambilLokasi);
ambilLokasi();
</script>
<!-- FORM HIDDEN UNTUK KIRIM DATA -->
<script>
// Set minimum date ke hari ini
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('input-tanggal').setAttribute('min', today);
});

/* BUTTON BOOKING -> SESSION */
document.querySelector('.btn-booking').addEventListener('click', function () {

    const layanan = document.getElementById('summary-layanan').innerText;
    const tanggal = document.getElementById('summary-tanggal').innerText;
    const waktu = document.getElementById('summary-waktu').innerText;
    const alamat = document.getElementById('alamat').value.trim();
    const harga = document.getElementById('summary-harga').innerText;

    // VALIDASI
    if (tanggal === '-' || !tanggal) {
        alert('❌ Pilih tanggal terlebih dahulu!');
        return;
    }

    if (!alamat) {
        alert('❌ Masukkan alamat lengkap!');
        return;
    }

    if (alamat.length < 10) {
        alert('❌ Alamat minimal 10 karakter!');
        return;
    }

    if (!layanan || !harga) {
        alert('❌ Pilih layanan terlebih dahulu!');
        return;
    }

    // KIRIM KE SESSION (INI YANG KITA PAKAI)
    fetch('set_session_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            layanan: layanan,
            tanggal: tanggal,
            waktu: waktu,
            alamat: alamat,
            harga: harga
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'set_session_booking.php';
        } else {
            alert('❌ Gagal menyimpan booking!');
        }
    })
    .catch(err => {
        console.error(err);
        alert('❌ Terjadi kesalahan sistem!');
    });

});
</script>

<script>
// Set minimum date ke hari ini
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('input-tanggal').setAttribute('min', today);
});

document.querySelector('.btn-booking').addEventListener('click', function () {

    // ambil data dari halaman
    const layanan = document.getElementById('summary-layanan').innerText;
    const tanggal = document.getElementById('summary-tanggal').innerText;
    const waktu = document.getElementById('summary-waktu').innerText;
    const alamat = document.getElementById('alamat').value.trim();
    const harga = document.getElementById('summary-harga').innerText;

    // validasi lengkap
    if (tanggal === '-') {
        alert('❌ Pilih tanggal terlebih dahulu!');
        return;
    }
    
    if (!alamat) {
        alert('❌ Masukkan alamat lengkap!');
        return;
    }
    
    if (alamat.length < 10) {
        alert('❌ Alamat minimal harus 10 karakter!');
        return;
    }
    
    if (!layanan || !harga) {
        alert('❌ Pilih layanan terlebih dahulu!');
        return;
    }

    // kirim ke form hidden
    document.getElementById('f_layanan').value = layanan;
    document.getElementById('f_tanggal').value = tanggal;
    document.getElementById('f_waktu').value = waktu;
    document.getElementById('f_alamat').value = alamat;
    document.getElementById('f_harga').value = harga;

    // submit ke database
    document.getElementById('bookingForm').submit();
});
</script>
</body>
</html>