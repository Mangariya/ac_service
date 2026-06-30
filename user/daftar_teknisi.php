<?php
session_start();
include '../config/database.php';

// Buat tabel pendaftaran_teknisi jika belum ada
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS pendaftaran_teknisi (
            id SERIAL PRIMARY KEY,
            nama VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            telepon VARCHAR(20) NOT NULL,
            alamat TEXT,
            tanggal_lahir DATE,
            jenis_kelamin VARCHAR(20),
            pendidikan VARCHAR(100),
            pengalaman_tahun INT DEFAULT 0,
            pengalaman_kerja TEXT,
            spesialisasi VARCHAR(200),
            wilayah VARCHAR(150),
            kemampuan TEXT,
            portofolio_url VARCHAR(300),
            motivasi TEXT,
            ketersediaan VARCHAR(100),
            status VARCHAR(30) DEFAULT 'menunggu',
            catatan_admin TEXT,
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP DEFAULT NOW()
        )
    ");
} catch (PDOException $e) {
    // Tabel sudah ada, abaikan
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama              = trim($_POST['nama'] ?? '');
    $email             = strtolower(trim($_POST['email'] ?? ''));
    $telepon           = trim($_POST['telepon'] ?? '');
    $alamat            = trim($_POST['alamat'] ?? '');
    $tanggal_lahir     = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin     = trim($_POST['jenis_kelamin'] ?? '');
    $pendidikan        = trim($_POST['pendidikan'] ?? '');
    $pengalaman_tahun  = intval($_POST['pengalaman_tahun'] ?? 0);
    $pengalaman_kerja  = trim($_POST['pengalaman_kerja'] ?? '');
    $spesialisasi      = trim($_POST['spesialisasi'] ?? '');
    $wilayah           = trim($_POST['wilayah'] ?? '');
    $kemampuan         = trim($_POST['kemampuan'] ?? '');
    $portofolio_url    = trim($_POST['portofolio_url'] ?? '');
    $motivasi          = trim($_POST['motivasi'] ?? '');
    $ketersediaan      = trim($_POST['ketersediaan'] ?? '');

    if (empty($nama) || empty($email) || empty($telepon) || empty($spesialisasi) || empty($wilayah) || empty($motivasi)) {
        $error = 'Nama, email, telepon, spesialisasi, wilayah, dan motivasi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek apakah email sudah pernah mendaftar
        $cek = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_teknisi WHERE email = ? AND status != 'ditolak'");
        $cek->execute([$email]);
        if ($cek->fetchColumn() > 0) {
            $error = 'Email ini sudah pernah mengirimkan pendaftaran. Silakan tunggu konfirmasi dari admin.';
        } else {
            // Cek apakah email sudah terdaftar sebagai teknisi
            $cek2 = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $cek2->execute([$email]);
            if ($cek2->fetchColumn() > 0) {
                $error = 'Email ini sudah terdaftar di sistem kami.';
            } else {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO pendaftaran_teknisi
                            (nama, email, telepon, alamat, tanggal_lahir, jenis_kelamin, pendidikan,
                             pengalaman_tahun, pengalaman_kerja, spesialisasi, wilayah, kemampuan,
                             portofolio_url, motivasi, ketersediaan, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')
                    ");
                    $stmt->execute([
                        $nama, $email, $telepon, $alamat,
                        !empty($tanggal_lahir) ? $tanggal_lahir : null,
                        $jenis_kelamin, $pendidikan, $pengalaman_tahun,
                        $pengalaman_kerja, $spesialisasi, $wilayah, $kemampuan,
                        $portofolio_url, $motivasi, $ketersediaan
                    ]);
                    $success = 'Pendaftaran Anda berhasil dikirim! Tim kami akan menghubungi Anda dalam 1-3 hari kerja.';
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan pendaftaran: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jadi Teknisi - AC Service</title>
    <meta name="description" content="Bergabunglah sebagai teknisi AC profesional. Daftarkan diri Anda dan mulai karir sebagai teknisi AC terpercaya.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 40%, #EDE9FE 100%); min-height: 100vh; }

        /* Navbar */
        .navbar-custom { background: white; padding: 16px 50px; border-bottom: 2px solid #E5E7EB; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo { font-size: 22px; font-weight: 800; color: #111827; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .btn-back { background: #F1F5F9; color: #475569; padding: 9px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-back:hover { background: #E2E8F0; color: #1E293B; }

        /* Hero Banner */
        .hero-section {
            background: linear-gradient(135deg, #1E40AF 0%, #2563EB 50%, #7C3AED 100%);
            padding: 60px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .hero-title { font-size: 42px; font-weight: 800; margin-bottom: 12px; position: relative; }
        .hero-sub { font-size: 17px; opacity: 0.9; max-width: 600px; margin: 0 auto 24px; position: relative; }
        .hero-badges { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; position: relative; }
        .hero-badge {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Main Form Area */
        .form-wrapper { max-width: 900px; margin: 0 auto; padding: 40px 20px 60px; }

        /* Step Indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 36px;
        }
        .step-item { display: flex; align-items: center; gap: 0; }
        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            flex-shrink: 0;
            transition: 0.3s;
        }
        .step-circle.active { background: #2563EB; color: white; box-shadow: 0 4px 14px rgba(37,99,235,0.4); }
        .step-circle.done { background: #10B981; color: white; }
        .step-line { width: 60px; height: 3px; background: #E2E8F0; transition: 0.3s; }
        .step-line.done { background: #10B981; }
        .step-label { font-size: 11px; font-weight: 600; color: #94A3B8; text-align: center; margin-top: 6px; }
        .step-label.active { color: #2563EB; }
        .step-container { display: flex; flex-direction: column; align-items: center; }

        /* Cards */
        .form-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(37,99,235,0.08);
            padding: 36px;
            margin-bottom: 24px;
            border: 1px solid rgba(37,99,235,0.06);
            display: none;
        }
        .form-section.active { display: block; animation: fadeInUp 0.3s ease; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid #F1F5F9;
        }
        .section-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            color: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .section-title-text h4 { font-size: 19px; font-weight: 800; color: #111827; margin: 0; }
        .section-title-text p { font-size: 13px; color: #6B7280; margin: 3px 0 0; }

        .form-label { font-weight: 700; color: #374151; font-size: 13px; margin-bottom: 6px; }
        .form-label .req { color: #EF4444; }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1.5px solid #E5E7EB;
            font-weight: 500;
            font-size: 14px;
            transition: 0.2s;
            background: #FAFAFA;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            background: white;
        }
        textarea.form-control { resize: vertical; min-height: 110px; }

        /* Kemampuan checkboxes */
        .kemampuan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 4px;
        }
        .kemampuan-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #F8FAFC;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .kemampuan-item:hover { border-color: #93C5FD; background: #EFF6FF; }
        .kemampuan-item input[type="checkbox"] { accent-color: #2563EB; width: 17px; height: 17px; }
        .kemampuan-item span { font-size: 13px; font-weight: 600; color: #374151; }
        .kemampuan-item.checked { border-color: #2563EB; background: #EFF6FF; }

        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 12px;
        }
        .btn-prev {
            background: #F1F5F9;
            color: #475569;
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-prev:hover { background: #E2E8F0; }
        .btn-next {
            background: linear-gradient(135deg, #2563EB, #3B82F6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 32px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 14px rgba(37,99,235,0.35);
        }
        .btn-next:hover { background: linear-gradient(135deg, #1D4ED8, #2563EB); transform: translateY(-1px); }
        .btn-submit-final {
            background: linear-gradient(135deg, #059669, #10B981);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 36px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 14px rgba(16,185,129,0.35);
        }
        .btn-submit-final:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,0.45); }

        /* Summary Card */
        .summary-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .summary-item:last-child { border-bottom: none; }
        .summary-label { font-size: 12px; font-weight: 700; color: #9CA3AF; min-width: 150px; }
        .summary-value { font-size: 13px; font-weight: 600; color: #1F2937; }

        /* Alert */
        .alert-success-custom {
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            border: 1.5px solid #10B981;
            border-radius: 16px;
            padding: 28px;
            text-align: center;
        }
        .alert-danger-custom {
            background: #FEF2F2;
            border: 1.5px solid #FCA5A5;
            border-radius: 16px;
            padding: 18px 22px;
            color: #B91C1C;
            font-weight: 600;
            font-size: 14px;
        }

        /* Progress Bar */
        .progress-bar-wrapper { margin-bottom: 32px; }
        .progress-track { height: 5px; background: #E2E8F0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #2563EB, #7C3AED); border-radius: 10px; transition: width 0.4s ease; }

        @media(max-width: 768px) {
            .hero-title { font-size: 28px; }
            .form-section { padding: 22px; }
            .step-line { width: 30px; }
            .navbar-custom { padding: 14px 18px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
    <a class="logo" href="home.php">
        <i class="bi bi-snow2" style="font-size:28px; color:#2563EB;"></i>
        AC SERVICE
    </a>
    <a href="home.php" class="btn-back">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
    </a>
</nav>

<!-- Hero -->
<section class="hero-section">
    <h1 class="hero-title">🔧 Bergabung Jadi Teknisi Kami</h1>
    <p class="hero-sub">Jadilah bagian dari tim teknisi AC profesional kami dan raih penghasilan lebih besar.</p>
    <div class="hero-badges">
        <span class="hero-badge"><i class="bi bi-cash-coin me-1"></i>Penghasilan Menarik</span>
        <span class="hero-badge"><i class="bi bi-calendar-check me-1"></i>Jadwal Fleksibel</span>
        <span class="hero-badge"><i class="bi bi-shield-check me-1"></i>Sistem Terpercaya</span>
        <span class="hero-badge"><i class="bi bi-people me-1"></i>Tim Profesional</span>
    </div>
</section>

<div class="form-wrapper">

<?php if (!empty($success)): ?>
    <div class="alert-success-custom">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 52px; display: block; margin-bottom: 16px;"></i>
        <h4 class="fw-800 text-success mb-2">Pendaftaran Berhasil Dikirim!</h4>
        <p class="text-success mb-4" style="font-size: 15px;"><?= htmlspecialchars($success) ?></p>
        <div style="background: rgba(16,185,129,0.1); border-radius: 12px; padding: 16px; margin-bottom: 20px; text-align: left;">
            <p class="mb-1 fw-bold text-success" style="font-size: 14px;">📋 Langkah Selanjutnya:</p>
            <ul class="text-success mb-0" style="font-size: 13px;">
                <li>Tim kami akan meninjau pendaftaran Anda dalam 1-3 hari kerja</li>
                <li>Jika disetujui, akun teknisi akan dibuat dan dikirim ke email Anda</li>
                <li>Anda akan menerima email berisi username dan password untuk login</li>
            </ul>
        </div>
        <a href="home.php" class="btn btn-success fw-bold px-4 py-2 rounded-3">Kembali ke Beranda</a>
    </div>
<?php else: ?>

<?php if (!empty($error)): ?>
    <div class="alert-danger-custom mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Step Indicator -->
<div class="step-indicator" id="stepIndicator">
    <div class="step-item">
        <div class="step-container">
            <div class="step-circle active" id="step-circle-1">1</div>
            <div class="step-label active" id="step-label-1">Data Pribadi</div>
        </div>
    </div>
    <div class="step-line" id="step-line-1"></div>
    <div class="step-item">
        <div class="step-container">
            <div class="step-circle" id="step-circle-2">2</div>
            <div class="step-label" id="step-label-2">Pengalaman</div>
        </div>
    </div>
    <div class="step-line" id="step-line-2"></div>
    <div class="step-item">
        <div class="step-container">
            <div class="step-circle" id="step-circle-3">3</div>
            <div class="step-label" id="step-label-3">Spesialisasi</div>
        </div>
    </div>
    <div class="step-line" id="step-line-3"></div>
    <div class="step-item">
        <div class="step-container">
            <div class="step-circle" id="step-circle-4">4</div>
            <div class="step-label" id="step-label-4">Konfirmasi</div>
        </div>
    </div>
</div>

<!-- Progress bar -->
<div class="progress-bar-wrapper">
    <div class="progress-track">
        <div class="progress-fill" id="progressFill" style="width: 25%;"></div>
    </div>
</div>

<form method="POST" id="mainForm" action="daftar_teknisi.php">

    <!-- STEP 1: Data Pribadi -->
    <div class="form-section active" id="section1">
        <div class="section-header">
            <div class="section-icon"><i class="bi bi-person-fill"></i></div>
            <div class="section-title-text">
                <h4>Data Pribadi</h4>
                <p>Lengkapi informasi identitas diri Anda</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                <input type="text" name="nama" id="f_nama" class="form-control" placeholder="Masukkan nama lengkap Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Aktif <span class="req">*</span></label>
                <input type="email" name="email" id="f_email" class="form-control" placeholder="contoh@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <div class="form-text text-muted" style="font-size: 11px; margin-top: 4px;">Email akan digunakan untuk mengirim akun teknisi Anda.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">No. Telepon / WhatsApp <span class="req">*</span></label>
                <input type="tel" name="telepon" id="f_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select">
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki" <?= ($_POST['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($_POST['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" class="form-control" rows="3" placeholder="Jl. ..., Kelurahan, Kecamatan, Kota"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pendidikan Terakhir</label>
                <select name="pendidikan" class="form-select">
                    <option value="">-- Pilih --</option>
                    <option value="SMP" <?= ($_POST['pendidikan'] ?? '') === 'SMP' ? 'selected' : '' ?>>SMP / Sederajat</option>
                    <option value="SMA/SMK" <?= ($_POST['pendidikan'] ?? '') === 'SMA/SMK' ? 'selected' : '' ?>>SMA / SMK</option>
                    <option value="D3" <?= ($_POST['pendidikan'] ?? '') === 'D3' ? 'selected' : '' ?>>D3 / Diploma</option>
                    <option value="S1" <?= ($_POST['pendidikan'] ?? '') === 'S1' ? 'selected' : '' ?>>S1 / Sarjana</option>
                    <option value="Lainnya" <?= ($_POST['pendidikan'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Wilayah Kerja <span class="req">*</span></label>
                <input type="text" name="wilayah" id="f_wilayah" class="form-control" placeholder="Contoh: Denpasar, Badung, Gianyar" value="<?= htmlspecialchars($_POST['wilayah'] ?? '') ?>" required>
            </div>
        </div>

        <div class="nav-buttons">
            <div></div>
            <button type="button" class="btn-next" onclick="goToStep(2)">
                Lanjutkan <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 2: Pengalaman Kerja -->
    <div class="form-section" id="section2">
        <div class="section-header">
            <div class="section-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706;"><i class="bi bi-briefcase-fill"></i></div>
            <div class="section-title-text">
                <h4>Pengalaman Kerja</h4>
                <p>Ceritakan pengalaman Anda di bidang AC & Elektronik</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Lama Pengalaman Kerja (Tahun)</label>
                <select name="pengalaman_tahun" class="form-select">
                    <option value="0" <?= ($_POST['pengalaman_tahun'] ?? '0') == '0' ? 'selected' : '' ?>>Belum ada pengalaman</option>
                    <option value="1" <?= ($_POST['pengalaman_tahun'] ?? '') == '1' ? 'selected' : '' ?>>Kurang dari 1 tahun</option>
                    <option value="2" <?= ($_POST['pengalaman_tahun'] ?? '') == '2' ? 'selected' : '' ?>>1 - 2 tahun</option>
                    <option value="3" <?= ($_POST['pengalaman_tahun'] ?? '') == '3' ? 'selected' : '' ?>>3 - 5 tahun</option>
                    <option value="6" <?= ($_POST['pengalaman_tahun'] ?? '') == '6' ? 'selected' : '' ?>>6 - 10 tahun</option>
                    <option value="11" <?= ($_POST['pengalaman_tahun'] ?? '') == '11' ? 'selected' : '' ?>>Lebih dari 10 tahun</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ketersediaan Waktu</label>
                <select name="ketersediaan" class="form-select">
                    <option value="">-- Pilih --</option>
                    <option value="Full-time" <?= ($_POST['ketersediaan'] ?? '') === 'Full-time' ? 'selected' : '' ?>>Full-time (Senin - Sabtu)</option>
                    <option value="Part-time" <?= ($_POST['ketersediaan'] ?? '') === 'Part-time' ? 'selected' : '' ?>>Part-time (Beberapa hari)</option>
                    <option value="Weekend" <?= ($_POST['ketersediaan'] ?? '') === 'Weekend' ? 'selected' : '' ?>>Weekend saja</option>
                    <option value="Fleksibel" <?= ($_POST['ketersediaan'] ?? '') === 'Fleksibel' ? 'selected' : '' ?>>Fleksibel / Sesuai panggilan</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Riwayat Pengalaman Kerja</label>
                <textarea name="pengalaman_kerja" class="form-control" rows="5" placeholder="Ceritakan pengalaman kerja Anda sebelumnya. Contoh: Pernah bekerja di bengkel AC XYZ selama 2 tahun sebagai teknisi lapangan..."><?= htmlspecialchars($_POST['pengalaman_kerja'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Link Portofolio / Media Sosial (Opsional)</label>
                <input type="url" name="portofolio_url" class="form-control" placeholder="https://instagram.com/username atau link lainnya" value="<?= htmlspecialchars($_POST['portofolio_url'] ?? '') ?>">
            </div>
        </div>

        <div class="nav-buttons">
            <button type="button" class="btn-prev" onclick="goToStep(1)">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button type="button" class="btn-next" onclick="goToStep(3)">
                Lanjutkan <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 3: Spesialisasi & Kemampuan -->
    <div class="form-section" id="section3">
        <div class="section-header">
            <div class="section-icon" style="background: linear-gradient(135deg, #F0FDF4, #D1FAE5); color: #059669;"><i class="bi bi-tools"></i></div>
            <div class="section-title-text">
                <h4>Spesialisasi & Kemampuan</h4>
                <p>Pilih bidang keahlian yang Anda kuasai</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Spesialisasi Utama <span class="req">*</span></label>
                <select name="spesialisasi" id="f_spesialisasi" class="form-select" required>
                    <option value="">-- Pilih Spesialisasi --</option>
                    <option value="Cuci AC" <?= ($_POST['spesialisasi'] ?? '') === 'Cuci AC' ? 'selected' : '' ?>>Cuci AC</option>
                    <option value="Perbaikan & Cuci AC" <?= ($_POST['spesialisasi'] ?? '') === 'Perbaikan & Cuci AC' ? 'selected' : '' ?>>Perbaikan & Cuci AC</option>
                    <option value="AC Inverter" <?= ($_POST['spesialisasi'] ?? '') === 'AC Inverter' ? 'selected' : '' ?>>AC Inverter</option>
                    <option value="AC Inverter & Cuci Steam" <?= ($_POST['spesialisasi'] ?? '') === 'AC Inverter & Cuci Steam' ? 'selected' : '' ?>>AC Inverter & Cuci Steam</option>
                    <option value="Isi Freon" <?= ($_POST['spesialisasi'] ?? '') === 'Isi Freon' ? 'selected' : '' ?>>Isi Freon</option>
                    <option value="Instalasi AC" <?= ($_POST['spesialisasi'] ?? '') === 'Instalasi AC' ? 'selected' : '' ?>>Instalasi AC Baru</option>
                    <option value="Semua Layanan AC" <?= ($_POST['spesialisasi'] ?? '') === 'Semua Layanan AC' ? 'selected' : '' ?>>Semua Layanan AC</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Kemampuan Tambahan <span class="text-muted fw-normal">(Pilih yang sesuai)</span></label>
                <div class="kemampuan-grid" id="kemampuanGrid">
                    <?php
                    $kemampuan_list = [
                        'Cuci AC Split', 'Cuci AC Cassette', 'Cuci AC Standing',
                        'Isi Freon R22', 'Isi Freon R32', 'Isi Freon R410A',
                        'Perbaikan Kompresor', 'Ganti Sparepart', 'Instalasi AC Baru',
                        'Bongkar Pasang AC', 'Service PCB/Elektronik', 'AC Central',
                        'AC Mobil', 'Kulkas & Freezer', 'Mesin Cuci'
                    ];
                    $selected_kemampuan = explode(', ', $_POST['kemampuan'] ?? '');
                    foreach ($kemampuan_list as $k): ?>
                        <label class="kemampuan-item <?= in_array($k, $selected_kemampuan) ? 'checked' : '' ?>">
                            <input type="checkbox" name="kemampuan_list[]" value="<?= $k ?>" <?= in_array($k, $selected_kemampuan) ? 'checked' : '' ?> onchange="updateKemampuanStyle(this)">
                            <span><?= $k ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="kemampuan" id="hiddenKemampuan" value="<?= htmlspecialchars($_POST['kemampuan'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Motivasi Bergabung <span class="req">*</span></label>
                <textarea name="motivasi" id="f_motivasi" class="form-control" rows="4" placeholder="Ceritakan mengapa Anda ingin bergabung sebagai teknisi di AC Service kami dan apa yang membuat Anda berbeda dari yang lain..." required><?= htmlspecialchars($_POST['motivasi'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="nav-buttons">
            <button type="button" class="btn-prev" onclick="goToStep(2)">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button type="button" class="btn-next" onclick="goToStep(4)">
                Pratinjau & Kirim <i class="bi bi-eye ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 4: Ringkasan & Konfirmasi -->
    <div class="form-section" id="section4">
        <div class="section-header">
            <div class="section-icon" style="background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: #7C3AED;"><i class="bi bi-clipboard-check-fill"></i></div>
            <div class="section-title-text">
                <h4>Pratinjau & Kirim</h4>
                <p>Periksa kembali data Anda sebelum mengirim</p>
            </div>
        </div>

        <!-- Ringkasan Data -->
        <div style="background: #F8FAFC; border-radius: 14px; padding: 22px; margin-bottom: 22px;">
            <p class="fw-bold mb-3" style="color: #374151; font-size: 14px;"><i class="bi bi-person-fill text-primary me-2"></i>Data Pribadi</p>
            <div class="summary-item"><span class="summary-label">Nama Lengkap</span><span class="summary-value" id="sum_nama">-</span></div>
            <div class="summary-item"><span class="summary-label">Email</span><span class="summary-value" id="sum_email">-</span></div>
            <div class="summary-item"><span class="summary-label">Telepon</span><span class="summary-value" id="sum_telepon">-</span></div>
            <div class="summary-item"><span class="summary-label">Wilayah Kerja</span><span class="summary-value" id="sum_wilayah">-</span></div>
        </div>

        <div style="background: #F8FAFC; border-radius: 14px; padding: 22px; margin-bottom: 22px;">
            <p class="fw-bold mb-3" style="color: #374151; font-size: 14px;"><i class="bi bi-tools text-success me-2"></i>Keahlian</p>
            <div class="summary-item"><span class="summary-label">Spesialisasi</span><span class="summary-value" id="sum_spesialisasi">-</span></div>
            <div class="summary-item"><span class="summary-label">Kemampuan</span><span class="summary-value" id="sum_kemampuan">-</span></div>
        </div>

        <!-- Persetujuan -->
        <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 14px; padding: 18px; margin-bottom: 22px;">
            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                <input type="checkbox" id="agreeCheck" style="width: 18px; height: 18px; accent-color: #2563EB; margin-top: 2px; flex-shrink: 0;" required>
                <span style="font-size: 13px; color: #92400E; font-weight: 600; line-height: 1.5;">
                    Saya menyatakan bahwa semua informasi yang saya berikan adalah <strong>benar dan dapat dipertanggungjawabkan</strong>. Saya bersedia mengikuti proses seleksi dan pelatihan yang ditetapkan oleh AC Service.
                </span>
            </label>
        </div>

        <div class="nav-buttons">
            <button type="button" class="btn-prev" onclick="goToStep(3)">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button type="submit" class="btn-submit-final" id="btnFinalSubmit">
                <i class="bi bi-send-fill me-2"></i> Kirim Pendaftaran
            </button>
        </div>
    </div>

</form>
<?php endif; ?>

</div><!-- end form-wrapper -->

<footer class="py-4 text-center text-muted" style="font-size: 13px;">
    © 2026 AC Service - Platform Perawatan AC Terpercaya
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var currentStep = 1;
    var totalSteps = 4;

    function goToStep(step) {
        if (step > currentStep) {
            if (!validateStep(currentStep)) return;
        }

        // Kumpulkan kemampuan sebelum pindah step
        collectKemampuan();

        // Hide semua section
        for (var i = 1; i <= totalSteps; i++) {
            var sec = document.getElementById('section' + i);
            if (sec) {
                sec.classList.remove('active');
            }
        }

        // Update step circles
        for (var i = 1; i <= totalSteps; i++) {
            var circle = document.getElementById('step-circle-' + i);
            var label = document.getElementById('step-label-' + i);
            var line = document.getElementById('step-line-' + i);

            if (circle) {
                circle.classList.remove('active', 'done');
                if (i < step) { circle.classList.add('done'); circle.innerHTML = '<i class="bi bi-check-lg"></i>'; }
                else if (i === step) { circle.classList.add('active'); circle.innerHTML = i; }
                else { circle.innerHTML = i; }
            }
            if (label) {
                label.classList.remove('active');
                if (i === step) label.classList.add('active');
            }
            if (line) {
                line.classList.remove('done');
                if (i < step) line.classList.add('done');
            }
        }

        // Show target section
        var targetSec = document.getElementById('section' + step);
        if (targetSec) targetSec.classList.add('active');

        // Update progress bar
        var progress = (step / totalSteps) * 100;
        document.getElementById('progressFill').style.width = progress + '%';

        currentStep = step;

        // If step 4, fill summary
        if (step === 4) fillSummary();

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        if (step === 1) {
            var nama = document.getElementById('f_nama').value.trim();
            var email = document.getElementById('f_email').value.trim();
            var telepon = document.getElementById('f_telepon').value.trim();
            var wilayah = document.getElementById('f_wilayah').value.trim();
            if (!nama || !email || !telepon || !wilayah) {
                alert('Nama, email, telepon, dan wilayah kerja wajib diisi!');
                return false;
            }
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Format email tidak valid!');
                return false;
            }
        }
        if (step === 3) {
            var spesialisasi = document.getElementById('f_spesialisasi').value;
            var motivasi = document.getElementById('f_motivasi').value.trim();
            if (!spesialisasi) {
                alert('Pilih spesialisasi utama Anda!');
                return false;
            }
            if (!motivasi) {
                alert('Motivasi bergabung wajib diisi!');
                return false;
            }
        }
        return true;
    }

    function collectKemampuan() {
        var checkboxes = document.querySelectorAll('input[name="kemampuan_list[]"]:checked');
        var vals = [];
        checkboxes.forEach(function(cb) { vals.push(cb.value); });
        document.getElementById('hiddenKemampuan').value = vals.join(', ');
    }

    function updateKemampuanStyle(checkbox) {
        var label = checkbox.closest('.kemampuan-item');
        if (checkbox.checked) {
            label.classList.add('checked');
        } else {
            label.classList.remove('checked');
        }
        collectKemampuan();
    }

    function fillSummary() {
        document.getElementById('sum_nama').textContent = document.getElementById('f_nama').value || '-';
        document.getElementById('sum_email').textContent = document.getElementById('f_email').value || '-';
        document.getElementById('sum_telepon').textContent = document.getElementById('f_telepon').value || '-';
        document.getElementById('sum_wilayah').textContent = document.getElementById('f_wilayah').value || '-';
        document.getElementById('sum_spesialisasi').textContent = document.getElementById('f_spesialisasi').value || '-';
        document.getElementById('sum_kemampuan').textContent = document.getElementById('hiddenKemampuan').value || 'Tidak ada';
    }

    // Validasi submit akhir
    document.getElementById('mainForm')?.addEventListener('submit', function(e) {
        var agreeCheck = document.getElementById('agreeCheck');
        if (agreeCheck && !agreeCheck.checked) {
            e.preventDefault();
            alert('Centang persetujuan terlebih dahulu!');
        }
        collectKemampuan();
    });
</script>
</body>
</html>
