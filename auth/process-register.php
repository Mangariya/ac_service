<?php

session_start();

include '../config/database.php';

$nama = $_POST['nama'];
$email = $_POST['email'];
$telepon = $_POST['telepon'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if(
    empty($nama) ||
    empty($email) ||
    empty($telepon) ||
    empty($password) ||
    empty($confirm_password)
){
    echo "
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>

    <script>

    Swal.fire({
        icon: 'warning',
        title: 'Oops...',
        text: 'Semua form wajib diisi!'
    }).then(() => {
        window.location='register.php';
    });

    </script>

    </body>
    </html>
    ";
    exit;
}

if($password != $confirm_password){

    echo "
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>

    <script>

    Swal.fire({
        icon: 'error',
        title: 'Password Tidak Sama!',
        text: 'Silahkan cek kembali password Anda'
    }).then(() => {
        window.location='register.php';
    });

    </script>

    </body>
    </html>
    ";

    exit;
}

$cek = $conn->prepare(
"SELECT * FROM users WHERE email=?"
);

$cek->execute([$email]);

if($cek->rowCount() > 0){

    echo "
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>

    <script>

    Swal.fire({
        icon: 'error',
        title: 'Email Sudah Digunakan!',
        text: 'Gunakan email lain untuk register'
    }).then(() => {
        window.location='register.php';
    });

    </script>

    </body>
    </html>
    ";

    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = (substr(strtolower(trim($email)), -10) === '@admin.com') ? 'admin' : 'user';

$query = $conn->prepare(
"INSERT INTO users
(nama,email,telepon,password,role,status_acc)
VALUES (?,?,?,?,?,?)"
);

$query->execute([
    $nama,
    $email,
    $telepon,
    $password_hash,
    $role,
    'approved'
]);

echo "
<html>
<head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>

<script>

Swal.fire({
    icon: 'success',
    title: 'Registrasi Berhasil!',
    text: 'Mengalihkan ke halaman login...',
    timer: 2000,
    showConfirmButton: false
}).then(() => {
    window.location='login.php';
});

</script>

</body>
</html>
";

?>