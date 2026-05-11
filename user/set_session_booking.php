<?php
session_start();

// ambil raw input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// DEBUG (hapus nanti kalau sudah jalan)
// var_dump($raw, $data);

if (
    isset($data['layanan']) &&
    isset($data['tanggal']) &&
    isset($data['waktu']) &&
    isset($data['alamat']) &&
    isset($data['harga'])
) {
    $_SESSION['booking'] = [
        'layanan' => $data['layanan'],
        'tanggal' => $data['tanggal'],
        'waktu'   => $data['waktu'],
        'alamat'  => $data['alamat'],
        'harga'   => $data['harga']
    ];

    echo json_encode(["success" => true]);
} else {
    echo json_encode([
        "success" => false,
        "debug_raw" => $raw
    ]);
}
?>