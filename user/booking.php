<?php
session_start();
include '../config/database.php';

// Ambil data dari URL
$teknisi_id = $_GET['teknisi_id'] ?? null;
$layanan_url = $_GET['layanan'] ?? null; 
$harga_url   = intval($_GET['harga'] ?? 0);
$is_multi    = isset($_GET['multi']);

$customer_name = $_SESSION['user']['nama'] ?? '';
$customer_phone = $_SESSION['user']['telepon'] ?? '';
$customer_email = $_SESSION['user']['email'] ?? '';

// Ambil Nama Teknisi dari Database
$teknisi_nama = "-";
if($teknisi_id) {
    $stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
    $stmt->execute([$teknisi_id]);
    $t_data = $stmt->fetch();
    $teknisi_nama = $t_data ? $t_data['nama'] : "-";
}

// Hitung harga dari layanan tunggal jika bukan multi
if (!$is_multi && $layanan_url) {
    $harga_map = ['Cuci AC' => 75000, 'Perbaikan AC' => 150000, 'Isi Freon' => 200000];
    $harga_url = $harga_map[$layanan_url] ?? 0;
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
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; color: #333; }
        
        /* Layout */
        .main-card { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 30px; }
        .summary-card { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 25px; position: sticky; top: 20px; }
        
        /* Service Selection */
        .service-option { border: 2px solid #f0f0f0; border-radius: 15px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center; height: 100%; }
        .service-option:hover { border-color: #0d6efd; transform: translateY(-3px); }
        .service-option.active { border-color: #0d6efd; background-color: #f0f7ff; }
        .service-option i { font-size: 1.5rem; margin-bottom: 8px; display: block; }

        /* Map Style */
        #map { height: 300px; width: 100%; border-radius: 15px; border: 1px solid #ddd; z-index: 1; }
        
        .form-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; padding: 12px; border: 1px solid #eee; background-color: #fcfcfc; }
        .form-control:focus { box-shadow: none; border-color: #0d6efd; background-color: #fff; }
        
        .btn-booking { background-color: #2b67f6; border: none; border-radius: 10px; padding: 12px; font-weight: 600; width: 100%; transition: 0.3s; }
        .btn-booking:hover { background-color: #1a54d4; }
    </style>
</head>
<body>

<div class="container py-5">
    <?php if(isset($_SESSION['booking_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['booking_error']); ?>
        </div>
        <?php unset($_SESSION['booking_error']); ?>
    <?php endif; ?>
    <form action="proses_booking.php" method="POST">
    <input type="hidden" name="teknisi_id" value="<?= $teknisi_id ?>">
    <input type="hidden" name="layanan" id="input_layanan" value="<?= htmlspecialchars($layanan_url ?? '') ?>">
    <input type="hidden" name="harga" id="input_harga" value="<?= $harga_url ?>">
    <input type="hidden" name="lat" id="lat">
    <input type="hidden" name="lng" id="lng">
    <input type="hidden" name="booking_step" id="booking_step" value="1">
    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <div class="main-card">
                <h4 class="fw-bold mb-4">Detail Booking</h4>

                <div class="mb-4">
                    <label class="form-label">Pilih Layanan</label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="service-option <?= ($layanan_url == 'Cuci AC') ? 'active' : '' ?>" onclick="setService('Cuci AC', 75000, '45-60 Menit', this)">
                                <i class="bi bi-snow text-primary"></i>
                                <div class="fw-bold small">Cuci AC</div>
                                <div class="text-muted" style="font-size: 12px;">Rp 75.000</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="service-option <?= ($layanan_url == 'Isi Freon') ? 'active' : '' ?>" onclick="setService('Isi Freon', 200000, '30-45 Menit', this)">
                                <i class="bi bi-droplet-fill text-info"></i>
                                <div class="fw-bold small">Isi Freon</div>
                                <div class="text-muted" style="font-size: 12px;">Rp 200.000</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="service-option <?= ($layanan_url == 'Perbaikan AC') ? 'active' : '' ?>" onclick="setService('Perbaikan AC', 150000, '1-3 Jam', this)">
                                <i class="bi bi-tools text-warning"></i>
                                <div class="fw-bold small">Perbaikan AC</div>
                                <div class="text-muted" style="font-size: 12px;">Rp 150.000</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Teknisi yang melayani:</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($teknisi_nama) ?>" readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Kunjungan</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Jam Kedatangan</label>
                        <select name="waktu" class="form-select" required>
                            <option>09:00 WIB</option>
                            <option>13:00 WIB</option>
                            <option>16:00 WIB</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Masukan alamat lengkap Anda..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Titik Lokasi</label>
                        <div id="map"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="findMyLocation()">
                            <i class="bi bi-geo-alt"></i> Sinkronkan Lokasi Anda
                        </button>
                        <p class="text-muted mt-1" style="font-size: 11px;">*Geser pin biru tepat ke lokasi rumah Anda</p>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi Tambahan / Keluhan</label>
                        <textarea name="keluhan" class="form-control" rows="3" placeholder="Contoh: AC tidak dingin sama sekali, ada bunyi berisik..."></textarea>
                    </div>
                </div>

                <div id="step2_section" style="display:none;">
                    <h5 class="fw-bold mb-4 mt-4">Data Diri Pelanggan</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="pelanggan_nama" class="form-control" placeholder="Nama lengkap pelanggan" required value="<?= htmlspecialchars($customer_name) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Pelanggan</label>
                            <input type="email" name="pelanggan_email" class="form-control" placeholder="contoh@email.com" required value="<?= htmlspecialchars($customer_email) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" name="pelanggan_telepon" class="form-control" placeholder="08xxxxxxxxxx" required value="<?= htmlspecialchars($customer_phone) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kota / Kecamatan</label>
                            <input type="text" name="pelanggan_lokasi" class="form-control" placeholder="Contoh: Denpasar / Kuta" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan Pelanggan</label>
                            <textarea name="pelanggan_catatan" class="form-control" rows="3" placeholder="Misalnya: parkiran mudah, kontak via WA..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="summary-card">
                <h5 class="fw-bold mb-4">Ringkasan Pesanan</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Teknisi</span>
                    <span class="fw-bold small text-end"><?= htmlspecialchars($teknisi_nama) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Layanan</span>
                    <span class="fw-bold small text-end" id="sum_layanan" style="max-width:180px;"><?= htmlspecialchars($layanan_url ?? '-') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted small">Estimasi</span>
                    <span class="fw-bold small text-end" id="sum_est">-</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold">Total Harga</span>
                    <span class="h4 fw-bold text-primary mb-0" id="sum_harga">Rp <?= number_format($harga_url, 0, ',', '.') ?></span>
                </div>
                <div class="alert alert-info py-2 mb-3" role="alert" style="font-size: 0.95rem;">
                    Lengkapi layanan, tanggal, alamat, lalu isi data diri pelanggan di langkah berikutnya.
                </div>
                <div class="d-grid gap-2 mb-3">
                    <button type="button" id="btnBackStep" class="btn btn-outline-secondary btn-booking" style="display:none; width:100%;" onclick="goToStep(1)">Kembali</button>
                    <button type="button" id="btnNextStep" class="btn btn-primary btn-booking" style="width:100%;" onclick="goToStep(2)">Lanjutkan ke Data Diri</button>
                </div>
                <button type="submit" id="btnSubmitBooking" class="btn btn-primary btn-booking" style="display:none; width:100%;">Konfirmasi Booking</button>
                <div class="text-center mt-3">
                    <a href="home.php" class="text-muted small text-decoration-none">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>

<script>
    // 1. Logika Pilih Layanan Dinamis
    function setService(nama, harga, est, el) {
        // Update Tampilan Ringkasan
        document.getElementById('sum_layanan').innerText = nama;
        document.getElementById('sum_est').innerText = est;
        document.getElementById('sum_harga').innerText = 'Rp ' + harga.toLocaleString('id-ID');
        
        // Update Input Form (Hidden)
        document.getElementById('input_layanan').value = nama;
        document.getElementById('input_harga').value = harga;

        // Update Class Active
        document.querySelectorAll('.service-option').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
    }

    // 2. Inisialisasi Map (Leaflet)
    var map = L.map('map').setView([-8.670, 115.212], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    var marker = L.marker([-8.670, 115.212], {draggable: true}).addTo(map);

    // Update koordinat saat marker digeser
    marker.on('dragend', function(e) {
        var pos = marker.getLatLng();
        document.getElementById('lat').value = pos.lat;
        document.getElementById('lng').value = pos.lng;
    });

    // 3. Fungsi Sinkronisasi Lokasi (GPS)
    function findMyLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                
                // Pindahkan Marker & Zoom Peta
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 17);
                
                // Simpan ke input
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
            }, function() {
                alert("Gagal mengakses lokasi. Pastikan izin GPS aktif.");
            });
        } else {
            alert("Browser tidak mendukung GPS.");
        }
    }

    function goToStep(step) {
        var step2 = document.getElementById('step2_section');
        var nextBtn = document.getElementById('btnNextStep');
        var backBtn = document.getElementById('btnBackStep');
        var submitBtn = document.getElementById('btnSubmitBooking');
        var bookingStep = document.getElementById('booking_step');

        if (step === 2) {
            var tanggal = document.querySelector('input[name="tanggal"]').value.trim();
            var waktu = document.querySelector('select[name="waktu"]').value.trim();
            var alamat = document.querySelector('textarea[name="alamat"]').value.trim();
            var layanan = document.getElementById('input_layanan').value.trim();

            if (!layanan || !tanggal || !waktu || !alamat) {
                alert('Lengkapi layanan, tanggal, waktu, dan alamat terlebih dahulu.');
                return;
            }

            step2.style.display = 'block';
            nextBtn.style.display = 'none';
            backBtn.style.display = 'inline-block';
            submitBtn.style.display = 'inline-block';
            bookingStep.value = '2';
            document.querySelector('input[name="pelanggan_nama"]').focus();
        } else {
            step2.style.display = 'none';
            nextBtn.style.display = 'inline-block';
            backBtn.style.display = 'none';
            submitBtn.style.display = 'none';
            bookingStep.value = '1';
        }
    }

    // Jalankan auto-select saat halaman load
    window.onload = function() {
        if (document.getElementById('input_layanan').value) {
            var active = document.querySelector('.service-option.active');
            if (active) {
                var nama = active.innerText.split('\n')[0].trim();
                var harga = (nama === 'Cuci AC') ? 75000 : (nama === 'Isi Freon' ? 200000 : 150000);
                var est = (nama === 'Perbaikan AC') ? '1-3 Jam' : (nama === 'Isi Freon' ? '30-45 Menit' : '45-60 Menit');
                setService(nama, harga, est, active);
            }
        } else {
            var firstService = document.querySelector('.service-option');
            if (firstService) {
                setService('Cuci AC', 75000, '45-60 Menit', firstService);
            }
        }
    }
</script>

</body>
</html>