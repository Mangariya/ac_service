<?php
session_start();

if(!isset($_SESSION['booking_temp'])){
    header("Location: booking.php");
    exit;
}

$booking = $_SESSION['booking_temp'];
$_SESSION['booking_final'] = $booking;

// pindahkan ke proses simpan DB
header("Location: process_booking.php");
exit;