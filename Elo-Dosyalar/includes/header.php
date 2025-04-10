<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Elo Boost Hizmetleri'; ?></title>
    
    <!-- Favicon -->
    <?php if (getSetting('site_favicon')): ?>
    <link rel="icon" href="<?php echo getBaseUrl() . getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo getBaseUrl() . getSetting('site_favicon'); ?>" type="image/x-icon">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="/assets/css/gameco-theme.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
</head>
<body>
    <!-- Header Area -->
    <header class="header-area">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-6">
                    <div class="logo">
                        <a href="/">
                            <img src="/assets/img/logo.png" alt="Logo" class="img-fluid">
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <nav class="main-menu">
                        <ul>
                            <li><a href="/">Ana Sayfa</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3 col-6 text-end">
                    <?php if (isLoggedIn()): ?>
                        <div class="user-menu">
                            <?php
                            $panel_url = 'user/';  // varsayılan panel
                            $user_type = getUserType();
                            
                            if ($user_type === 'admin') {
                                $panel_url = 'admin/';
                            } elseif ($user_type === 'booster') {
                                $panel_url = 'booster/';
                            }
                            ?>
                            <a href="<?php echo $panel_url; ?>" class="btn btn-primary me-2">Hesabım</a>
                            <a href="logout.php" class="btn btn-outline-light">Çıkış</a>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn btn-primary me-2">Giriş</a>
                            <a href="register.php" class="btn btn-outline-light">Kayıt Ol</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mobile Menu Button -->
                    <button class="mobile-menu-btn d-lg-none">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu d-lg-none">
        <div class="container">
            <ul>
                <li><a href="/">Ana Sayfa</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php
                    $panel_url = 'user/';  // varsayılan panel
                    $user_type = getUserType();
                    
                    if ($user_type === 'admin') {
                        $panel_url = 'admin/';
                    } elseif ($user_type === 'booster') {
                        $panel_url = 'booster/';
                    }
                    ?>
                    <li><a href="<?php echo $panel_url; ?>">Hesabım</a></li>
                    <li><a href="logout.php">Çıkış</a></li>
                <?php else: ?>
                    <li><a href="login.php">Giriş</a></li>
                    <li><a href="register.php">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
</body>
</html> 