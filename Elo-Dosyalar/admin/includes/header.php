<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece adminlerin erişimine izin ver
if (!isAdmin()) {
    redirect('../login.php');
}

// Aktif menü elemanını belirle
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Material Design Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --primary-color: #6366f1;
        --secondary-color: #4f46e5;
        --dark-bg: #0f172a;
        --darker-bg: #0a0f1d;
        --card-bg: #1e293b;
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
        --border-color: rgba(148, 163, 184, 0.1);
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: var(--dark-bg);
        color: var(--text-primary);
        min-height: 100vh;
    }

    /* Navbar Styles */
    .navbar {
        background: var(--darker-bg) !important;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .nav-link {
        color: var(--text-secondary) !important;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        margin: 0 0.25rem;
        font-weight: 500;
    }

    .nav-link:hover {
        color: var(--text-primary) !important;
        background: rgba(99, 102, 241, 0.1);
    }

    .nav-link.active {
        color: var(--text-primary) !important;
        background: rgba(99, 102, 241, 0.15);
    }

    .nav-link i {
        margin-right: 0.5rem;
    }

    /* Card Styles */
    .card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .card-header {
        background: rgba(99, 102, 241, 0.1);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    /* Button Styles */
    .btn-primary {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    /* Table Styles */
    .table {
        color: var(--text-primary);
    }

    .table thead th {
        background: var(--darker-bg);
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 2px solid var(--border-color);
    }

    .table td {
        color: var(--text-secondary);
        border-color: var(--border-color);
        vertical-align: middle;
    }

    /* Form Controls */
    .form-control {
        background: var(--darker-bg);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .form-control:focus {
        background: var(--darker-bg);
        border-color: var(--primary-color);
        color: var(--text-primary);
        box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--darker-bg);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--secondary-color);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }

    /* Stats Card */
    .stats-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(79, 70, 229, 0.1));
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-bottom: 1rem;
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="mdi mdi-shield-crown me-2"></i>
                Admin Paneli
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="mdi mdi-menu text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="index.php">
                            <i class="mdi mdi-view-dashboard"></i> Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'users' ? 'active' : ''; ?>" href="users.php">
                            <i class="mdi mdi-account-group"></i> Kullanıcılar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'orders' ? 'active' : ''; ?>" href="orders.php">
                            <i class="mdi mdi-cart"></i> Siparişler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'boosters' ? 'active' : ''; ?>" href="boosters.php">
                            <i class="mdi mdi-account-star"></i> Boosterlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'booster_payments' ? 'active' : ''; ?>" href="booster_payments.php">
                            <i class="mdi mdi-cash-multiple"></i> Booster Ödemeleri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'games' ? 'active' : ''; ?>" href="games.php">
                            <i class="mdi mdi-gamepad-variant"></i> Oyunlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'boost_prices' ? 'active' : ''; ?>" href="boost_prices.php">
                            <i class="mdi mdi-currency-usd"></i> Boost Fiyatları
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'payments' ? 'active' : ''; ?>" href="payments.php">
                            <i class="mdi mdi-cash-multiple"></i> Ödemeler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'settings' ? 'active' : ''; ?>" href="settings.php">
                            <i class="mdi mdi-cog"></i> Ayarlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'support' ? 'active' : ''; ?>" href="support.php">
                            <i class="mdi mdi-headset"></i> Destek Sistemi
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" data-bs-toggle="dropdown">
                            <i class="mdi mdi-account-circle"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="mdi mdi-account me-2"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="mdi mdi-logout me-2"></i> Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4"> 