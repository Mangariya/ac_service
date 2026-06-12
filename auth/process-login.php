<?php

session_start();

include '../config/database.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$email_lower = strtolower($email);

if (empty($email) || empty($password)) {

    echo "
    <script>
    alert('Email dan Password wajib diisi!');
    window.location='login.php';
    </script>
    ";

    exit;
}

$query = $conn->prepare(
    "SELECT * FROM users WHERE email=?"
);

$query->execute([$email]);

$user = $query->fetch();

if (!$user) {

    echo "
    <script>
    alert('Email tidak ditemukan!');
    window.location='login.php';
    </script>
    ";

    exit;
}

if (!password_verify($password, $user['password'])) {

    echo "
    <script>
    alert('Password salah!');
    window.location='login.php';
    </script>
    ";

    exit;
}

if (str_ends_with($email_lower, '@admin.com')) {
    $role = 'admin';
} elseif (str_ends_with($email_lower, '@teknisi.com')) {
    $role = 'teknisi';
} else {
    $role = $user['role'] ?? 'user';
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'nama' => $user['nama'],
    'email' => $user['email'],
    'telepon' => $user['telepon'] ?? '',
    'role' => $role
];

if ($role === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}

if ($role === 'teknisi') {
    header('Location: ../admin/manajemen_layanan.php');
    exit;
}

header('Location: ../user/home.php');
exit;

?>