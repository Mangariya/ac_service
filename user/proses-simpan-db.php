<?php
session_start();
include '../config/database.php';

// Atur header agar mengirimkan format JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Cek Sesi User
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Sesi habis, silakan login kembali.']);
            exit;
        }

        $user_id = $_SESSION['user']['id'];
        $layanan = $_POST['layanan'];
        $tanggal = $_POST['tanggal'];
        $waktu   = $_POST['waktu'];
        $alamat  = $_POST['alamat'];

        // 2. LOGIKA PENGAMAN: Cek apakah jadwal sudah terisi di database
        // Kita mengecek booking yang statusnya BUKAN 'Dibatalkan'
        $stmt_cek = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE tanggal = ? AND waktu = ? AND status != 'Dibatalkan'");
        $stmt_cek->execute([$tanggal, $waktu]);
        $sudah_ada = $stmt_cek->fetchColumn();

        if ($sudah_ada > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Mohon maaf, jadwal ini baru saja diambil oleh pelanggan lain. Silakan pilih jadwal atau waktu lainnya.'
            ]);
            exit;
        }

        // 3. Penentuan harga otomatis agar sinkron
        $harga = 0;
        if ($layanan == 'Cuci AC') {
            $harga = 75000;
        } elseif ($layanan == 'Perbaikan AC') {
            $harga = 150000;
        } elseif ($layanan == 'Isi Freon') {
            $harga = 200000;
        } else {
            $harga = 50000; // Harga default
        }

        // 4. Proses Simpan ke Database
        $sql = "INSERT INTO bookings (user_id, layanan, tanggal, waktu, alamat, status, harga) 
                VALUES (?, ?, ?, ?, ?, 'Menunggu', ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $layanan, $tanggal, $waktu, $alamat, $harga]);

        // 5. Kirim Respon Sukses
        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        // Jika ada error database
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode akses tidak diizinkan.']);
    exit;
}