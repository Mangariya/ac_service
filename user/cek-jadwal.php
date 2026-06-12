<?php
include '../config/database.php';

$tanggal = $_GET['tanggal'] ?? '';
$waktu   = $_GET['waktu'] ?? '';

$response = ['tersedia' => true];

if ($tanggal && $waktu) {
    // Menghitung booking aktif (bukan yang dibatalkan) di jam yang sama
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE tanggal = ? AND waktu = ? AND status != 'Dibatalkan'");
    $stmt->execute([$tanggal, $waktu]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $response['tersedia'] = false;
    }
}

header('Content-Type: application/json');
echo json_encode($response);