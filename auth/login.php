<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - AC Service</title>

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

.login-container{

    width:950px;
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

    padding:60px;

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

.login-title{
    font-size:30px;
    font-weight:700;
}

.login-subtitle{
    color:#6B7280;
    margin-bottom:35px;
}

.form-control{
    height:55px;
    border-radius:14px;
}

.btn-login{

    height:55px;

    background:#2563EB;
    color:white;

    border:none;
    border-radius:14px;

    font-weight:600;
}

.btn-login:hover{
    background:#1D4ED8;
}

.register-link{
    margin-top:20px;
    text-align:center;
}

.register-link a{
    text-decoration:none;
    color:#2563EB;
    font-weight:600;
}

@media(max-width:768px){

    .login-container{
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

<div class="login-container">

<div class="left-side">

<h1>AC Service</h1>

<p>
Booking service AC kini lebih mudah, cepat, dan terpercaya untuk rumah maupun kantor Anda.
</p>

</div>

<div class="right-side">

<div class="logo">
<i class="bi bi-snow"></i> AC Service
</div>

<div class="login-title">
Welcome Back
</div>

<div class="login-subtitle">
Silahkan login untuk melanjutkan
</div>

<form action="process-login.php" method="POST">

<div class="mb-3">

<input
type="email"
name="email"
class="form-control"
placeholder="Masukkan Email"
required>

</div>

<div class="mb-4">

<input
type="password"
name="password"
class="form-control"
placeholder="Masukkan Password"
required>

</div>

<button type="submit" class="btn btn-login w-100">
Login
</button>

</form>

<div class="register-link">

Belum punya akun?
<a href="register.php">
Daftar Sekarang
</a>

</div>

</div>

</div>

</body>

</html>