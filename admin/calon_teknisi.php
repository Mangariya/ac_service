<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Buat tabel jika belum ada
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
} catch (PDOException $e) { /* already exists */ }

$success = '';
$error   = '';

// ─── Aksi ACC / Tolak ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi        = $_POST['aksi'] ?? '';
    $calon_id    = intval($_POST['calon_id'] ?? 0);
    $catatan     = trim($_POST['catatan_admin'] ?? '');

    // ── TOLAK ──────────────────────────────────────────────────────
    if ($aksi === 'tolak' && $calon_id > 0) {
        $stmt = $conn->prepare("
            UPDATE pendaftaran_teknisi
            SET status = 'ditolak', catatan_admin = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$catatan, $calon_id]);
        $success = 'Pendaftaran berhasil ditolak.';
    }

    // ── ACC → Daftarkan sebagai Teknisi + Kirim Email ───────────────
    if ($aksi === 'acc' && $calon_id > 0) {
        // Ambil data calon
        $stmtCalon = $conn->prepare("SELECT * FROM pendaftaran_teknisi WHERE id = ?");
        $stmtCalon->execute([$calon_id]);
        $calon = $stmtCalon->fetch(PDO::FETCH_ASSOC);

        if (!$calon) {
            $error = 'Data calon teknisi tidak ditemukan.';
        } else {
            // Generate email @teknisi.com dari nama
            $base_email = strtolower(preg_replace('/\s+/', '.', $calon['nama']));
            $base_email = preg_replace('/[^a-z0-9.]/', '', $base_email);
            $teknisi_email = $base_email . '@teknisi.com';

            // Pastikan unik
            $counter = 1;
            while (true) {
                $cek = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $cek->execute([$teknisi_email]);
                if ($cek->fetchColumn() == 0) break;
                $teknisi_email = $base_email . $counter . '@teknisi.com';
                $counter++;
            }

            // Generate password random (8 karakter)
            $plain_password = strtoupper(substr(md5(uniqid()), 0, 4)) . rand(10, 99) . '!';

            // Insert ke users
            try {
                $stmtInsert = $conn->prepare("
                    INSERT INTO users
                        (nama, email, password, role, telepon, status_acc, foto_profil, spesialisasi, wilayah)
                    VALUES
                        (?, ?, ?, 'teknisi', ?, 'approved', 'default_user.png', ?, ?)
                ");
                $stmtInsert->execute([
                    $calon['nama'],
                    $teknisi_email,
                    password_hash($plain_password, PASSWORD_DEFAULT),
                    $calon['telepon'],
                    $calon['spesialisasi'] ?? '',
                    $calon['wilayah'] ?? ''
                ]);

                // Update status pendaftaran
                $stmtUpdate = $conn->prepare("
                    UPDATE pendaftaran_teknisi
                    SET status = 'diterima', catatan_admin = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$catatan, $calon_id]);

                // ── Kirim Email ───────────────────────────────────────
                $to      = $calon['email'];
                $subject = 'Selamat! Pendaftaran Teknisi AC Service Anda Diterima';

                $body = "
Halo " . htmlspecialchars($calon['nama']) . ",

Selamat! Pendaftaran Anda sebagai teknisi di AC Service telah DITERIMA.

Tim kami telah membuat akun teknisi untuk Anda. Berikut informasi login Anda:

=========================================
   INFORMASI AKUN TEKNISI
=========================================
   Email Login : {$teknisi_email}
   Password    : {$plain_password}
   URL Login   : http://localhost/ac_service/auth/login.php
=========================================

PENTING:
- Segera ganti password Anda setelah login pertama kali
- Jaga kerahasiaan password Anda
- Jangan bagikan informasi login kepada siapapun

Wilayah Kerja  : " . ($calon['wilayah'] ?? '-') . "
Spesialisasi   : " . ($calon['spesialisasi'] ?? '-') . "
";
                if (!empty($catatan)) {
                    $body .= "\nCatatan dari Admin:\n{$catatan}\n";
                }
                $body .= "
Jika ada pertanyaan, silakan hubungi admin kami.

Salam,
Tim AC Service
                ";

                $headers  = "From: noreply@acservice.com\r\n";
                $headers .= "Reply-To: admin@acservice.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                $mail_sent = @mail($to, $subject, $body, $headers);

                $success = "Teknisi <strong>" . htmlspecialchars($calon['nama']) . "</strong> berhasil didaftarkan!<br>"
                         . "Email login: <code>{$teknisi_email}</code> | Password: <code>{$plain_password}</code><br>"
                         . ($mail_sent ? "✅ Email berhasil dikirim ke <strong>{$calon['email']}</strong>" : "⚠️ Email gagal terkirim (SMTP belum dikonfigurasi). Sampaikan akun secara manual.");

            } catch (PDOException $e) {
                $error = 'Gagal mendaftarkan teknisi: ' . $e->getMessage();
            }
        }
    }

    // ── HAPUS (permanen) ──────────────────────────────────────────
    if ($aksi === 'hapus' && $calon_id > 0) {
        $conn->prepare("DELETE FROM pendaftaran_teknisi WHERE id = ?")->execute([$calon_id]);
        $success = 'Data pendaftaran berhasil dihapus.';
    }
}

// ─── Filter ──────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'menunggu';
$where_filter  = '';
$params_filter = [];

if ($filter_status !== 'semua') {
    $where_filter  = 'WHERE status = ?';
    $params_filter = [$filter_status];
}

$stmtList = $conn->prepare("SELECT * FROM pendaftaran_teknisi $where_filter ORDER BY created_at DESC");
$stmtList->execute($params_filter);
$calon_list = $stmtList->fetchAll(PDO::FETCH_ASSOC);

// Count untuk badge
$counts = [];
foreach (['menunggu', 'diterima', 'ditolak'] as $s) {
    $c = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_teknisi WHERE status = ?");
    $c->execute([$s]);
    $counts[$s] = $c->fetchColumn();
}
$counts['semua'] = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calon Teknisi - AC Service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #DFF0FA; color: #1E293B; }

        /* ── Sidebar ── */
        .sidebar { height: 100vh; width: 260px; position: fixed; top: 0; left: 0; background: white; border-right: 1px solid #E2E8F0; padding-top: 20px; }
        .sidebar-brand { padding: 0 30px 30px; font-size: 22px; font-weight: 800; color: #1E293B; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { padding: 12px 30px; display: flex; align-items: center; gap: 15px; color: #64748B; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #EFF6FF; color: #2563EB; border-right: 4px solid #2563EB; }
        .logout-box { position: absolute; bottom: 30px; width: 100%; }
        .badge-notif { background: #EF4444; color: white; border-radius: 50px; padding: 1px 8px; font-size: 11px; font-weight: 800; margin-left: auto; }

        /* ── Main ── */
        .main-content { margin-left: 260px; min-height: 100vh; }
        .topbar { height: 90px; background: white; box-shadow: 0 3px 10px rgba(15,23,42,0.18); display: flex; align-items: center; justify-content: space-between; padding: 0 38px; }
        .top-left { display: flex; align-items: center; gap: 28px; }
        .hamburger { font-size: 30px; color: #475569; }
        .top-title h3 { font-weight: 800; margin-bottom: 3px; }
        .top-title p { margin: 0; color: #64748B; font-size: 14px; }
        .top-right { display: flex; align-items: center; gap: 22px; }
        .admin-profile { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; background: #EAF4FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .content-area { padding: 36px 52px; }

        /* ── Filter Tabs ── */
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid #E2E8F0;
            background: white;
            color: #64748B;
            text-decoration: none;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover { border-color: #93C5FD; color: #2563EB; background: #EFF6FF; }
        .tab-btn.active-tab { background: #2563EB; color: white; border-color: #2563EB; }
        .tab-count { background: rgba(255,255,255,0.25); border-radius: 50px; padding: 1px 8px; font-size: 11px; }
        .tab-btn:not(.active-tab) .tab-count { background: #F1F5F9; color: #64748B; }

        /* ── Stats ── */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 14px; padding: 20px 22px; box-shadow: 0 4px 14px rgba(15,23,42,0.1); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 54px; height: 54px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .stat-val { font-size: 28px; font-weight: 800; line-height: 1; }
        .stat-lbl { font-size: 13px; font-weight: 600; color: #64748B; margin-top: 3px; }

        /* ── Table Card ── */
        .table-card { background: white; border-radius: 16px; box-shadow: 0 4px 14px rgba(15,23,42,0.1); overflow: hidden; }
        .table-card-header { padding: 20px 24px; border-bottom: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between; }
        .table-card-header h4 { font-size: 18px; font-weight: 800; color: #111827; margin: 0; }
        .table-wrapper { overflow-x: auto; }
        table { margin: 0; width: 100%; border-collapse: collapse; }
        thead th { background: #F8FAFC; font-size: 12px; font-weight: 800; color: #64748B; padding: 12px 16px; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 14px 16px; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F8FAFC; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #FAFCFF; }

        /* Status Pills */
        .pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }
        .pill-menunggu { background: #FFF3D8; color: #D97706; }
        .pill-diterima { background: #D1FAE5; color: #059669; }
        .pill-ditolak  { background: #FEE2E2; color: #DC2626; }

        /* Buttons */
        .btn-detail { background: #EFF6FF; color: #2563EB; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-detail:hover { background: #DBEAFE; }
        .btn-acc { background: #D1FAE5; color: #059669; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-acc:hover { background: #A7F3D0; }
        .btn-tolak { background: #FEE2E2; color: #DC2626; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-tolak:hover { background: #FECACA; }
        .btn-hapus { background: #F1F5F9; color: #94A3B8; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-hapus:hover { background: #E2E8F0; color: #64748B; }

        /* Alert */
        .alert-box { border-radius: 12px; padding: 14px 20px; font-size: 14px; font-weight: 600; margin-bottom: 22px; }
        .alert-success-box { background: #D1FAE5; border: 1.5px solid #6EE7B7; color: #065F46; }
        .alert-danger-box { background: #FEE2E2; border: 1.5px solid #FCA5A5; color: #991B1B; }

        /* Modal */
        .modal-content { border: 0; border-radius: 20px; }
        .modal-header { border-bottom: 1px solid #F1F5F9; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #F1F5F9; padding: 16px 24px; }
        .detail-row { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid #F8FAFC; }
        .detail-label { font-size: 12px; font-weight: 700; color: #9CA3AF; min-width: 160px; }
        .detail-value { font-size: 13px; font-weight: 600; color: #1F2937; }
        .section-divider { font-size: 12px; font-weight: 800; color: #6B7280; text-transform: uppercase; letter-spacing: 1px; margin: 16px 0 8px; padding: 8px 12px; background: #F8FAFC; border-radius: 8px; }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }
        .empty-icon { font-size: 52px; margin-bottom: 16px; display: block; }

        @media(max-width: 992px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .logout-box { position: static; margin-top: 20px; }
            .main-content { margin-left: 0; }
            .topbar { height: auto; padding: 20px; flex-direction: column; align-items: flex-start; gap: 14px; }
            .content-area { padding: 20px; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────── -->
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-snow2 text-primary" style="font-size:28px;"></i>
        AC SERVICE
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <li><a href="manajemen_layanan.php"><i class="bi bi-briefcase"></i> Manajemen Layanan</a></li>
        <li><a href="reservasi.php"><i class="bi bi-calendar-check"></i> Reservasi</a></li>
        <li><a href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a></li>
        <li><a href="teknisi.php"><i class="bi bi-person-plus"></i> Daftar Teknisi</a></li>
        <li>
            <a href="calon_teknisi.php" class="active">
                <i class="bi bi-person-check"></i> Calon Teknisi
                <?php if ($counts['menunggu'] > 0): ?>
                    <span class="badge-notif"><?= $counts['menunggu'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="bantuan.php"><i class="bi bi-question-circle"></i> Bantuan</a></li>
    </ul>
    <div class="logout-box">
        <a href="../auth/logout.php" class="text-danger fw-bold" style="padding-left:30px;text-decoration:none;">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>
    </div>
</div>

<!-- ── Main Content ─────────────────────────────────────── -->
<div class="main-content">
    <div class="topbar">
        <div class="top-left">
            <i class="bi bi-list hamburger"></i>
            <div class="top-title">
                <h3>Calon Teknisi</h3>
                <p>Kelola pendaftaran teknisi baru dari masyarakat.</p>
            </div>
        </div>
        <div class="top-right">
            <div class="admin-profile">
                <div class="profile-icon"><i class="bi bi-person-fill"></i></div>
                <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin') ?></span>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
    </div>

    <div class="content-area">

        <?php if (!empty($success)): ?>
            <div class="alert-box alert-success-box">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-box alert-danger-box">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:#FFF3D8; color:#D97706;"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-val" style="color:#D97706;"><?= $counts['menunggu'] ?></div>
                    <div class="stat-lbl">Menunggu Review</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#D1FAE5; color:#059669;"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-val" style="color:#059669;"><?= $counts['diterima'] ?></div>
                    <div class="stat-lbl">Diterima</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#FEE2E2; color:#DC2626;"><i class="bi bi-person-x-fill"></i></div>
                <div>
                    <div class="stat-val" style="color:#DC2626;"><?= $counts['ditolak'] ?></div>
                    <div class="stat-lbl">Ditolak</div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <?php foreach ([
                'menunggu' => ['label' => 'Menunggu', 'icon' => 'bi-hourglass-split'],
                'diterima' => ['label' => 'Diterima',  'icon' => 'bi-check-circle'],
                'ditolak'  => ['label' => 'Ditolak',   'icon' => 'bi-x-circle'],
                'semua'    => ['label' => 'Semua',      'icon' => 'bi-list-ul'],
            ] as $s => $meta): ?>
                <a href="?status=<?= $s ?>" class="tab-btn <?= $filter_status === $s ? 'active-tab' : '' ?>">
                    <i class="bi <?= $meta['icon'] ?>"></i>
                    <?= $meta['label'] ?>
                    <span class="tab-count"><?= $counts[$s] ?? 0 ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-card-header">
                <h4><i class="bi bi-person-lines-fill me-2 text-primary"></i>Daftar Pendaftar</h4>
                <span style="font-size:13px; color:#9CA3AF; font-weight:600;"><?= count($calon_list) ?> pendaftar</span>
            </div>
            <div class="table-wrapper">
                <?php if (count($calon_list) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email Pribadi</th>
                            <th>Telepon</th>
                            <th>Spesialisasi</th>
                            <th>Wilayah</th>
                            <th>Pengalaman</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calon_list as $i => $c): ?>
                            <?php
                                $exp_label = ['0' => '-', '1' => '< 1 thn', '2' => '1-2 thn', '3' => '3-5 thn', '6' => '6-10 thn', '11' => '> 10 thn'];
                                $exp_str   = $exp_label[$c['pengalaman_tahun']] ?? ($c['pengalaman_tahun'] . ' thn');
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($c['nama']) ?></span><br>
                                    <small class="text-muted"><?= htmlspecialchars($c['jenis_kelamin'] ?? '') ?> <?= !empty($c['pendidikan']) ? '· ' . $c['pendidikan'] : '' ?></small>
                                </td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['telepon']) ?></td>
                                <td><?= htmlspecialchars($c['spesialisasi'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['wilayah'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($exp_str) ?></td>
                                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <?php if ($c['status'] === 'menunggu'): ?>
                                        <span class="pill pill-menunggu"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                    <?php elseif ($c['status'] === 'diterima'): ?>
                                        <span class="pill pill-diterima"><i class="bi bi-check-circle-fill"></i> Diterima</span>
                                    <?php else: ?>
                                        <span class="pill pill-ditolak"><i class="bi bi-x-circle-fill"></i> Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <!-- Detail -->
                                        <button class="btn-detail"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetail"
                                            data-id="<?= $c['id'] ?>"
                                            data-nama="<?= htmlspecialchars($c['nama'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($c['email'], ENT_QUOTES) ?>"
                                            data-telepon="<?= htmlspecialchars($c['telepon'], ENT_QUOTES) ?>"
                                            data-alamat="<?= htmlspecialchars($c['alamat'] ?? '-', ENT_QUOTES) ?>"
                                            data-tgl-lahir="<?= htmlspecialchars($c['tanggal_lahir'] ?? '-', ENT_QUOTES) ?>"
                                            data-jk="<?= htmlspecialchars($c['jenis_kelamin'] ?? '-', ENT_QUOTES) ?>"
                                            data-pendidikan="<?= htmlspecialchars($c['pendidikan'] ?? '-', ENT_QUOTES) ?>"
                                            data-pengalaman-tahun="<?= htmlspecialchars($exp_str, ENT_QUOTES) ?>"
                                            data-pengalaman-kerja="<?= htmlspecialchars($c['pengalaman_kerja'] ?? '-', ENT_QUOTES) ?>"
                                            data-spesialisasi="<?= htmlspecialchars($c['spesialisasi'] ?? '-', ENT_QUOTES) ?>"
                                            data-wilayah="<?= htmlspecialchars($c['wilayah'] ?? '-', ENT_QUOTES) ?>"
                                            data-kemampuan="<?= htmlspecialchars($c['kemampuan'] ?? '-', ENT_QUOTES) ?>"
                                            data-ketersediaan="<?= htmlspecialchars($c['ketersediaan'] ?? '-', ENT_QUOTES) ?>"
                                            data-portofolio="<?= htmlspecialchars($c['portofolio_url'] ?? '-', ENT_QUOTES) ?>"
                                            data-motivasi="<?= htmlspecialchars($c['motivasi'] ?? '-', ENT_QUOTES) ?>"
                                            data-catatan="<?= htmlspecialchars($c['catatan_admin'] ?? '', ENT_QUOTES) ?>"
                                            data-status="<?= $c['status'] ?>"
                                        >
                                            <i class="bi bi-eye"></i> Detail
                                        </button>

                                        <?php if ($c['status'] === 'menunggu'): ?>
                                            <button class="btn-acc"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalAcc"
                                                data-id="<?= $c['id'] ?>"
                                                data-nama="<?= htmlspecialchars($c['nama'], ENT_QUOTES) ?>">
                                                <i class="bi bi-check-lg"></i> Terima
                                            </button>
                                            <button class="btn-tolak"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalTolak"
                                                data-id="<?= $c['id'] ?>"
                                                data-nama="<?= htmlspecialchars($c['nama'], ENT_QUOTES) ?>">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        <?php endif; ?>

                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus data pendaftaran ini?')">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="calon_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn-hapus"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox empty-icon text-muted"></i>
                        <p class="fw-bold mb-1">Belum ada pendaftar</p>
                        <p class="text-muted" style="font-size:13px;">Pendaftar akan muncul di sini ketika ada yang mengisi form pendaftaran teknisi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /content-area -->
</div><!-- /main-content -->

<!-- ── Modal Detail ─────────────────────────────────────── -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>Detail Pendaftar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="section-divider">📋 Data Pribadi</div>
                <div class="detail-row"><span class="detail-label">Nama Lengkap</span><span class="detail-value" id="d_nama">-</span></div>
                <div class="detail-row"><span class="detail-label">Email Pribadi</span><span class="detail-value" id="d_email">-</span></div>
                <div class="detail-row"><span class="detail-label">Telepon / WA</span><span class="detail-value" id="d_telepon">-</span></div>
                <div class="detail-row"><span class="detail-label">Tanggal Lahir</span><span class="detail-value" id="d_tgl_lahir">-</span></div>
                <div class="detail-row"><span class="detail-label">Jenis Kelamin</span><span class="detail-value" id="d_jk">-</span></div>
                <div class="detail-row"><span class="detail-label">Pendidikan Terakhir</span><span class="detail-value" id="d_pendidikan">-</span></div>
                <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value" id="d_alamat">-</span></div>

                <div class="section-divider">💼 Pengalaman Kerja</div>
                <div class="detail-row"><span class="detail-label">Lama Pengalaman</span><span class="detail-value" id="d_pengalaman_tahun">-</span></div>
                <div class="detail-row"><span class="detail-label">Ketersediaan</span><span class="detail-value" id="d_ketersediaan">-</span></div>
                <div class="detail-row" style="flex-direction:column;">
                    <span class="detail-label">Riwayat Pengalaman</span>
                    <div class="detail-value mt-2 p-3 rounded" style="background:#F8FAFC; font-size:13px; white-space:pre-wrap; line-height:1.6;" id="d_pengalaman_kerja">-</div>
                </div>

                <div class="section-divider">🔧 Spesialisasi & Kemampuan</div>
                <div class="detail-row"><span class="detail-label">Spesialisasi Utama</span><span class="detail-value" id="d_spesialisasi">-</span></div>
                <div class="detail-row"><span class="detail-label">Wilayah Kerja</span><span class="detail-value" id="d_wilayah">-</span></div>
                <div class="detail-row" style="flex-direction:column;">
                    <span class="detail-label">Kemampuan Tambahan</span>
                    <div id="d_kemampuan_tags" class="mt-2" style="display:flex;gap:6px;flex-wrap:wrap;"></div>
                </div>
                <div class="detail-row"><span class="detail-label">Portofolio / Medsos</span><span class="detail-value" id="d_portofolio">-</span></div>

                <div class="section-divider">💡 Motivasi</div>
                <div style="background:#F8FAFC; border-radius:10px; padding:14px; font-size:13px; line-height:1.7; font-weight:600; color:#374151; white-space:pre-wrap;" id="d_motivasi">-</div>

                <div id="d_catatan_section" style="display:none; margin-top:16px;">
                    <div class="section-divider">📝 Catatan Admin</div>
                    <div style="background:#FFFBEB; border-radius:10px; padding:14px; font-size:13px; font-weight:600; color:#92400E;" id="d_catatan">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal ACC ─────────────────────────────────────────── -->
<div class="modal fade" id="modalAcc" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="acc">
            <input type="hidden" name="calon_id" id="acc_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color:#059669;"><i class="bi bi-person-check-fill me-2"></i>Terima Pendaftar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="background:#D1FAE5; border-radius:14px; padding:18px; margin-bottom:18px;">
                    <p class="fw-bold mb-1" style="color:#065F46; font-size:14px;"><i class="bi bi-info-circle me-1"></i> Yang akan terjadi saat Anda menekan "Terima":</p>
                    <ul style="color:#065F46; font-size:13px; margin:0; padding-left:18px;">
                        <li>Akun teknisi otomatis dibuat dengan email <strong>@teknisi.com</strong></li>
                        <li>Password acak akan di-generate secara otomatis</li>
                        <li>Email berisi akun & password dikirim ke email pribadi pendaftar</li>
                        <li>Teknisi langsung dapat login ke sistem</li>
                    </ul>
                </div>
                <p style="font-size:14px; font-weight:600;">Anda akan menerima pendaftaran dari: <strong id="acc_nama" class="text-success">-</strong></p>
                <div class="mb-3 mt-3">
                    <label class="form-label fw-bold" style="font-size:13px;">Catatan / Pesan untuk Calon Teknisi (Opsional)</label>
                    <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Contoh: Selamat bergabung! Silakan hadir untuk orientasi pada tanggal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-check-lg me-1"></i> Ya, Terima & Daftarkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal TOLAK ────────────────────────────────────────── -->
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="tolak">
            <input type="hidden" name="calon_id" id="tolak_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color:#DC2626;"><i class="bi bi-person-x-fill me-2"></i>Tolak Pendaftar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px; font-weight:600;">Anda akan menolak pendaftaran dari: <strong id="tolak_nama" class="text-danger">-</strong></p>
                <div class="mb-3 mt-2">
                    <label class="form-label fw-bold" style="font-size:13px;">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="catatan_admin" class="form-control" rows="4" placeholder="Contoh: Kualifikasi belum memenuhi persyaratan. Silakan mendaftar kembali setelah mendapat lebih banyak pengalaman..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger fw-bold px-4">
                    <i class="bi bi-x-lg me-1"></i> Tolak Pendaftaran
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Modal Detail
    document.getElementById('modalDetail').addEventListener('show.bs.modal', function(e) {
        var b = e.relatedTarget;
        document.getElementById('d_nama').textContent        = b.dataset.nama || '-';
        document.getElementById('d_email').textContent       = b.dataset.email || '-';
        document.getElementById('d_telepon').textContent     = b.dataset.telepon || '-';
        document.getElementById('d_tgl_lahir').textContent   = b.dataset.tglLahir || '-';
        document.getElementById('d_jk').textContent          = b.dataset.jk || '-';
        document.getElementById('d_pendidikan').textContent  = b.dataset.pendidikan || '-';
        document.getElementById('d_alamat').textContent      = b.dataset.alamat || '-';
        document.getElementById('d_pengalaman_tahun').textContent = b.dataset.pengalamanTahun || '-';
        document.getElementById('d_ketersediaan').textContent    = b.dataset.ketersediaan || '-';
        document.getElementById('d_pengalaman_kerja').textContent = b.dataset.pengalamanKerja || '-';
        document.getElementById('d_spesialisasi').textContent = b.dataset.spesialisasi || '-';
        document.getElementById('d_wilayah').textContent      = b.dataset.wilayah || '-';
        document.getElementById('d_portofolio').textContent   = b.dataset.portofolio || '-';
        document.getElementById('d_motivasi').textContent     = b.dataset.motivasi || '-';

        // Kemampuan tags
        var tagContainer = document.getElementById('d_kemampuan_tags');
        tagContainer.innerHTML = '';
        var kemampuan = b.dataset.kemampuan || '';
        if (kemampuan && kemampuan !== '-') {
            kemampuan.split(',').forEach(function(k) {
                k = k.trim();
                if (k) {
                    var tag = document.createElement('span');
                    tag.textContent = k;
                    tag.style.cssText = 'background:#EFF6FF;color:#2563EB;border-radius:6px;padding:3px 10px;font-size:12px;font-weight:700;';
                    tagContainer.appendChild(tag);
                }
            });
        } else {
            tagContainer.textContent = 'Tidak ada';
        }

        // Catatan admin
        var catatanSection = document.getElementById('d_catatan_section');
        var catatan = b.dataset.catatan || '';
        if (catatan) {
            catatanSection.style.display = 'block';
            document.getElementById('d_catatan').textContent = catatan;
        } else {
            catatanSection.style.display = 'none';
        }
    });

    // Modal Acc
    document.getElementById('modalAcc').addEventListener('show.bs.modal', function(e) {
        var b = e.relatedTarget;
        document.getElementById('acc_id').value       = b.dataset.id;
        document.getElementById('acc_nama').textContent = b.dataset.nama;
    });

    // Modal Tolak
    document.getElementById('modalTolak').addEventListener('show.bs.modal', function(e) {
        var b = e.relatedTarget;
        document.getElementById('tolak_id').value       = b.dataset.id;
        document.getElementById('tolak_nama').textContent = b.dataset.nama;
    });
</script>
</body>
</html>
