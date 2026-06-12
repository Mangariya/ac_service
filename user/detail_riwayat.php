<?php
session_start();
include '../config/database.php';

$user = $_SESSION['user'] ?? null;
$user_id = $user['id'] ?? null;
$email_filter = trim($_GET['email'] ?? '');

if (!$user_id && empty($email_filter)) {
    header("Location: ../auth/login.php");
    exit;
}

$id_booking = $_GET['id'] ?? '';

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare(
        "SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ? AND column_name = ?
        )"
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$id_booking, $user_id]);
} else {
    $has_email_column = columnExists($conn, 'bookings', 'email');
    if ($has_email_column) {
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND email = ?");
        $stmt->execute([$id_booking, $email_filter]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND catatan ILIKE ?");
        $stmt->execute([$id_booking, '%' . $email_filter . '%']);
    }
}

$booking = $stmt->fetch();
$guest_email_only = !$user_id;

if (!$booking) {
    $redirect = 'riwayat.php';
    if (!empty($email_filter)) {
        $redirect .= '?email=' . urlencode($email_filter);
    }
    header("Location: $redirect");
    exit;
}

// 2. PROSES UPDATE (SIMPAN PERUBAHAN)
if (isset($_POST['update_booking'])) {
    if (!$user_id) {
        header('Location: riwayat.php');
        exit;
    }
    $layanan = $_POST['layanan'];
    $tanggal = $_POST['tanggal'];
    $waktu   = $_POST['waktu'];
    $alamat  = $_POST['alamat'];
    $catatan = $_POST['catatan']; 
    $harga   = $_POST['harga_hidden']; 

    $update = $conn->prepare("UPDATE bookings SET layanan = ?, tanggal = ?, waktu = ?, alamat = ?, catatan = ?, harga = ? WHERE id = ?");
    if ($update->execute([$layanan, $tanggal, $waktu, $alamat, $catatan, $harga, $id_booking])) {
        $_SESSION['update_success'] = true;
        header("Location: riwayat.php");
        exit;
    }
}

// 3. PROSES PEMBATALAN
if (isset($_POST['confirm_cancel'])) {
    $cancel = $conn->prepare("UPDATE bookings SET status = 'Dibatalkan' WHERE id = ?");
    if ($cancel->execute([$id_booking])) {
        $_SESSION['cancel_success'] = true;
        header("Location: riwayat.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #EAF6FF; font-family: 'Poppins', sans-serif; }
        .card-edit { background: white; border-radius: 24px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none; }
        .summary-side { background: white; border-radius: 24px; padding: 30px; position: sticky; top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        /* Service Card Styling */
        .service-card-custom {
            border: 2px solid #F3F4F6;
            border-radius: 20px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .btn-check:checked + .service-card-custom {
            border-color: #2563EB;
            background-color: #EFF6FF;
        }
        .service-card-custom:hover { border-color: #2563EB; transform: translateY(-5px); }
        
        .section-title { font-weight: 700; font-size: 20px; margin-bottom: 25px; color: #111827; }
        .info-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .info-icon { width: 45px; height: 45px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2563EB; font-size: 20px; }
        
        .btn-save { background: #2563EB; color: white; border-radius: 14px; padding: 15px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-cancel { background: #FEE2E2; color: #DC2626; border-radius: 14px; padding: 15px; font-weight: 600; width: 100%; border: none; margin-top: 12px; transition: 0.3s; }
        .btn-cancel:hover { background: #FECACA; }
        
        .badge-info-yellow { background: #FFFBEB; color: #92400E; border-radius: 14px; padding: 15px; font-size: 13px; margin-top: 25px; border: 1px solid #FEF3C7; }
        .form-control { border-radius: 12px; padding: 12px; border: 1px solid #D1D5DB; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="mb-4">
        <h2 class="fw-bold">Edit Booking</h2>
        <p class="text-muted">Ubah detail booking sesuai kebutuhan Anda.</p>
    </div>

    <form method="POST" id="mainForm">
        <div class="row">
            <div class="col-lg-8">
                <div class="card-edit mb-4">
                    <div class="section-title">1. Pilih Layanan</div>
                    <div class="row g-3 mb-5">
                        <?php 
                        $services = [
                            ['nama' => 'Cuci AC', 'harga' => '75.000', 'icon' => 'bi-snow', 'desc' => 'Pembersihan unit indoor & outdoor'],
                            ['nama' => 'Isi Freon', 'harga' => '200.000', 'icon' => 'bi-droplet-fill', 'desc' => 'Mengisi ulang freon agar dingin'],
                            ['nama' => 'Perbaikan AC', 'harga' => '150.000', 'icon' => 'bi-tools', 'desc' => 'Diagnosa dan perbaikan kerusakan']
                        ];
                        foreach($services as $s):
                        ?>
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="layanan" id="svc_<?= $s['nama'] ?>" value="<?= $s['nama'] ?>" 
                                   <?= ($booking['layanan'] == $s['nama']) ? 'checked' : '' ?> 
                                   onchange="updateSummary('<?= $s['nama'] ?>', '<?= $s['harga'] ?>', '<?= $s['icon'] ?>')"
                                   <?= ($booking['status'] == 'Dibatalkan') ? 'disabled' : '' ?>>
                            <label class="service-card-custom" for="svc_<?= $s['nama'] ?>">
                                <div class="info-icon mb-3"><i class="<?= $s['icon'] ?>"></i></div>
                                <div class="fw-bold mb-1"><?= $s['nama'] ?></div>
                                <div class="text-muted mb-2" style="font-size: 11px;"><?= $s['desc'] ?></div>
                                <div class="text-primary fw-bold mt-auto small">Mulai Rp <?= $s['harga'] ?></div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="section-title">2. Pilih Jadwal</div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $booking['tanggal'] ?>" required onchange="document.getElementById('sum_tanggal').innerText = this.value" <?= ($booking['status'] == 'Dibatalkan') ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Waktu</label>
                            <select name="waktu" class="form-select form-control" onchange="document.getElementById('sum_waktu').innerText = this.value" <?= ($booking['status'] == 'Dibatalkan') ? 'disabled' : '' ?>>
                                <option <?= $booking['waktu'] == '09:00 WIB' ? 'selected' : '' ?>>09:00 WIB</option>
                                <option <?= $booking['waktu'] == '12:00 WIB' ? 'selected' : '' ?>>12:00 WIB</option>
                                <option <?= $booking['waktu'] == '15:00 WIB' ? 'selected' : '' ?>>15:00 WIB</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title">3. Isi Alamat</div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required oninput="document.getElementById('sum_alamat').innerText = this.value" <?= ($booking['status'] == 'Dibatalkan') ? 'disabled' : '' ?>><?= $booking['alamat'] ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan Tambahan</label>
                        <textarea name="catatan" class="form-control" rows="3" <?= ($booking['status'] == 'Dibatalkan') ? 'disabled' : '' ?>><?php echo trim(strip_tags($booking['catatan'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-side">
                    <h5 class="fw-bold mb-4">Ringkasan Update</h5>
                    
                    <div class="info-item">
                        <div class="info-icon" id="sum_icon"><i class="bi bi-snow"></i></div>
                        <div>
                            <div class="text-muted small">Layanan</div>
                            <div class="fw-bold" id="sum_layanan"><?= $booking['layanan'] ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-calendar-event"></i></div>
                        <div>
                            <div class="text-muted small">Tanggal</div>
                            <div class="fw-bold" id="sum_tanggal"><?= $booking['tanggal'] ?></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                        <div style="max-width: 180px;">
                            <div class="text-muted small">Alamat</div>
                            <div class="fw-bold small" id="sum_alamat"><?= $booking['alamat'] ?></div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Estimasi Biaya</span>
                        <span class="fw-bold text-primary fs-4" id="sum_harga">Rp <?= $booking['harga'] ?></span>
                    </div>
                    <input type="hidden" name="harga_hidden" id="harga_hidden" value="<?= $booking['harga'] ?>">

                    <div class="badge-info-yellow">
                        <i class="bi bi-info-circle me-2"></i> Konfirmasi biaya final akan dilakukan oleh teknisi di lokasi.
                    </div>

                    <div class="mt-4">
                        <?php if (!$guest_email_only && $booking['status'] !== 'Dibatalkan'): ?>
                            <button type="submit" name="update_booking" class="btn-save mb-2">
                                <i class="bi bi-check-circle me-2"></i> Simpan Perubahan
                            </button>
                            
                            <button type="button" id="cancelBtn" class="btn-cancel">
                                <i class="bi bi-x-circle me-2"></i> Batalkan Booking
                            </button>
                        <?php elseif ($guest_email_only && $booking['status'] !== 'Dibatalkan'): ?>
                            <div class="alert alert-info border-0 mb-3" style="border-radius: 14px; font-size: 13px;">
                                Hanya tampilan riwayat. Untuk mengubah booking, silakan login terlebih dahulu.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger border-0 mb-3" style="border-radius: 14px; font-size: 13px;">
                                <i class="bi bi-info-circle me-2"></i> Pesanan ini telah dibatalkan dan tidak dapat diubah kembali.
                            </div>
                        <?php endif; ?>
                        
                        <a href="riwayat.php<?= !empty($email_filter) ? '?email=' . urlencode($email_filter) : '' ?>" class="btn btn-light w-100 mt-3 py-3 fw-bold text-muted" style="border-radius: 14px;">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<form id="cancelForm" method="POST" style="display:none;">
    <input type="hidden" name="confirm_cancel" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Fungsi update ringkasan otomatis
function updateSummary(nama, harga, icon) {
    document.getElementById('sum_layanan').innerText = nama;
    document.getElementById('sum_harga').innerText = 'Rp ' + harga;
    document.getElementById('harga_hidden').value = harga;
    document.getElementById('sum_icon').innerHTML = '<i class="' + icon + '"></i>';
}

// SweetAlert Konfirmasi Pembatalan
document.getElementById('cancelBtn')?.addEventListener('click', function() {
    Swal.fire({
        title: "Batalkan pesanan ini?",
        text: "Pesanan yang dibatalkan tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DC2626",
        cancelButtonColor: "#6B7280",
        confirmButtonText: "Ya, Batalkan!",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('cancelForm').submit();
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>