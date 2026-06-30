<?php
session_start();
include '../config/database.php';

$teknisi_id = $_GET['teknisi_id'] ?? null;
if (!$teknisi_id) { header("Location: layanan.php"); exit; }

// Ambil info teknisi lengkap
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'teknisi'");
$stmt->execute([$teknisi_id]);
$teknisi = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$teknisi) { header("Location: layanan.php"); exit; }

// Rata-rata rating & total ulasan teknisi ini
$stmt_avg = $conn->prepare("
    SELECT ROUND(AVG(bintang)::numeric, 1) as avg_rating, COUNT(*) as total_review
    FROM ratings WHERE teknisi_id = ?
");
$stmt_avg->execute([$teknisi_id]);
$avg_data = $stmt_avg->fetch(PDO::FETCH_ASSOC);
$avg_rating = floatval($avg_data['avg_rating'] ?? 0);
$total_review = intval($avg_data['total_review'] ?? 0);

// Distribusi bintang (1-5)
$dist_data = [];
for ($b = 5; $b >= 1; $b--) {
    $stmt_dist = $conn->prepare("SELECT COUNT(*) FROM ratings WHERE teknisi_id = ? AND bintang = ?");
    $stmt_dist->execute([$teknisi_id, $b]);
    $dist_data[$b] = intval($stmt_dist->fetchColumn());
}

// Ambil ulasan terbaru (8 ulasan) untuk teknisi ini
$stmt_ulasan = $conn->prepare("
    SELECT r.bintang, r.komentar, r.created_at, r.layanan,
           u.nama AS nama_pelanggan
    FROM ratings r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.teknisi_id = ?
    ORDER BY r.created_at DESC
    LIMIT 8
");
$stmt_ulasan->execute([$teknisi_id]);
$ulasan_list = $stmt_ulasan->fetchAll(PDO::FETCH_ASSOC);

// ─── Ambil layanan dari database ─────────────────────────────────────
// Cek apakah tabel layanan_teknisi sudah ada
$has_layanan_tbl = false;
try {
    $cek_lt = $conn->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='layanan_teknisi')");
    $cek_lt->execute();
    $has_layanan_tbl = (bool) $cek_lt->fetchColumn();
} catch (PDOException $e) {}

$services = [];
if ($has_layanan_tbl) {
    $stmtSvc = $conn->prepare("
        SELECT id, nama, harga, durasi, deskripsi, keunggulan, icon, warna, status
        FROM layanan_teknisi
        WHERE teknisi_id = ? AND status = 'aktif'
        ORDER BY urutan ASC, id ASC
    ");
    $stmtSvc->execute([$teknisi_id]);
    $rows = $stmtSvc->fetchAll(PDO::FETCH_ASSOC);

    $warna_bg_map = [
        '#2563EB'=>'#EFF6FF', '#10B981'=>'#ECFDF5', '#F59E0B'=>'#FFFBEB',
        '#EF4444'=>'#FEF2F2', '#8B5CF6'=>'#F5F3FF', '#EC4899'=>'#FDF2F8',
        '#14B8A6'=>'#F0FDFA', '#F97316'=>'#FFF7ED', '#64748B'=>'#F8FAFC', '#059669'=>'#ECFDF5',
    ];

    foreach ($rows as $row) {
        $bg = $warna_bg_map[$row['warna']] ?? '#EFF6FF';
        $keunggulan_arr = json_decode($row['keunggulan'] ?? '[]', true) ?: [];
        $services[] = [
            'id'       => 'svc_' . $row['id'],
            'nama'     => $row['nama'],
            'harga'    => intval($row['harga']),
            'icon'     => $row['icon'] ?: 'bi-tools',
            'warna'    => $row['warna'] ?: '#2563EB',
            'bg'       => $bg,
            'durasi'   => $row['durasi'] ?: '30-60 Menit',
            'desc'     => $row['deskripsi'] ?: '',
            'includes' => $keunggulan_arr,
        ];
    }
}

// Fallback: jika teknisi belum punya layanan sendiri, tampilkan layanan default
if (empty($services)) {
    $services = [
        [
            'id'    => 'cuci_ac',
            'nama'  => 'Cuci AC',
            'harga' => 75000,
            'icon'  => 'bi-wind',
            'warna' => '#3B82F6',
            'bg'    => '#EFF6FF',
            'durasi'=> '45-60 Menit',
            'desc'  => 'Pembersihan menyeluruh unit indoor & outdoor. Menghilangkan debu, jamur, dan kotoran untuk performa AC optimal.',
            'includes' => ['Cuci filter udara', 'Semprot kondensor & evaporator', 'Cek freon (tidak termasuk isi)', 'Uji performa dingin']
        ],
        [
            'id'    => 'perbaikan_ac',
            'nama'  => 'Perbaikan AC',
            'harga' => 150000,
            'icon'  => 'bi-tools',
            'warna' => '#F59E0B',
            'bg'    => '#FFFBEB',
            'durasi'=> '1-3 Jam',
            'desc'  => 'Diagnosa dan perbaikan masalah mekanis & elektrikal. Cocok untuk AC error, mati, atau tidak berfungsi normal.',
            'includes' => ['Diagnosa kerusakan', 'Perbaikan sistem kelistrikan', 'Penggantian komponen (spare part belum termasuk)', 'Test fungsionalitas']
        ],
        [
            'id'    => 'isi_freon',
            'nama'  => 'Isi Freon',
            'harga' => 200000,
            'icon'  => 'bi-moisture',
            'warna' => '#10B981',
            'bg'    => '#ECFDF5',
            'durasi'=> '30-45 Menit',
            'desc'  => 'Pengisian ulang refrigeran untuk mengembalikan performa dingin. Tersedia freon R22, R32, dan R410A.',
            'includes' => ['Cek tekanan freon', 'Pengisian sesuai kapasitas AC', 'Cek kebocoran pipa', 'Garansi dingin 1 bulan']
        ],
    ];
}

// Jumlah booking selesai teknisi ini
$stmt_done = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE teknisi_id = ? AND status = 'Selesai'");
$stmt_done->execute([$teknisi_id]);
$total_done = intval($stmt_done->fetchColumn());
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan <?= htmlspecialchars($teknisi['nama']) ?> - AC Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #F3F4F6; color: #111827; }

        /* ===== NAVBAR ===== */
        .topnav { background: white; padding: 14px 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); position: sticky; top: 0; z-index: 100; }
        .back-btn { width: 38px; height: 38px; border-radius: 50%; border: 1px solid #E5E7EB; display: flex; align-items: center; justify-content: center; color: #374151; text-decoration: none; transition: 0.2s; }
        .back-btn:hover { background: #F3F4F6; color: #2563EB; }
        .topnav-title { font-weight: 700; font-size: 15px; color: #111827; }
        .topnav-sub { font-size: 12px; color: #9CA3AF; }

        /* ===== HERO PROFILE CARD ===== */
        .hero-section { background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 60%, #3B82F6 100%); padding: 28px 20px 20px; color: white; }
        .teknisi-profile { display: flex; gap: 16px; align-items: flex-start; max-width: 900px; margin: 0 auto; }
        .teknisi-avatar-big { width: 80px; height: 80px; border-radius: 16px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; font-size: 36px; flex-shrink: 0; }
        .teknisi-name { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
        .teknisi-spec { font-size: 13px; opacity: 0.85; margin-bottom: 10px; }
        .teknisi-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .badge-pill { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }
        .badge-pill.verified { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.5); }

        /* Star Hero */
        .hero-stars { display: flex; align-items: center; gap: 6px; }
        .hero-stars .stars { color: #FCD34D; font-size: 16px; }
        .hero-stars .avg-num { font-size: 20px; font-weight: 800; }
        .hero-stars .review-cnt { font-size: 12px; opacity: 0.8; }

        /* Stats row */
        .hero-stats { display: flex; gap: 0; border-top: 1px solid rgba(255,255,255,0.15); margin-top: 16px; padding-top: 16px; max-width: 900px; margin: 16px auto 0; }
        .stat-item { flex: 1; text-align: center; padding: 8px 0; border-right: 1px solid rgba(255,255,255,0.15); }
        .stat-item:last-child { border-right: none; }
        .stat-num { font-size: 20px; font-weight: 800; }
        .stat-label { font-size: 11px; opacity: 0.75; }

        /* ===== TAB NAV ===== */
        .tab-nav { background: white; border-bottom: 1px solid #E5E7EB; position: sticky; top: 65px; z-index: 90; }
        .tab-nav-inner { max-width: 900px; margin: 0 auto; display: flex; }
        .tab-btn { flex: 1; padding: 14px 8px; text-align: center; font-size: 14px; font-weight: 600; color: #6B7280; border: none; background: none; border-bottom: 3px solid transparent; transition: 0.2s; cursor: pointer; }
        .tab-btn.active { color: #2563EB; border-bottom-color: #2563EB; }

        /* ===== MAIN LAYOUT ===== */
        .main-layout { max-width: 900px; margin: 0 auto; padding: 20px 16px 100px; display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
        @media (max-width: 768px) { .main-layout { grid-template-columns: 1fr; } }

        /* ===== SECTION CARDS ===== */
        .section-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .section-heading { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .section-heading i { color: #2563EB; }

        /* ===== SERVICE ITEM (Shopee style) ===== */
        .service-item {
            border: 2px solid #E5E7EB;
            border-radius: 14px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
            background: white;
        }
        .service-item:hover { border-color: #93C5FD; box-shadow: 0 4px 16px rgba(37,99,235,0.1); transform: translateY(-2px); }
        .service-item.selected { border-color: #2563EB; background: #EFF6FF; }
        .service-item.selected::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 0; height: 0;
            border-top: 32px solid #2563EB;
            border-left: 32px solid transparent;
        }
        .service-item.selected::after {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 3px;
            color: white;
            font-size: 12px;
            font-weight: 700;
        }

        .service-item-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .service-icon-box { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .service-name { font-weight: 700; font-size: 15px; color: #111827; margin-bottom: 2px; }
        .service-duration { font-size: 12px; color: #6B7280; display: flex; align-items: center; gap: 4px; }
        .service-price { font-size: 20px; font-weight: 800; color: #2563EB; margin-left: auto; }

        .service-desc { font-size: 13px; color: #4B5563; line-height: 1.6; margin-bottom: 10px; }

        .service-includes { display: flex; flex-wrap: wrap; gap: 6px; }
        .include-tag { background: #F0F9FF; color: #0369A1; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 3px; }

        .qty-control { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
        .qty-btn { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid #2563EB; background: white; color: #2563EB; font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; line-height: 1; }
        .qty-btn:hover { background: #2563EB; color: white; }
        .qty-num { font-weight: 700; font-size: 15px; min-width: 20px; text-align: center; }
        .service-item:not(.selected) .qty-control { display: none; }

        /* ===== RATING SUMMARY ===== */
        .rating-summary { display: flex; gap: 20px; align-items: center; margin-bottom: 16px; }
        .rating-big-num { font-size: 56px; font-weight: 800; color: #111827; line-height: 1; }
        .rating-big-stars { color: #F59E0B; font-size: 18px; margin-bottom: 4px; }
        .rating-big-label { font-size: 12px; color: #9CA3AF; }

        .rating-bars { flex: 1; }
        .rating-bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .rating-bar-label { font-size: 12px; color: #6B7280; width: 36px; text-align: right; flex-shrink: 0; display: flex; align-items: center; gap: 3px; }
        .rating-bar-track { flex: 1; height: 8px; background: #E5E7EB; border-radius: 4px; overflow: hidden; }
        .rating-bar-fill { height: 100%; background: linear-gradient(90deg, #F59E0B, #FBBF24); border-radius: 4px; transition: width 0.8s ease; }
        .rating-bar-cnt { font-size: 11px; color: #9CA3AF; width: 20px; }

        /* ===== REVIEW CARD ===== */
        .review-card { border-bottom: 1px solid #F3F4F6; padding: 16px 0; }
        .review-card:last-child { border-bottom: none; }
        .reviewer-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #DBEAFE, #EFF6FF); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #2563EB; font-weight: 700; flex-shrink: 0; }
        .reviewer-name { font-weight: 600; font-size: 14px; color: #111827; }
        .reviewer-date { font-size: 11px; color: #9CA3AF; }
        .review-stars { color: #F59E0B; font-size: 13px; margin: 4px 0; }
        .review-text { font-size: 13px; color: #4B5563; line-height: 1.6; }
        .review-layanan-badge { background: #F0FDF4; color: #15803D; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px; }

        .empty-reviews { text-align: center; padding: 40px 20px; color: #9CA3AF; }
        .empty-reviews i { font-size: 40px; margin-bottom: 12px; display: block; }

        /* ===== STICKY ORDER SUMMARY (RIGHT SIDE) ===== */
        .order-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); position: sticky; top: 115px; }
        .order-title { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #111827; }

        .order-empty { text-align: center; padding: 30px 0; color: #9CA3AF; font-size: 13px; }
        .order-empty i { font-size: 32px; margin-bottom: 8px; display: block; }

        .order-item-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #E5E7EB; }
        .order-item-row:last-of-type { border-bottom: none; }
        .order-item-name { font-size: 13px; font-weight: 600; color: #374151; }
        .order-item-qty { font-size: 11px; color: #6B7280; }
        .order-item-price { font-size: 13px; font-weight: 700; color: #2563EB; white-space: nowrap; }

        .order-total-row { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 2px solid #E5E7EB; margin-top: 4px; }
        .order-total-label { font-size: 14px; font-weight: 700; }
        .order-total-price { font-size: 22px; font-weight: 800; color: #2563EB; }

        .btn-pesan {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 16px;
            box-shadow: 0 6px 18px rgba(37,99,235,0.3);
        }
        .btn-pesan:hover { background: linear-gradient(135deg, #1D4ED8, #1E40AF); transform: translateY(-2px); box-shadow: 0 10px 24px rgba(37,99,235,0.35); }
        .btn-pesan:disabled { background: #9CA3AF; box-shadow: none; transform: none; cursor: not-allowed; }

        .guarantee-box { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 10px 14px; margin-top: 12px; font-size: 12px; color: #15803D; display: flex; align-items: flex-start; gap: 8px; }

        /* ===== FLOATING BOTTOM BAR (Mobile) ===== */
        .bottom-bar-mobile {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: white;
            border-top: 1px solid #E5E7EB;
            padding: 12px 16px;
            z-index: 999;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .bottom-bar-mobile { display: flex; }
            .order-card { display: none; }
        }
        .bottom-total { font-size: 18px; font-weight: 800; color: #2563EB; }
        .bottom-sublabel { font-size: 11px; color: #9CA3AF; }
        .btn-pesan-mobile { padding: 14px 28px; background: linear-gradient(135deg, #2563EB, #1D4ED8); color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .btn-pesan-mobile:disabled { background: #9CA3AF; cursor: not-allowed; }

        /* ===== FILTER PILLS ===== */
        .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .filter-pill { padding: 6px 14px; border-radius: 20px; border: 1.5px solid #E5E7EB; background: white; font-size: 12px; font-weight: 600; color: #6B7280; cursor: pointer; transition: 0.2s; }
        .filter-pill:hover { border-color: #93C5FD; color: #2563EB; }
        .filter-pill.active { border-color: #2563EB; background: #EFF6FF; color: #2563EB; }

        /* Animations */
        @keyframes fadeInUp { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: translateY(0); } }
        .section-card { animation: fadeInUp 0.4s ease; }
    </style>
</head>
<body>

<!-- TOP NAV -->
<div class="topnav">
    <a href="layanan.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="topnav-title"><?= htmlspecialchars($teknisi['nama']) ?></div>
        <div class="topnav-sub">Halaman Layanan Teknisi</div>
    </div>
    <div class="ms-auto">
        <?php if (isset($_SESSION['user'])): ?>
            <span style="font-size:13px; color:#6B7280; font-weight:600;">
                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user']['nama']) ?>
            </span>
        <?php else: ?>
            <a href="../auth/login.php" style="font-size:13px; color:#2563EB; font-weight:700; text-decoration:none;">Masuk</a>
        <?php endif; ?>
    </div>
</div>

<!-- HERO PROFILE -->
<div class="hero-section">
    <div class="teknisi-profile">
        <div class="teknisi-avatar-big">
            <i class="bi bi-person-badge"></i>
        </div>
        <div style="flex:1;">
            <div class="teknisi-name"><?= htmlspecialchars($teknisi['nama']) ?></div>
            <div class="teknisi-spec"><i class="bi bi-award me-1"></i><?= htmlspecialchars($teknisi['spesialisasi'] ?? 'Teknisi AC Profesional') ?></div>
            <div class="teknisi-badges">
                <span class="badge-pill verified"><i class="bi bi-check-circle me-1"></i>Terverifikasi</span>
                <span class="badge-pill"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($teknisi['wilayah'] ?? 'Area Lokal') ?></span>
                <span class="badge-pill"><i class="bi bi-shield-check me-1"></i>Pro Teknisi</span>
            </div>
            <div class="hero-stars">
                <span class="avg-num"><?= $avg_rating > 0 ? $avg_rating : '-' ?></span>
                <div class="stars">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <?php if ($s <= floor($avg_rating)): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php elseif ($avg_rating - floor($avg_rating) >= 0.5 && $s == ceil($avg_rating)): ?>
                            <i class="bi bi-star-half"></i>
                        <?php else: ?>
                            <i class="bi bi-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <span class="review-cnt">(<?= $total_review ?> ulasan)</span>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="hero-stats">
        <div class="stat-item">
            <div class="stat-num"><?= $avg_rating > 0 ? $avg_rating : '-' ?></div>
            <div class="stat-label">⭐ Rating</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= $total_review ?></div>
            <div class="stat-label">Ulasan</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= $total_done ?>+</div>
            <div class="stat-label">Order Selesai</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= count($services) ?></div>
            <div class="stat-label">Jenis Layanan</div>
        </div>
    </div>
</div>

<!-- TAB NAV -->
<div class="tab-nav">
    <div class="tab-nav-inner">
        <button class="tab-btn active" onclick="showTab('layanan', this)">🛠️ Layanan</button>
        <button class="tab-btn" onclick="showTab('ulasan', this)">⭐ Ulasan (<?= $total_review ?>)</button>
        <button class="tab-btn" onclick="showTab('profil', this)">👤 Profil</button>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="main-layout">
    <!-- LEFT COLUMN -->
    <div>
        <!-- ====== TAB: LAYANAN ====== -->
        <div id="tab-layanan">
            <div class="section-card">
                <div class="section-heading"><i class="bi bi-grid-3x3-gap-fill"></i> Pilih Layanan</div>
                <p style="font-size:13px; color:#6B7280; margin-bottom:16px;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Anda bisa memilih <strong>lebih dari satu layanan</strong> sekaligus. Centang layanan yang diinginkan.
                </p>

                <?php foreach ($services as $svc): ?>
                <div class="service-item" id="svc-<?= $svc['id'] ?>" onclick="toggleService('<?= $svc['id'] ?>', <?= $svc['harga'] ?>, '<?= addslashes($svc['nama']) ?>', '<?= $svc['durasi'] ?>', event)">
                    <div class="service-item-header">
                        <div class="service-icon-box" style="background: <?= $svc['bg'] ?>; color: <?= $svc['warna'] ?>;">
                            <i class="bi <?= $svc['icon'] ?>"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="service-name"><?= $svc['nama'] ?></div>
                            <div class="service-duration">
                                <i class="bi bi-clock"></i> <?= $svc['durasi'] ?>
                            </div>
                        </div>
                        <div class="service-price">Rp <?= number_format($svc['harga'], 0, ',', '.') ?></div>
                    </div>

                    <div class="service-desc"><?= $svc['desc'] ?></div>

                    <div class="service-includes">
                        <?php foreach ($svc['includes'] as $inc): ?>
                            <span class="include-tag"><i class="bi bi-check2"></i><?= $inc ?></span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Qty Control -->
                    <div class="qty-control" onclick="event.stopPropagation()">
                        <span style="font-size:12px; color:#6B7280; font-weight:600;">Jumlah Unit:</span>
                        <button class="qty-btn" onclick="changeQty('<?= $svc['id'] ?>', -1)">−</button>
                        <span class="qty-num" id="qty-<?= $svc['id'] ?>">1</span>
                        <button class="qty-btn" onclick="changeQty('<?= $svc['id'] ?>', 1)">+</button>
                        <span style="font-size:12px; color:#9CA3AF;">unit AC</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ====== TAB: ULASAN ====== -->
        <div id="tab-ulasan" style="display:none;">
            <!-- Rating Summary -->
            <div class="section-card">
                <div class="section-heading"><i class="bi bi-star-fill"></i> Ringkasan Penilaian</div>

                <?php if ($total_review > 0): ?>
                <div class="rating-summary">
                    <div style="text-align:center;">
                        <div class="rating-big-num"><?= $avg_rating ?></div>
                        <div class="rating-big-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi bi-star<?= $s <= round($avg_rating) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-big-label">dari <?= $total_review ?> ulasan</div>
                    </div>

                    <div class="rating-bars">
                        <?php for ($b = 5; $b >= 1; $b--): 
                            $cnt = $dist_data[$b] ?? 0;
                            $pct = $total_review > 0 ? round(($cnt / $total_review) * 100) : 0;
                        ?>
                        <div class="rating-bar-row">
                            <div class="rating-bar-label"><?= $b ?> <i class="bi bi-star-fill" style="color:#F59E0B;font-size:10px;"></i></div>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width:<?= $pct ?>%;"></div>
                            </div>
                            <div class="rating-bar-cnt"><?= $cnt ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Filter by star -->
                <div class="filter-pills" id="starFilter">
                    <div class="filter-pill active" onclick="filterReviews('all', this)">Semua</div>
                    <?php for ($b = 5; $b >= 1; $b--): ?>
                        <div class="filter-pill" onclick="filterReviews(<?= $b ?>, this)">
                            <?= $b ?> ⭐ (<?= $dist_data[$b] ?? 0 ?>)
                        </div>
                    <?php endfor; ?>
                </div>
                <?php else: ?>
                <div class="empty-reviews">
                    <i class="bi bi-star"></i>
                    <p>Belum ada penilaian untuk teknisi ini.</p>
                    <small>Jadilah yang pertama memberi ulasan!</small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Review List -->
            <div class="section-card" id="reviewList">
                <div class="section-heading"><i class="bi bi-chat-square-text-fill"></i> Ulasan Pelanggan</div>
                <?php if (!empty($ulasan_list)): ?>
                    <?php foreach ($ulasan_list as $ul): ?>
                    <div class="review-card" data-bintang="<?= $ul['bintang'] ?>">
                        <div class="d-flex gap-10 align-items-start" style="gap:10px;">
                            <div class="reviewer-avatar">
                                <?= mb_strtoupper(mb_substr($ul['nama_pelanggan'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div style="flex:1;">
                                <div class="reviewer-name"><?= htmlspecialchars($ul['nama_pelanggan'] ?? 'Pelanggan') ?></div>
                                <div class="reviewer-date"><?= date('d M Y', strtotime($ul['created_at'])) ?></div>
                                <div class="review-stars">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi bi-star<?= $s <= $ul['bintang'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-text"><?= htmlspecialchars($ul['komentar']) ?></p>
                                <span class="review-layanan-badge"><i class="bi bi-check2-circle"></i><?= htmlspecialchars($ul['layanan']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-reviews">
                        <i class="bi bi-chat-dots"></i>
                        <p>Belum ada ulasan yang ditulis.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====== TAB: PROFIL ====== -->
        <div id="tab-profil" style="display:none;">
            <div class="section-card">
                <div class="section-heading"><i class="bi bi-person-badge-fill"></i> Profil Teknisi</div>
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#2563EB; font-size:18px; flex-shrink:0;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:12px; color:#9CA3AF;">Nama Teknisi</div>
                            <div style="font-weight:700; color:#111827;"><?= htmlspecialchars($teknisi['nama']) ?></div>
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#ECFDF5; display:flex; align-items:center; justify-content:center; color:#10B981; font-size:18px; flex-shrink:0;">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:12px; color:#9CA3AF;">Spesialisasi</div>
                            <div style="font-weight:700; color:#111827;"><?= htmlspecialchars($teknisi['spesialisasi'] ?? 'Teknisi AC Umum') ?></div>
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#FFF7ED; display:flex; align-items:center; justify-content:center; color:#F59E0B; font-size:18px; flex-shrink:0;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:12px; color:#9CA3AF;">Area Layanan</div>
                            <div style="font-weight:700; color:#111827;"><?= htmlspecialchars($teknisi['wilayah'] ?? 'Wilayah Lokal') ?></div>
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#FDF2F8; display:flex; align-items:center; justify-content:center; color:#9333EA; font-size:18px; flex-shrink:0;">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:12px; color:#9CA3AF;">Kontak</div>
                            <div style="font-weight:700; color:#111827;"><?= htmlspecialchars($teknisi['telepon'] ?? 'Hubungi via platform') ?></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px; padding:16px; background:#F8FAFC; border-radius:12px; border:1px solid #E5E7EB;">
                    <div style="font-weight:700; font-size:13px; margin-bottom:8px; color:#374151;">Jaminan Layanan</div>
                    <div style="font-size:12px; color:#6B7280; line-height:1.8;">
                        ✅ Teknisi berpengalaman & terlatih<br>
                        ✅ Datang tepat waktu sesuai jadwal<br>
                        ✅ Membawa peralatan lengkap<br>
                        ✅ Garansi perbaikan 7 hari<br>
                        ✅ Terdaftar resmi di platform AC Service
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: ORDER SUMMARY -->
    <div>
        <div class="order-card">
            <div class="order-title">🛒 Keranjang Layanan</div>

            <div id="orderEmpty" class="order-empty">
                <i class="bi bi-cart3"></i>
                <p>Belum ada layanan dipilih</p>
                <small>Pilih layanan di sebelah kiri</small>
            </div>

            <div id="orderItems" style="display:none;"></div>

            <div id="orderTotal" style="display:none;">
                <div class="order-total-row">
                    <div class="order-total-label">Total Bayar</div>
                    <div class="order-total-price" id="totalPriceDisplay">Rp 0</div>
                </div>
            </div>

            <button class="btn-pesan" id="btnPesanDesktop" disabled onclick="goToBooking()">
                <i class="bi bi-calendar-check me-2"></i>Pesan Sekarang
            </button>

            <div class="guarantee-box">
                <i class="bi bi-shield-check-fill" style="flex-shrink:0; margin-top:1px;"></i>
                <span>Pembayaran dilakukan langsung kepada teknisi setelah pekerjaan selesai.</span>
            </div>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM BAR -->
<div class="bottom-bar-mobile" id="bottomBar">
    <div>
        <div class="bottom-sublabel" id="bottomLabel">Pilih layanan</div>
        <div class="bottom-total" id="bottomTotal">Rp 0</div>
    </div>
    <button class="btn-pesan-mobile" id="btnPesanMobile" disabled onclick="goToBooking()">
        Pesan Sekarang →
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
// DATA STATE
// ============================================================
const TEKNISI_ID = <?= $teknisi_id ?>;

const servicesData = {
    <?php foreach ($services as $svc): ?>
    '<?= $svc['id'] ?>': {
        nama: '<?= addslashes($svc['nama']) ?>',
        harga: <?= $svc['harga'] ?>,
        durasi: '<?= $svc['durasi'] ?>',
        qty: 1,
        selected: false
    },
    <?php endforeach; ?>
};

// ============================================================
// TOGGLE SERVICE SELECTION
// ============================================================
function toggleService(id, harga, nama, durasi, event) {
    if (event.target.classList.contains('qty-btn') || 
        event.target.closest('.qty-control')) return;

    const item = document.getElementById('svc-' + id);
    const data = servicesData[id];
    
    data.selected = !data.selected;
    
    if (data.selected) {
        item.classList.add('selected');
    } else {
        item.classList.remove('selected');
        data.qty = 1;
        document.getElementById('qty-' + id).innerText = 1;
    }

    updateOrderSummary();
}

// ============================================================
// CHANGE QUANTITY
// ============================================================
function changeQty(id, delta) {
    const data = servicesData[id];
    if (!data.selected) return;
    
    data.qty = Math.max(1, Math.min(5, data.qty + delta));
    document.getElementById('qty-' + id).innerText = data.qty;
    updateOrderSummary();
}

// ============================================================
// UPDATE ORDER SUMMARY
// ============================================================
function updateOrderSummary() {
    const selectedItems = Object.entries(servicesData).filter(([k, v]) => v.selected);
    const totalHarga = selectedItems.reduce((sum, [k, v]) => sum + (v.harga * v.qty), 0);
    const count = selectedItems.length;

    const emptyEl = document.getElementById('orderEmpty');
    const itemsEl = document.getElementById('orderItems');
    const totalEl = document.getElementById('orderTotal');
    const btnD = document.getElementById('btnPesanDesktop');
    const btnM = document.getElementById('btnPesanMobile');
    const bottomLabel = document.getElementById('bottomLabel');
    const bottomTotal = document.getElementById('bottomTotal');

    if (count === 0) {
        emptyEl.style.display = 'block';
        itemsEl.style.display = 'none';
        totalEl.style.display = 'none';
        btnD.disabled = true;
        btnM.disabled = true;
        bottomLabel.innerText = 'Pilih layanan';
        bottomTotal.innerText = 'Rp 0';
    } else {
        emptyEl.style.display = 'none';
        itemsEl.style.display = 'block';
        totalEl.style.display = 'block';
        btnD.disabled = false;
        btnM.disabled = false;

        // Render items
        let html = '';
        selectedItems.forEach(([id, v]) => {
            html += `
                <div class="order-item-row">
                    <div>
                        <div class="order-item-name">${v.nama}</div>
                        <div class="order-item-qty">${v.qty > 1 ? v.qty + ' unit × Rp ' + v.harga.toLocaleString('id-ID') : '1 unit'}</div>
                    </div>
                    <div class="order-item-price">Rp ${(v.harga * v.qty).toLocaleString('id-ID')}</div>
                </div>
            `;
        });
        itemsEl.innerHTML = html;

        const fmt = 'Rp ' + totalHarga.toLocaleString('id-ID');
        document.getElementById('totalPriceDisplay').innerText = fmt;
        bottomTotal.innerText = fmt;
        bottomLabel.innerText = count + ' layanan dipilih';
    }
}

// ============================================================
// GO TO BOOKING
// ============================================================
function goToBooking() {
    const selectedItems = Object.entries(servicesData).filter(([k, v]) => v.selected);
    if (selectedItems.length === 0) {
        alert('Pilih minimal satu layanan terlebih dahulu.');
        return;
    }

    // Build query string
    const layanans = selectedItems.map(([k, v]) => encodeURIComponent(v.nama) + 'x' + v.qty);
    const totalHarga = selectedItems.reduce((sum, [k, v]) => sum + (v.harga * v.qty), 0);
    const layananStr = selectedItems.map(([k, v]) => v.qty > 1 ? v.nama + ' (x' + v.qty + ')' : v.nama).join(', ');

    const url = `booking.php?teknisi_id=${TEKNISI_ID}&layanan=${encodeURIComponent(layananStr)}&harga=${totalHarga}&multi=1`;
    window.location.href = url;
}

// ============================================================
// TAB SWITCHING
// ============================================================
function showTab(tab, btn) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

// ============================================================
// FILTER REVIEWS
// ============================================================
function filterReviews(star, btn) {
    document.querySelectorAll('#starFilter .filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.review-card');
    cards.forEach(card => {
        if (star === 'all' || parseInt(card.dataset.bintang) === star) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ============================================================
// AUTO SCROLL TO TAB
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const urlHash = window.location.hash;
    if (urlHash === '#ulasan') {
        const btn = document.querySelector('.tab-btn:nth-child(2)');
        if (btn) showTab('ulasan', btn);
    }
});
</script>
</body>
</html>