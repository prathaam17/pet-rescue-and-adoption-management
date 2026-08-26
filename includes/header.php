<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Assume config is included in key files, or include here if path allows. 
// Ideally pages include config first.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Rescue & Care System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .nav-link.active {
            color: var(--primary-color) !important;
            text-shadow: 0 0 10px rgba(0, 184, 148, 0.2);
        }
        .nav-link.active::after {
            width: 100% !important;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent) !important;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
                <i class="bi bi-heart-pulse-fill text-danger"></i>
                <span>Pet Rescue</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="bi bi-list fs-1 text-primary"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'index.php' || $current_page == '') ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'about.php') ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'adoption.php') ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>adoption.php">Adopt</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'vets.php') ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>vets.php">Vets & Salons</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'ngos.php') ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>ngos.php">NGOs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-light text-dark px-3" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php">Admin Dashboard</a>
                                    </li>
                                <?php elseif ($_SESSION['user_role'] == 'ngo'): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>ngo/dashboard.php">NGO Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>user/dashboard.php">My Profile</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>user/emergency.php">Request Rescue</a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-outline-primary rounded-pill ms-2"
                                href="<?= BASE_URL ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>