<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register - AC Service</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{

    min-height:100vh;

    background:
    linear-gradient(
        rgba(255,255,255,0.7),
        rgba(255,255,255,0.7)
    ),
    url('http://localhost/ac_service/assets/images/bg-login.jpg');

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    display:flex;
    justify-content:center;
    align-items:center;

    padding:20px;
}

.register-container{

    width:1000px;
    background:white;

    border-radius:30px;

    display:flex;

    box-shadow:0 10px 40px rgba(0,0,0,0.15);

    overflow:hidden;
}

.left-side{

    width:50%;

    background:linear-gradient(
        135deg,
        #2563EB,
        #60A5FA
    );

    color:white;

    padding:60px;

    display:flex;
    flex-direction:column;
    justify-content:center;
}

.left-side h1{
    font-size:42px;
    font-weight:700;
    margin-bottom:20px;
}

.left-side p{
    line-height:1.8;
    opacity:0.9;
}

.right-side{

    width:50%;

    padding:50px;

    display:flex;
    flex-direction:column;
    justify-content:center;
}

.logo{
    font-size:32px;
    font-weight:700;
    color:#2563EB;
    margin-bottom:10px;
}

.register-title{
    font-size:30px;
    font-weight:700;
}

.register-subtitle{
    color:#6B7280;
    margin-bottom:30px;
}

.form-control{
    height:55px;
    border-radius:14px;
}

.btn-register{

    height:55px;

    background:#2563EB;
    color:white;

    border:none;
    border-radius:14px;

    font-weight:600;
}

.btn-register:hover{
    background:#1D4ED8;
}

.login-link{
    margin-top:20px;
    text-align:center;
}

.login-link a{
    text-decoration:none;
    color:#2563EB;
    font-weight:600;
}

@media(max-width:768px){

    .register-container{
        flex-direction:column;
    }

    .left-side,
    .right-side{
        width:100%;
    }

}

</style>

</head>

<body>

<div class="register-container">

<div class="left-side">

<h1>AC Service</h1>

<p>
Daftarkan akun Anda dan nikmati kemudahan booking service AC secara online dengan teknisi terpercaya.
</p>

</div>

<div class="right-side">

<div class="logo">
<i class="bi bi-snow"></i> AC Service
</div>

<div class="register-title">
Create Account
</div>

<div class="register-subtitle">
Silahkan daftar untuk melanjutkan
</div>

<form action="process-register.php" method="POST">

<div class="mb-3">

<input
type="text"
name="nama"
class="form-control"
placeholder="Enter your full name"
required>

</div>

<div class="mb-3">

<input
type="email"
name="email"
class="form-control"
placeholder="Enter your email"
required>

</div>

<div class="mb-3">

<input
type="text"
name="telepon"
class="form-control"
placeholder="Enter the telephone number"
required>

</div>

<div class="mb-3">

<input
type="password"
name="password"
class="form-control"
placeholder="Enter your password"
required>

</div>

<div class="mb-4">

<input
type="password"
name="confirm_password"
class="form-control"
placeholder="Enter your password again"
required>

</div>

<button type="submit" class="btn btn-register w-100">
Register
</button>

</form>

<div class="login-link">

Sudah punya akun?
<a href="login.php">
Login Sekarang
</a>

</div>

</div>

</div>

<!-- SWEET ALERT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_GET['success'])) : ?>

<script>

Swal.fire({
    icon: 'success',
    title: 'Registrasi Berhasil!',
    text: 'Silahkan login untuk melanjutkan',
    timer: 2000,
    showConfirmButton: false
}).then(() => {
    window.location.href = 'login.php';
});

</script>

<?php endif; ?>

</body>

</html>