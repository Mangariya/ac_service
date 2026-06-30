<?php
session_start();
include '../config/database.php';

$user = $_SESSION['user'] ?? null;
$user_id = $user['id'] ?? null;

if (!$user_id) {
    header("Location: ../auth/login.php");
    exit;
}

$booking_id = $_GET['booking_id'] ?? '';
if (empty($booking_id)) {
    header("Location: riwayat.php");
    exit;
}

// Cek booking valid & milik user ini & statusnya Selesai
$stmt = $conn->prepare("SELECT b.*, u.nama AS nama_teknisi, u.spesialisasi, u.wilayah 
    FROM bookings b
    LEFT JOIN users u ON b.teknisi_id = u.id
    WHERE b.id = ? AND b.user_id = ? AND b.status = 'Selesai'");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: riwayat.php");
    exit;
}

// Cek apakah sudah pernah memberi rating untuk booking ini
$stmt_cek = $conn->prepare("SELECT id FROM ratings WHERE booking_id = ?");
$stmt_cek->execute([$booking_id]);
$sudah_rating = $stmt_cek->fetch();

if ($sudah_rating) {
    $_SESSION['info_rating'] = 'Anda sudah memberikan rating untuk booking ini.';
    header("Location: riwayat.php");
    exit;
}

// Ambil rata-rata rating teknisi untuk ditampilkan
$avg_stmt = $conn->prepare("SELECT AVG(bintang)::numeric(3,1) as avg_rating, COUNT(*) as total FROM ratings WHERE teknisi_id = ?");
$avg_stmt->execute([$booking['teknisi_id']]);
$avg_data = $avg_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Rating - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #EAF6FF 0%, #F0F9FF 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 20px; }

        .rating-card {
            background: white;
            border-radius: 28px;
            padding: 45px 40px;
            max-width: 540px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.12);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6B7280;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 30px;
            transition: 0.2s;
        }
        .back-link:hover { color: #2563EB; }

        .teknisi-info {
            display: flex;
            align-items: center;
            gap: 18px;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .teknisi-avatar {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2563EB, #60A5FA);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
            flex-shrink: 0;
        }

        .teknisi-name { font-weight: 700; font-size: 17px; color: #111827; margin-bottom: 3px; }
        .teknisi-detail { font-size: 13px; color: #6B7280; }
        .teknisi-layanan { 
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            background: white; 
            color: #2563EB; 
            font-size: 12px; 
            font-weight: 700; 
            padding: 4px 10px; 
            border-radius: 8px; 
            margin-top: 5px;
        }

        .section-label {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 12px;
        }

        /* Star Rating UI */
        .star-group {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .star-group input[type="radio"] { display: none; }

        .star-group label {
            font-size: 44px;
            color: #D1D5DB;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .star-group input[type="radio"]:checked ~ label,
        .star-group label:hover,
        .star-group label:hover ~ label {
            color: #F59E0B;
            transform: scale(1.1);
        }

        /* Reverse order trick for CSS-only star rating */
        .star-group { flex-direction: row-reverse; justify-content: center; }
        .star-group label:hover,
        .star-group label:hover ~ label { color: #F59E0B; transform: scale(1.15); }
        .star-group input:checked ~ label { color: #F59E0B; }

        .rating-desc {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #6B7280;
            margin-bottom: 25px;
            min-height: 22px;
            transition: 0.2s;
        }

        .form-control {
            border-radius: 14px;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            font-size: 14px;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            outline: none;
        }

        .char-count { font-size: 12px; color: #9CA3AF; text-align: right; margin-top: 5px; }

        .btn-submit-rating {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563EB, #3B82F6);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 25px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-submit-rating:hover {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
        }

        .btn-submit-rating:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .page-title { font-size: 26px; font-weight: 800; color: #111827; margin-bottom: 5px; }
        .page-sub { font-size: 14px; color: #6B7280; margin-bottom: 30px; }

        .existing-rating {
            text-align: center;
            padding: 12px;
            background: #F0FDF4;
            border-radius: 12px;
            border: 1px solid #BBF7D0;
            margin-bottom: 20px;
            font-size: 13px;
            color: #166534;
            font-weight: 600;
        }

        .star-display { color: #F59E0B; font-size: 16px; }
    </style>
</head>
<body>

<div class="rating-card">
    <a href="riwayat.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
    </a>

    <div class="page-title">⭐ Beri Penilaian</div>
    <div class="page-sub">Bagikan pengalaman Anda dengan teknisi ini</div>

    <!-- Info Teknisi -->
    <div class="teknisi-info">
        <div class="teknisi-avatar">
            <i class="bi bi-person-badge"></i>
        </div>
        <div>
            <div class="teknisi-name"><?= htmlspecialchars($booking['nama_teknisi'] ?? 'Teknisi') ?></div>
            <div class="teknisi-detail">
                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($booking['wilayah'] ?? '-') ?>
            </div>
            <div class="teknisi-layanan">
                <i class="bi bi-tools"></i>
                <?= htmlspecialchars($booking['layanan'] ?? '-') ?>
            </div>
        </div>
    </div>

    <?php if ($avg_data['total'] > 0): ?>
    <div class="existing-rating">
        Rating teknisi saat ini:
        <span class="star-display">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star<?= $i <= round($avg_data['avg_rating']) ? '-fill' : '' ?>"></i>
            <?php endfor; ?>
        </span>
        <strong><?= $avg_data['avg_rating'] ?>/5</strong>
        dari <?= $avg_data['total'] ?> ulasan
    </div>
    <?php endif; ?>

    <!-- Form Rating -->
    <form method="POST" action="proses_rating.php" id="ratingForm">
        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_id) ?>">
        <input type="hidden" name="teknisi_id" value="<?= htmlspecialchars($booking['teknisi_id'] ?? '') ?>">
        <input type="hidden" name="layanan" value="<?= htmlspecialchars($booking['layanan'] ?? '') ?>">

        <div class="section-label">Pilih Rating</div>
        <div class="star-group" id="starGroup">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="bintang" id="star<?= $i ?>" value="<?= $i ?>">
                <label for="star<?= $i ?>" title="<?= $i ?> bintang">&#9733;</label>
            <?php endfor; ?>
        </div>

        <div class="rating-desc" id="ratingDesc">Pilih bintang di atas</div>

        <div class="mb-2">
            <div class="section-label">Tulis Ulasan (Opsional)</div>
            <textarea 
                name="komentar" 
                class="form-control" 
                rows="4" 
                placeholder="Ceritakan pengalaman Anda... Apakah teknisi datang tepat waktu? Apakah masalah AC terselesaikan dengan baik?"
                maxlength="500"
                id="komentarArea"
                oninput="document.getElementById('charCount').innerText = this.value.length + '/500'"
            ></textarea>
            <div class="char-count" id="charCount">0/500</div>
        </div>

        <button type="submit" class="btn-submit-rating" id="submitBtn" disabled>
            <i class="bi bi-send-fill me-2"></i> Kirim Ulasan
        </button>
    </form>
</div>

<script>
    const descMap = {
        1: '😞 Sangat Buruk — Tidak memuaskan sama sekali',
        2: '😐 Kurang Memuaskan — Ada banyak yang perlu diperbaiki',
        3: '🙂 Cukup Baik — Lumayan, tapi bisa lebih baik',
        4: '😊 Memuaskan — Teknisi bekerja dengan baik',
        5: '🤩 Luar Biasa! — Sangat puas dengan layanannya!'
    };

    const radios = document.querySelectorAll('input[name="bintang"]');
    const desc = document.getElementById('ratingDesc');
    const btn = document.getElementById('submitBtn');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            desc.innerText = descMap[this.value] || '';
            btn.disabled = false;
        });
    });

    // Validasi form sebelum submit
    document.getElementById('ratingForm').addEventListener('submit', function(e) {
        const selected = document.querySelector('input[name="bintang"]:checked');
        if (!selected) {
            e.preventDefault();
            alert('Silakan pilih rating bintang terlebih dahulu.');
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
