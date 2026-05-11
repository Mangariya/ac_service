<?php
session_start();

if(!isset($_SESSION['booking_temp'])){
    header("Location: booking.php");
    exit;
}

$data = $_SESSION['booking_temp'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ringkasan Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">

<div class="card p-4 shadow">

<h3>Ringkasan Booking</h3>
<hr>

<p><b>Layanan:</b> <?= $data['layanan'] ?></p>
<p><b>Tanggal:</b> <?= $data['tanggal'] ?></p>
<p><b>Waktu:</b> <?= $data['waktu'] ?></p>
<p><b>Alamat:</b> <?= $data['alamat'] ?></p>
<p><b>Harga:</b> <?= $data['harga'] ?></p>

<br>

<form action="success.php" method="POST">
    <button class="btn btn-primary">Konfirmasi Booking</button>
</form>

</div>

</body>
</html>