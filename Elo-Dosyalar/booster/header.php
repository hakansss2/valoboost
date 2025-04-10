<?php
// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Doğru yol tanımlamaları
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

// Booster kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'booster') {
    header('Location: ' . getBaseUrl() . 'login.php');
    exit;
}

// Okunmamış bildirimleri getir
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booster Panel - <?php echo getSetting('site_title') ?? 'Elo Boost'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --light-color: #f8f9fc;
        --dark-color: #5a5c69;
    }

    body {
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background-color: #f8f9fc;
    }

    .content {
        flex: 1 0 auto;
        margin-left: 250px;
        padding: 1.5rem;
        transition: all 0.3s;
    }

    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 48px 0 0;
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        background-color: #212529;
        transition: all 0.3s;
        width: 250px;
    }

    .sidebar.active {
        margin-left: -250px;
    }

    .content {
        margin-left: 250px;
        transition: all 0.3s;
    }
    
    .content.active {
        margin-left: 0;
    }
    
    .sidebar-heading {
        padding: 0.875rem 1.25rem;
        font-size: 1.2rem;
    }
    
    .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 1rem 1.25rem;
    }
    
    .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link.active {
        color: #fff;
        background: rgba(255, 255, 255, 0.2);
    }

    .nav-link i {
        width: 20px;
        margin-right: 0.5rem;
        text-align: center;
    }

    /* Topbar Styles */
    .topbar {
        height: 70px;
        background: white;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.15);
        display: flex;
        align-items: center;
        padding: 0 1.5rem;
        margin-bottom: 1.5rem;
    }

    .topbar-divider {
        width: 0;
        border-right: 1px solid #e3e6f0;
        height: 2rem;
        margin: auto 1rem;
    }

    .user-dropdown img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.15);
    }

    /* Card Styles */
    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.15);
        border-radius: 0.5rem;
    }

    .card-header {
        background: white;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.25rem;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            margin-left: -250px;
        }
        .sidebar.active {
            margin-left: 0;
        }
        .content {
            margin-left: 0;
        }
        .content.active {
            margin-left: 250px;
        }
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-3 text-white">
            <span>Booster Panel</span>
            <button id="sidebarToggle" class="btn btn-link text-white p-0 d-md-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="nav flex-column">
            <a href="<?php echo getBaseUrl(); ?>booster" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home me-2"></i> Ana Sayfa
            </a>
            <a href="<?php echo getBaseUrl(); ?>booster/orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks me-2"></i> Aktif Siparişler
            </a>
            <a href="<?php echo getBaseUrl(); ?>booster/completed_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'completed_orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle me-2"></i> Tamamlanan Siparişler
            </a>
            <a href="<?php echo getBaseUrl(); ?>booster/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user me-2"></i> Profil
            </a>
            <a href="<?php echo getBaseUrl(); ?>logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
            </a>
        </div>
    </nav>

    <!-- Content -->
    <div class="content">
        <!-- Topbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <button class="btn btn-link text-white me-3 d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand"><?php echo getSetting('site_title') ?? 'Elo Boost'; ?></span>
                <div class="ms-auto d-flex align-items-center">
                    <a href="notifications.php" class="btn btn-link text-white position-relative me-3">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unread_notifications; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="text-white">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo $_SESSION['username']; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Alerts -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 