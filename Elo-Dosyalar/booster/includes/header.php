<?php
// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum kontrolü - config.php'de başlatıldığı için burada başlatmıyoruz
// require_once ile dosyaları dahil et
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

// Booster kontrolü - Sadece login.php sayfasında değilsek kontrol et
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    } elseif ($_SESSION['user_role'] !== 'booster') {
        // Eğer kullanıcı booster değilse, rolüne göre yönlendir
        if ($_SESSION['user_role'] === 'user') {
            header('Location: /user/');
        } elseif ($_SESSION['user_role'] === 'admin') {
            header('Location: /admin/');
        } else {
            header('Location: /login.php');
        }
        exit;
    }
}

// Okunmamış bildirimleri getir
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE user_id = ? AND status = 'unread'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_notifications = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Bildirim hatası: " . $e->getMessage());
    $unread_notifications = 0;
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Material Design Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Google Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,300,400&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
    /* Tema Değişkenleri */
    :root {
        --primary-color: #6a11cb;
        --secondary-color: #2575fc;
        --neon-blue: #00f3ff;
        --neon-purple: #9d4edd;
        --dark-bg: #0a0b1e;
        --dark-card: #1a1b3a;
    }

    /* Genel Stiller */
    body {
        font-family: 'Satoshi', sans-serif;
        background-color: var(--dark-bg);
        min-height: 100vh;
        color: #fff;
        display: flex;
    }

    /* Sidebar Stiller */
    .sidebar {
        background: rgba(26, 27, 58, 0.95) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: all 0.3s ease;
        padding-top: 1rem;
    }

    .sidebar-heading {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 15px;
        margin: 0 15px 15px 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.8) !important;
        padding: 12px 20px;
        margin: 5px 15px;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-weight: 500;
        font-size: 0.95rem;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--neon-blue) !important;
        transform: translateX(5px);
    }

    .nav-link.active {
        background: rgba(106, 17, 203, 0.2);
        color: var(--neon-blue) !important;
        box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        font-weight: 600;
    }

    /* Navbar Stiller */
    .navbar {
        background: rgba(26, 27, 58, 0.95) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.5rem 1rem;
    }

    .navbar-brand {
        background: linear-gradient(to right, var(--neon-blue), var(--neon-purple));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
        font-size: 1.5rem;
    }

    /* Kartlar */
    .card {
        background: rgba(26, 27, 58, 0.95) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    /* Metin Renkleri */
    .text-muted {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    /* Content */
    .content {
        flex: 1;
        margin-left: 250px;
        min-height: 100vh;
        background-color: var(--dark-bg);
        transition: all 0.3s ease;
        position: relative;
        padding-bottom: 80px; /* Footer için alan */
    }

    /* Footer */
    .footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        background: rgba(26, 27, 58, 0.95) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1rem;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Dropdown */
    .dropdown-menu {
        background: rgba(26, 27, 58, 0.95) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.5rem;
    }

    .dropdown-item {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.5rem 1rem;
        border-radius: 8px;
    }

    .dropdown-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--neon-blue);
    }

    .dropdown-divider {
        border-color: rgba(255, 255, 255, 0.1);
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
        <div class="sidebar-heading d-flex justify-content-between align-items-center">
            <span class="text-glow">Booster Panel</span>
            <button id="sidebarToggle" class="btn btn-link text-white p-0 d-md-none">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        <div class="nav flex-column">
            <a href="/booster/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="mdi mdi-view-dashboard"></i> Ana Sayfa
            </a>
            <a href="/booster/orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="mdi mdi-format-list-bulleted"></i> Aktif Siparişler
            </a>
            <a href="/booster/completed_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'completed_orders.php' ? 'active' : ''; ?>">
                <i class="mdi mdi-check-circle"></i> Tamamlanan Siparişler
            </a>
            <a href="/booster/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="mdi mdi-account"></i> Profil
            </a>
            <a href="/booster/support.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>">
                <i class="mdi mdi-help-circle"></i> Destek
            </a>
            <a href="/logout.php" class="nav-link">
                <i class="mdi mdi-logout"></i> Çıkış Yap
            </a>
        </div>
    </nav>

    <!-- Content -->
    <div class="content">
        <!-- Alerts -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-3">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="container-fluid py-4">
            <!-- Begin Page Content -->
        </div>
    </div>
</body>
</html> 