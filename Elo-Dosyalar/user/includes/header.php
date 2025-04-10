<?php
// Oturum kontrolü zaten yapıldı, bu dosya sadece header'ı içerecek
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_title'); ?> - Kullanıcı Paneli</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Material Design Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- TechUI Theme CSS -->
    <link href="../theme/assets/app-0f19a312.css" rel="stylesheet" type="text/css" />
    
    <!-- Google Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,300,400&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <style>
        /* Özel stil düzenlemeleri */
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --neon-blue: #00f3ff;
            --neon-purple: #9d4edd;
            --dark-bg: #0a0b1e;
            --dark-card: #1a1b3a;
        }

        body {
            background-color: var(--dark-bg);
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar-user {
            background: rgba(26, 27, 58, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
        }

        .user-logo {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .user-logo:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        }

        .user-logo-text {
            background: linear-gradient(to right, var(--neon-blue), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .nav-link-user {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link-user:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--neon-blue) !important;
            transform: translateY(-2px);
        }

        .nav-link-user.active {
            background: rgba(106, 17, 203, 0.2);
            color: var(--neon-blue) !important;
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
            font-weight: 600;
        }

        /* Balance Container */
        .balance-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .balance-container:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        }

        .balance-amount {
            color: var(--neon-blue);
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-user {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-user:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
            color: var(--neon-blue);
        }

        /* User Menu */
        .user-menu {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        }

        .user-avatar {
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        }

        /* Notification Badge */
        .notification-badge {
            background: var(--neon-purple);
            box-shadow: 0 0 10px rgba(157, 78, 221, 0.6);
        }

        /* Dropdown Menu */
        .dropdown-menu-user {
            background: rgba(26, 27, 58, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(106, 17, 203, 0.4);
        }

        .dropdown-item-user {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .dropdown-item-user:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--neon-blue);
            transform: translateX(5px);
        }

        .dropdown-item-user.active {
            background: rgba(106, 17, 203, 0.2);
            color: var(--neon-blue);
        }

        .dropdown-header-user {
            color: var(--neon-blue);
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
        }

        .dropdown-divider-user {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Notification Items */
        .notification-item {
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .notification-icon {
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
        }

        .notification-text {
            color: rgba(255, 255, 255, 0.8);
        }

        .notification-time {
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>