<?php
// Okunmamış bildirimleri getir
$unread_notifications = [];
$notification_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND status = 'unread' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notification_count = count($unread_notifications);
} catch(PDOException $e) {
    // Hata durumunda sessizce devam et
}

// Kullanıcı bakiyesini getir
try {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_balance = $stmt->fetchColumn();
} catch(PDOException $e) {
    $user_balance = 0;
}

// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-color: #4e73df;
    --secondary-color: #6f8dff;
    --success-color: #1cc88a;
    --info-color: #36b9cc;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --light-color: #f8f9fc;
    --dark-color: #5a5c69;
    --purple-color: #8c54ff;
    --pink-color: #ff5e94;
    --cyan-color: #0acffe;
    --blue-color: #495aff;
    --orange-color: #ff9f43;
    --green-color: #28c76f;
}

body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Navbar Styles */
.navbar-user {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.user-logo {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 8px 16px;
    transform: skew(-10deg);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.user-logo:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: skew(-10deg) translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

.user-logo-text {
    transform: skew(10deg);
    display: inline-block;
    font-weight: 700;
    font-size: 1.25rem;
    background: linear-gradient(to right, #fff, rgba(255, 255, 255, 0.8));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

.user-logo-icon {
    margin-right: 8px;
    font-size: 1.1rem;
    vertical-align: middle;
}

.nav-item {
    margin: 0 5px;
}

.nav-link-user {
    color: rgba(255, 255, 255, 0.9) !important;
    padding: 0.6rem 1rem;
    font-weight: 500;
    transition: all 0.3s;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
    z-index: 1;
    font-size: 0.95rem;
}

.nav-link-user::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s ease;
    z-index: -1;
}

.nav-link-user:hover {
    color: #fff !important;
    transform: translateY(-2px);
}

.nav-link-user:hover::before {
    transform: scaleX(1);
    transform-origin: left;
}

.nav-link-user.active {
    color: #fff !important;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.nav-link-user .nav-icon {
    margin-right: 8px;
    font-size: 1rem;
    vertical-align: middle;
}

/* Action Buttons */
.btn-user {
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    z-index: 1;
    color: white;
}

.btn-user::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: -1;
}

.btn-user:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    color: white;
}

.btn-user:hover::before {
    transform: translateX(0);
}

.btn-order {
    background: linear-gradient(135deg, var(--orange-color), #ffbd59);
    box-shadow: 0 4px 10px rgba(255, 159, 67, 0.3);
}

.btn-deposit {
    background: linear-gradient(135deg, var(--green-color), #48da89);
    box-shadow: 0 4px 10px rgba(40, 199, 111, 0.3);
}

/* Balance Badge */
.balance-container {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 5px 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.balance-container:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.balance-icon {
    color: rgba(255, 255, 255, 0.9);
    margin-right: 8px;
    font-size: 1rem;
}

.balance-text {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    margin-right: 5px;
}

.balance-amount {
    background: rgba(255, 255, 255, 0.9);
    color: var(--primary-color);
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.9rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Notification Badge */
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.65rem;
    padding: 0.25rem 0.45rem;
    border-radius: 50%;
    background: var(--danger-color);
    color: white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* User Menu */
.user-menu {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 5px 15px 5px 5px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.user-menu:hover {
    background: rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--cyan-color), var(--pink-color));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: 600;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-name {
    color: white;
    font-weight: 500;
    font-size: 0.9rem;
    margin-right: 5px;
}

/* Dropdown Menu */
.dropdown-menu-user {
    border: none;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
    border-radius: 15px;
    padding: 0.75rem 0;
    margin-top: 10px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: white;
    min-width: 220px;
}

.dropdown-item-user {
    padding: 0.7rem 1.5rem;
    font-size: 0.9rem;
    transition: all 0.2s;
    color: var(--dark-color);
    position: relative;
    display: flex;
    align-items: center;
}

.dropdown-item-user:hover {
    background-color: rgba(78, 115, 223, 0.1);
    color: var(--primary-color);
    padding-left: 1.75rem;
}

.dropdown-item-user.active {
    background-color: rgba(78, 115, 223, 0.15);
    color: var(--primary-color);
    font-weight: 500;
}

.dropdown-item-user i {
    margin-right: 10px;
    font-size: 0.9rem;
    opacity: 0.7;
    width: 20px;
    text-align: center;
}

.dropdown-header-user {
    font-weight: 600;
    color: var(--dark-color);
    padding: 0.5rem 1.5rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.dropdown-divider-user {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Notification Items */
.notification-item {
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    transition: all 0.2s;
}

.notification-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.notification-icon.primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.notification-icon.success {
    background: linear-gradient(135deg, var(--success-color), #4ae3c0);
}

.notification-icon.warning {
    background: linear-gradient(135deg, var(--warning-color), #ffa26b);
}

.notification-content {
    flex: 1;
}

.notification-time {
    font-size: 0.75rem;
    color: #888;
    margin-bottom: 3px;
}

.notification-text {
    font-size: 0.85rem;
    color: var(--dark-color);
    font-weight: 500;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .nav-link-user {
        margin: 0.25rem 0;
        border-radius: 5px;
    }
    
    .nav-link-user.active {
        border: none;
        border-left: 4px solid #fff;
        border-radius: 0;
    }
    
    .user-menu, .balance-container {
        margin-top: 10px;
        justify-content: center;
    }
    
    .btn-user {
        width: 100%;
        margin: 5px 0;
    }
}
</style>

<header class="navbar navbar-expand-lg navbar-dark navbar-user sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand user-logo" href="index.php">
            <span class="user-logo-text">
                <i class="fas fa-crown user-logo-icon"></i>KULLANICI PANELİ
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link nav-link-user <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home nav-icon"></i>Ana Sayfa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-user <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                        <i class="fas fa-shopping-cart nav-icon"></i>Siparişlerim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-user <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                        <i class="fas fa-credit-card nav-icon"></i>Ödemelerim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-user <?php echo in_array($current_page, ['support.php', 'view_ticket.php', 'new_ticket.php']) ? 'active' : ''; ?>" href="support.php">
                        <i class="fas fa-headset nav-icon"></i>Destek
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav align-items-center">
                <!-- Yeni Sipariş Butonu -->
                <li class="nav-item mx-2">
                    <a class="btn btn-user btn-order" href="new_order.php">
                        <i class="fas fa-plus me-2"></i>Yeni Sipariş
                    </a>
                </li>
                
                <!-- Bakiye Yükleme Butonu -->
                <li class="nav-item mx-2">
                    <a class="btn btn-user btn-deposit" href="deposit.php">
                        <i class="fas fa-plus-circle me-2"></i>Bakiye Yükle
                    </a>
                </li>
                
                <!-- Bakiye Gösterimi -->
                <li class="nav-item mx-2">
                    <a class="nav-link balance-container" href="deposit.php">
                        <i class="fas fa-wallet balance-icon"></i>
                        <span class="balance-text d-none d-md-inline">Bakiye:</span>
                        <span class="balance-amount"><?php echo number_format($user_balance, 2, ',', '.'); ?> ₺</span>
                    </a>
                </li>
                
                <!-- Bildirimler -->
                <li class="nav-item dropdown mx-2">
                    <a class="nav-link nav-link-user position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell nav-icon"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-user dropdown-menu-end" style="width: 320px;">
                        <h6 class="dropdown-header-user">BİLDİRİMLER</h6>
                        <?php if (empty($unread_notifications)): ?>
                            <div class="notification-item">
                                <div class="notification-icon primary">
                                    <i class="fas fa-bell-slash text-white"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-text">Yeni bildiriminiz yok</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($unread_notifications as $notification): ?>
                                <a class="notification-item" href="notifications.php">
                                    <div class="notification-icon primary">
                                        <i class="fas fa-bell text-white"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-time">
                                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        </div>
                                        <div class="notification-text">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="dropdown-divider-user"></div>
                        <a class="dropdown-item-user text-center" href="notifications.php">
                            <i class="fas fa-arrow-right"></i>Tüm Bildirimleri Göster
                        </a>
                    </div>
                </li>
                
                <!-- Kullanıcı Menüsü -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-menu" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-user dropdown-menu-end">
                        <a class="dropdown-item-user <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fas fa-user fa-fw me-2"></i>Profilim
                        </a>
                        <a class="dropdown-item-user <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <i class="fas fa-shopping-cart fa-fw me-2"></i>Siparişlerim
                        </a>
                        <a class="dropdown-item-user <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                            <i class="fas fa-credit-card fa-fw me-2"></i>Ödemelerim
                        </a>
                        <div class="dropdown-divider-user"></div>
                        <a class="dropdown-item-user text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt fa-fw me-2"></i>Çıkış Yap
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>