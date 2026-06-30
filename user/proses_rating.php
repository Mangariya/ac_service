<?php
session_start();
include '../config/database.php';

$user = $_SESSION['user'] ?? null;
$user_id = $user['id'] ?? null;

if (!$user_id) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: riwayat.php");
    exit;
}

$booking_id  = intval($_POST['booking_id'] ?? 0);
$teknisi_id  = intval($_POST['teknisi_id'] ?? 0);
$layanan     = trim($_POST['layanan'] ?? '');
$bintang     = intval($_POST['bintang'] ?? 0);
$komentar    = trim($_POST['komentar'] ?? '');

// Validasi bintang
if ($bintang < 1 || $bintang > 5) {
    $_SESSION['error_rating'] = 'Rating tidak valid. Harap pilih antara 1-5 bintang.';
    header("Location: beri_rating.php?booking_id=$booking_id");
    exit;
}

// Validasi booking milik user ini & status Selesai
$stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'Selesai'");
$stmt->execute([$booking_id, $user_id]);
if (!$stmt->fetch()) {
    header("Location: riwayat.php");
    exit;
}

// Cek duplikat rating
$stmt_cek = $conn->prepare("SELECT id FROM ratings WHERE booking_id = ?");
$stmt_cek->execute([$booking_id]);
if ($stmt_cek->fetch()) {
    $_SESSION['info_rating'] = 'Anda sudah memberikan rating untuk booking ini.';
    header("Location: riwayat.php");
    exit;
}

// Sanitasi komentar
$komentar = htmlspecialchars(strip_tags($komentar), ENT_QUOTES, 'UTF-8');
if (strlen($komentar) > 500) {
    $komentar = substr($komentar, 0, 500);
}

// Simpan rating
try {
    $insert = $conn->prepare("
        INSERT INTO ratings (booking_id, user_id, teknisi_id, layanan, bintang, komentar, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute([$booking_id, $user_id, $teknisi_id, $layanan, $bintang, $komentar]);

    $_SESSION['rating_success'] = true;
    header("Location: riwayat.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['error_rating'] = 'Gagal menyimpan rating. Silakan coba lagi.';
    header("Location: beri_rating.php?booking_id=$booking_id");
    exit;
}
?>
