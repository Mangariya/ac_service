<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_login = $_SESSION['user'] ?? null;
$user_nama = $user_login['nama'] ?? '';
?>

<style>
    .navbar-custom {
        background: white;
        padding: 18px 50px;
        border-bottom: 2px solid #E5E7EB;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .logo {
        font-size: 24px;
        font-weight: 800;
        color: #111827 !important;
        text-decoration: none;
    }

    .menu-navbar {
        gap: 15px;
    }

    .menu-navbar .nav-link {
        color: #6B7280;
        font-size: 15px;
        font-weight: 600;
        padding: 10px 22px;
        border-radius: 12px;
        transition: 0.3s;
    }

    .menu-navbar .nav-link:hover,
    .menu-navbar .nav-link.active-menu {
        background: #2563EB;
        color: white !important;
        font-weight: 700;
    }

    .btn-login-user {
        background: #2563EB;
        color: white;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-login-user:hover {
        background: #1D4ED8;
        color: white;
    }

    .dropdown-user {
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        background: none;
        font-weight: 700;
        color: #1E293B;
    }

    .user-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #DBEAFE;
        color: #2563EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center logo" href="home.php">
            <i class="bi bi-snow2 me-3" style="font-size:45px; color:#2563EB;"></i>
            <span>AC SERVICE</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUser">
            <span class="navbar-toggler-icon"></span>
        </button>

        

        <div class="collapse navbar-collapse" id="navbarUser">
            <ul class="navbar-nav mx-auto menu-navbar">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active-menu' : ''; ?>" href="home.php">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'layanan.php' ? 'active-menu' : ''; ?>" href="layanan.php">
                        Layanan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tentang-kami.php' ? 'active-menu' : ''; ?>" href="tentang-kami.php">
                        Tentang Kami
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'active-menu' : ''; ?>" href="faq.php">
                        Bantuan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'riwayat.php' ? 'active-menu' : ''; ?>" href="riwayat.php">
                        Riwayat
                    </a>
                </li>
            </ul>

            <div class="ms-lg-3 mt-3 mt-lg-0">
                <?php if ($user_login): ?>
                    <div class="dropdown">
                        <button class="dropdown-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span><?= htmlspecialchars($user_nama ?: 'Pelanggan'); ?></span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li>
                                <a class="dropdown-item" href="riwayat.php">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Riwayat Booking
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item text-danger" href="../auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-login-user">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>