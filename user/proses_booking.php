<?php
session_start();
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

$user_id = $_SESSION['user']['id'] ?? null;

$teknisi_id = trim($_POST['teknisi_id'] ?? '');
$layanan = trim($_POST['layanan'] ?? '');
$tanggal = trim($_POST['tanggal'] ?? '');
$waktu = trim($_POST['waktu'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');

$pelanggan_nama = trim($_POST['pelanggan_nama'] ?? '');
$pelanggan_email = trim($_POST['pelanggan_email'] ?? '');
$pelanggan_telepon = trim($_POST['pelanggan_telepon'] ?? '');
$pelanggan_lokasi = trim($_POST['pelanggan_lokasi'] ?? '');
$pelanggan_catatan = trim($_POST['pelanggan_catatan'] ?? '');
$keluhan = trim($_POST['keluhan'] ?? '');
$lat = trim($_POST['lat'] ?? '');
$lng = trim($_POST['lng'] ?? '');

if (
    !$teknisi_id ||
    !$layanan ||
    !$tanggal ||
    !$waktu ||
    !$alamat ||
    !$pelanggan_nama ||
    !$pelanggan_email ||
    !$pelanggan_telepon ||
    !$pelanggan_lokasi
) {
    $_SESSION['booking_error'] = 'Lengkapi semua data booking terlebih dahulu.';
    header('Location: booking.php?teknisi_id=' . urlencode($teknisi_id) . '&layanan=' . urlencode($layanan));
    exit;
}

try {
    $cek_kolom = $conn->prepare("
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'bookings'
            AND column_name = 'teknisi_id'
        )
    ");
    $cek_kolom->execute();
    $has_teknisi_column = (bool) $cek_kolom->fetchColumn();

    if (!$has_teknisi_column) {
        $_SESSION['booking_error'] = 'Kolom teknisi_id belum ada di tabel bookings. Jalankan ALTER TABLE terlebih dahulu.';
        header('Location: booking.php?teknisi_id=' . urlencode($teknisi_id) . '&layanan=' . urlencode($layanan));
        exit;
    }

    $stmt_cek = $conn->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE teknisi_id = ?
        AND tanggal = ?
        AND waktu = ?
        AND status != 'Dibatalkan'
    ");
    $stmt_cek->execute([$teknisi_id, $tanggal, $waktu]);
    $sudah_ada = $stmt_cek->fetchColumn();

    if ($sudah_ada > 0) {
        $_SESSION['booking_error'] = 'Maaf, teknisi ini sudah memiliki jadwal di waktu tersebut. Silakan pilih jadwal lain.';
        header('Location: booking.php?teknisi_id=' . urlencode($teknisi_id) . '&layanan=' . urlencode($layanan));
        exit;
    }

    if ($layanan === 'Cuci AC') {
        $harga = 75000;
    } elseif ($layanan === 'Perbaikan AC') {
        $harga = 150000;
    } elseif ($layanan === 'Isi Freon') {
        $harga = 200000;
    } else {
        $harga = 50000;
    }

    $catatan = 'Pelanggan: ' . $pelanggan_nama;
    $catatan .= ' | Email: ' . $pelanggan_email;
    $catatan .= ' | Telp: ' . $pelanggan_telepon;
    $catatan .= ' | Lokasi: ' . $pelanggan_lokasi;

    if (!empty($keluhan)) {
        $catatan .= ' | Keluhan: ' . $keluhan;
    }

    if (!empty($pelanggan_catatan)) {
        $catatan .= ' | Catatan Pelanggan: ' . $pelanggan_catatan;
    }

    if (!empty($lat) && !empty($lng)) {
        $catatan .= ' | Koordinat: ' . $lat . ', ' . $lng;
    }

    // Jika user belum login, coba cari user berdasarkan email. Jika tidak ada, buat akun baru otomatis (guest->user)
    if (!$user_id) {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt_user->execute([$pelanggan_email]);
        $existing_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $user_id = $existing_user['id'];
            $_SESSION['user'] = [
                'id' => $existing_user['id'],
                'nama' => $existing_user['nama'],
                'email' => $existing_user['email'],
                'telepon' => $existing_user['telepon'] ?? '',
                'role' => $existing_user['role'] ?? 'user'
            ];
        } else {
            // buat password random agar kolom password terisi
            $random_pass = bin2hex(random_bytes(8));
            $password_hash = password_hash($random_pass, PASSWORD_DEFAULT);

            $role = 'user';
            $status_acc = 'approved';

            $ins = $conn->prepare("INSERT INTO users (nama, email, telepon, password, role, status_acc) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
            $ins->execute([$pelanggan_nama, $pelanggan_email, $pelanggan_telepon, $password_hash, $role, $status_acc]);
            $new_user_id = $ins->fetchColumn();

            if ($new_user_id) {
                $user_id = $new_user_id;
                $_SESSION['user'] = [
                    'id' => $new_user_id,
                    'nama' => $pelanggan_nama,
                    'email' => $pelanggan_email,
                    'telepon' => $pelanggan_telepon,
                    'role' => 'user'
                ];
            }
        }
    }

    // Siapkan query booking termasuk user_id jika tersedia
    if ($user_id) {
        $sql = "
            INSERT INTO bookings 
            (user_id, teknisi_id, layanan, tanggal, waktu, alamat, catatan, status, harga)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu', ?)
            RETURNING id
        ";

        $params = [
            $user_id,
            $teknisi_id,
            $layanan,
            $tanggal,
            $waktu,
            $alamat,
            $catatan,
            $harga
        ];
    } else {
        $sql = "
            INSERT INTO bookings 
            (teknisi_id, layanan, tanggal, waktu, alamat, catatan, status, harga)
            VALUES (?, ?, ?, ?, ?, ?, 'Menunggu', ?)
            RETURNING id
        ";

        $params = [
            $teknisi_id,
            $layanan,
            $tanggal,
            $waktu,
            $alamat,
            $catatan,
            $harga
        ];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $booking_id = $stmt->fetchColumn();

    $_SESSION['last_booking'] = [
        'id' => $booking_id,
        'teknisi_id' => $teknisi_id,
        'layanan' => $layanan,
        'tanggal' => $tanggal,
        'waktu' => $waktu,
        'alamat' => $alamat
    ];

    header('Location: booking-sukses.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['booking_error'] = 'Terjadi masalah saat menyimpan booking: ' . $e->getMessage();
    header('Location: booking.php?teknisi_id=' . urlencode($teknisi_id) . '&layanan=' . urlencode($layanan));
    exit;
}