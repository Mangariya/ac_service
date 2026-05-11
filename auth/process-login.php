<?php

session_start();

include '../config/database.php';

$email = $_POST['email'];
$password = $_POST['password'];

if(empty($email) || empty($password)){

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

if(!$user){

    echo "
    <script>
    alert('Email tidak ditemukan!');
    window.location='login.php';
    </script>
    ";

    exit;
}

if(!password_verify($password, $user['password'])){

    echo "
    <script>
    alert('Password salah!');
    window.location='login.php';
    </script>
    ";

    exit;
}

/* SESSION USER */

$_SESSION['user'] = [
    'id' => $user['id'],
    'nama' => $user['nama'],
    'email' => $user['email'],
    'telepon' => $user['telepon']
];

/* REDIRECT */

header('Location: ../user/home.php');
exit;

?>