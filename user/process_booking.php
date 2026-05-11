<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user']) || !isset($_SESSION['booking_final'])){
    header("Location: booking.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$b = $_SESSION['booking_final'];

$stmt = $conn->prepare("
    INSERT INTO bookings (user_id, layanan, tanggal, waktu, alamat, harga, status)
    VALUES (?, ?, ?, ?, ?, ?, 'Menunggu')
");

$stmt->execute([
    $user_id,
    $b['layanan'],
    $b['tanggal'],
    $b['waktu'],
    $b['alamat'],
    $b['harga']
]);

// bersihkan session
unset($_SESSION['booking_temp']);
unset($_SESSION['booking_final']);

header("Location: riwayat.php");
exit;