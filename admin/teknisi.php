<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $telepon = trim($_POST['telepon'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
        $spesialisasi = trim($_POST['spesialisasi'] ?? '');
        $wilayah = trim($_POST['wilayah'] ?? '');

        if (empty($nama) || empty($email) || empty($telepon) || empty($password) || empty($konfirmasi_password)) {
            $error = 'Nama, email, telepon, password, dan konfirmasi password wajib diisi.';
        } elseif (substr($email, -12) !== '@teknisi.com') {
            $error = 'Email teknisi wajib menggunakan domain @teknisi.com.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $konfirmasi_password) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check->execute([$email]);

            if ($stmt_check->fetchColumn() > 0) {
                $error = 'Email teknisi sudah terdaftar.';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO users 
                        (nama, email, password, role, telepon, status_acc, foto_profil, spesialisasi, wilayah)
                    VALUES 
                        (?, ?, ?, 'teknisi', ?, 'approved', 'default_user.png', ?, ?)
                ");

                $stmt->execute([
                    $nama,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $telepon,
                    $spesialisasi,
                    $wilayah
                ]);

                $success = 'Teknisi baru berhasil didaftarkan.';
            }
        }
    }

    if ($aksi === 'edit') {
        $id = $_POST['id'] ?? '';
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $telepon = trim($_POST['telepon'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
        $spesialisasi = trim($_POST['spesialisasi'] ?? '');
        $wilayah = trim($_POST['wilayah'] ?? '');

        if (empty($id) || empty($nama) || empty($email) || empty($telepon)) {
            $error = 'Nama, email, dan telepon wajib diisi.';
        } elseif (substr($email, -12) !== '@teknisi.com') {
            $error = 'Email teknisi wajib menggunakan domain @teknisi.com.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif (!empty($password) && $password !== $konfirmasi_password) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt_check->execute([$email, $id]);

            if ($stmt_check->fetchColumn() > 0) {
                $error = 'Email teknisi sudah digunakan oleh akun lain.';
            } else {
                if (!empty($password)) {
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET nama = ?, email = ?, telepon = ?, password = ?, spesialisasi = ?, wilayah = ?, role = 'teknisi'
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nama,
                        $email,
                        $telepon,
                        password_hash($password, PASSWORD_DEFAULT),
                        $spesialisasi,
                        $wilayah,
                        $id
                    ]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET nama = ?, email = ?, telepon = ?, spesialisasi = ?, wilayah = ?, role = 'teknisi'
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nama,
                        $email,
                        $telepon,
                        $spesialisasi,
                        $wilayah,
                        $id
                    ]);
                }

                $success = 'Data teknisi berhasil diperbarui.';
            }
        }
    }

    if ($aksi === 'hapus') {
        $id = $_POST['id'] ?? '';

        if (!empty($id)) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND (role = 'teknisi' OR email LIKE '%@teknisi.com')");
            $stmt->execute([$id]);

            $success = 'Data teknisi berhasil dihapus.';
        }
    }
}

$stmt = $conn->prepare("
    SELECT u.id, u.nama, u.email, u.telepon, u.spesialisasi, u.wilayah, u.created_at,
           ROUND(AVG(r.bintang)::numeric, 1) as avg_rating,
           COUNT(r.id) as total_review
    FROM users u
    LEFT JOIN ratings r ON r.teknisi_id = u.id
    WHERE u.role = 'teknisi' OR u.email LIKE '%@teknisi.com'
    GROUP BY u.id, u.nama, u.email, u.telepon, u.spesialisasi, u.wilayah, u.created_at
    ORDER BY u.id DESC
");
$stmt->execute();
$teknisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung calon teknisi yang menunggu
$cek_tbl = $conn->prepare("
    SELECT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'pendaftaran_teknisi'
    )
");
$cek_tbl->execute();
$tbl_exists = (bool) $cek_tbl->fetchColumn();
$pending_calon = 0;
if ($tbl_exists) {
    $cStmt = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_teknisi WHERE status = 'menunggu'");
    $cStmt->execute();
    $pending_calon = (int) $cStmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Teknisi - AC Service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #DFF0FA;
            color: #1E293B;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background: white;
            border-right: 1px solid #E2E8F0;
            padding-top: 20px;
        }

        .sidebar-brand {
            padding: 0 30px 30px;
            font-size: 22px;
            font-weight: 800;
            color: #1E293B;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #64748B;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #EFF6FF;
            color: #2563EB;
            border-right: 4px solid #2563EB;
        }

        .logout-box {
            position: absolute;
            bottom: 30px;
            width: 100%;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            height: 90px;
            background: white;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 38px;
        }

        .top-left {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .hamburger {
            font-size: 30px;
            color: #475569;
        }

        .top-title h3 {
            font-weight: 800;
            margin-bottom: 3px;
        }

        .top-title p {
            margin: 0;
            color: #64748B;
            font-size: 14px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .notification {
            position: relative;
            font-size: 24px;
            color: #475569;
        }

        .notification span {
            position: absolute;
            top: -7px;
            right: -7px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #EF4444;
            color: white;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #EAF4FF;
            color: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .content-area {
            padding: 42px 72px 44px;
        }

        .page-grid {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 410px 1fr;
            gap: 28px;
            align-items: start;
        }

        .form-card,
        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 13px rgba(15, 23, 42, 0.16);
            padding: 26px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 18px;
        }

        .form-label {
            font-weight: 700;
            color: #475569;
            font-size: 13px;
        }

        .form-control {
            border-radius: 10px;
            padding: 11px 13px;
            border: 1px solid #CBD5E1;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 10px;
            background: #0B73F6;
            color: white;
            font-weight: 800;
            box-shadow: 0 7px 12px rgba(11, 115, 246, 0.28);
            margin-top: 8px;
        }

        .alert {
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        .table-wrapper {
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #F8FAFC;
            color: #1E293B;
            font-size: 13px;
            font-weight: 800;
            padding: 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px;
            vertical-align: middle;
            font-size: 13px;
            font-weight: 600;
            border-bottom: 1px solid #F1F5F9;
        }

        .badge-teknisi {
            background: #E7EDFF;
            color: #2563EB;
            border-radius: 8px;
            padding: 6px 10px;
            font-weight: 800;
            font-size: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit,
        .btn-delete {
            border: none;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .btn-edit {
            background: #E7EDFF;
            color: #2563EB;
        }

        .btn-delete {
            background: #FFE4E4;
            color: #EF4444;
        }

        .badge-notif-sidebar {
            background: #EF4444;
            color: white;
            border-radius: 50px;
            padding: 1px 7px;
            font-size: 10px;
            font-weight: 800;
            margin-left: auto;
        }

        .modal-content {
            border: 0;
            border-radius: 18px;
        }

        @media(max-width: 992px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .logout-box {
                position: static;
                margin-top: 20px;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                height: auto;
                padding: 24px;
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .content-area {
                padding: 24px 18px;
            }

            .page-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-snow2 text-primary" style="font-size: 28px;"></i>
        AC SERVICE
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php">
                <i class="bi bi-grid-fill"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="manajemen_layanan.php">
                <i class="bi bi-briefcase"></i>
                Manajemen layanan
            </a>
        </li>

        <li>
            <a href="reservasi.php">
                <i class="bi bi-calendar-check"></i>
                Reservasi
            </a>
        </li>

        <li>
            <a href="laporan.php">
                <i class="bi bi-file-earmark-bar-graph"></i>
                Laporan
            </a>
        </li>

        <li>
            <a href="teknisi.php" class="active">
                <i class="bi bi-person-plus"></i>
                Daftar Teknisi
            </a>
        </li>

        <li>
            <a href="calon_teknisi.php">
                <i class="bi bi-person-check"></i>
                Calon Teknisi
                <?php if ($pending_calon > 0): ?>
                    <span class="badge-notif-sidebar"><?= $pending_calon ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li>
            <a href="bantuan.php">
                <i class="bi bi-question-circle"></i>
                Bantuan
            </a>
        </li>
    </ul>

    <div class="logout-box">
        <a href="../auth/logout.php" class="text-danger fw-bold" style="padding-left: 30px; text-decoration:none;">
            <i class="bi bi-box-arrow-left me-2"></i>
            Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="top-left">
            <i class="bi bi-list hamburger"></i>

            <div class="top-title">
                <h3>Pendaftaran Teknisi</h3>
                <p>Tambahkan dan kelola akun teknisi.</p>
            </div>
        </div>

        <div class="top-right">
            <div class="notification">
                <i class="bi bi-bell"></i>
                <span>5</span>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin'); ?></span>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
    </div>

    <div class="content-area">
        <div class="page-grid">
            <div class="form-card">
                <h4 class="card-title">Form Pendaftaran Teknisi</h4>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="aksi" value="tambah">

                    <div class="mb-3">
                        <label class="form-label">Nama Teknisi</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Mangariya" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Teknisi</label>
                        <input type="email" name="email" class="form-control" placeholder="contoh@teknisi.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No Telepon</label>
                        <input type="text" name="telepon" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Spesialisasi</label>
                        <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: AC Inverter & Cuci Steam">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Wilayah</label>
                        <input type="text" name="wilayah" class="form-control" placeholder="Contoh: Denpasar">
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-person-plus me-2"></i>
                        Daftarkan Teknisi
                    </button>
                </form>
            </div>

            <div class="table-card">
                <h4 class="card-title">Daftar Teknisi Terdaftar</h4>

                <div class="table-wrapper table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Spesialisasi</th>
                                <th>Wilayah</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($teknisi) > 0): ?>
                                <?php foreach ($teknisi as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1; ?></td>
                                        <td><?= htmlspecialchars($row['nama'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['telepon'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars(!empty($row['spesialisasi']) ? $row['spesialisasi'] : '-'); ?></td>
                                        <td><?= htmlspecialchars(!empty($row['wilayah']) ? $row['wilayah'] : '-'); ?></td>
                                        <td>
                                            <?php if ($row['total_review'] > 0): ?>
                                                <div style="color:#F59E0B; font-size:13px;">
                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                        <i class="bi bi-star<?= $s <= round($row['avg_rating']) ? '-fill' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <small class="text-muted"><?= $row['avg_rating'] ?> (<?= $row['total_review'] ?> ulasan)</small>
                                            <?php else: ?>
                                                <small class="text-muted">Belum ada ulasan</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-teknisi">Teknisi</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button
                                                    type="button"
                                                    class="btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit"
                                                    data-id="<?= $row['id']; ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES); ?>"
                                                    data-email="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?>"
                                                    data-telepon="<?= htmlspecialchars($row['telepon'] ?? '', ENT_QUOTES); ?>"
                                                    data-spesialisasi="<?= htmlspecialchars($row['spesialisasi'] ?? '', ENT_QUOTES); ?>"
                                                    data-wilayah="<?= htmlspecialchars($row['wilayah'] ?? '', ENT_QUOTES); ?>"
                                                >
                                                    Edit
                                                </button>

                                                <form method="POST" onsubmit="return confirm('Hapus teknisi ini?')">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                    <button type="submit" class="btn-delete">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada teknisi yang terdaftar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Teknisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Teknisi</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Teknisi</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">No Telepon</label>
                    <input type="text" name="telepon" id="edit_telepon" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diganti">
                </div>

                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" class="form-control" placeholder="Kosongkan jika tidak diganti">
                </div>

                <div class="mb-3">
                    <label class="form-label">Spesialisasi</label>
                    <input type="text" name="spesialisasi" id="edit_spesialisasi" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Wilayah</label>
                    <input type="text" name="wilayah" id="edit_wilayah" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modalEdit = document.getElementById('modalEdit');

    modalEdit.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_nama').value = button.getAttribute('data-nama');
        document.getElementById('edit_email').value = button.getAttribute('data-email');
        document.getElementById('edit_telepon').value = button.getAttribute('data-telepon');
        document.getElementById('edit_spesialisasi').value = button.getAttribute('data-spesialisasi');
        document.getElementById('edit_wilayah').value = button.getAttribute('data-wilayah');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>