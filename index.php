<?php

include 'config/database.php';

// Redirect ke halaman home dashboard (guest bisa akses tanpa login)
header('Location: user/home.php');
exit;

?>