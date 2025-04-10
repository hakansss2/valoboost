<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Kullanıcı bilgilerini getir
$user_id = $_SESSION['user_id'];

try {
    // Kullanıcı bilgilerini getir
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Toplam sipariş sayısı
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_orders = $stmt->fetchColumn();

    // Aktif sipariş sayısı
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$user_id]);
    $active_orders = $stmt->fetchColumn();

    // Tamamlanan sipariş sayısı
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $completed_orders = $stmt->fetchColumn();

    // Toplam harcama
    $stmt = $conn->prepare("SELECT COALESCE(SUM(price), 0) FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$user_id]);
    $total_spent = $stmt->fetchColumn();

    // Son siparişler
    $stmt = $conn->prepare("
        SELECT o.*, g.name as game_name, g.image as game_image,
        r1.name as current_rank, r1.image as current_rank_image,
        r2.name as target_rank, r2.image as target_rank_image,
        u.username as booster_name
        FROM orders o
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN ranks r1 ON o.current_rank_id = r1.id
        LEFT JOIN ranks r2 ON o.target_rank_id = r2.id
        LEFT JOIN users u ON o.booster_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aylık harcama grafiği için verileri getir
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(price) as total
        FROM orders 
        WHERE user_id = ? AND status != 'cancelled'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_spending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grafik verileri
    $months = [];
    $spending_data = [];

    // Son 6 ayı doldur
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i month"));
        $months[] = date('M Y', strtotime("-$i month"));
        $spending_data[$month] = 0;
    }

    // Veritabanından gelen verileri ekle
    foreach ($monthly_spending as $item) {
        if (isset($spending_data[$item['month']])) {
            $spending_data[$item['month']] = (float)$item['total'];
        }
    }

    // Oyun dağılımı
    $stmt = $conn->prepare("
        SELECT g.name, COUNT(*) as count
        FROM orders o
        JOIN games g ON o.game_id = g.id
        WHERE o.user_id = ?
        GROUP BY g.name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $game_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Veriler yüklenirken bir hata oluştu.";
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Kontrol Paneli</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active text-muted">Kontrol Paneli</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Kart -->
    <div class="row">
        <div class="col-xxl-6">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex flex-column h-100">
                                <div class="flex-grow-1">
                                    <h3 class="fw-medium text-capitalize mt-0 mb-2 text-glow">Hesap Durumunuzu Kontrol Edin</h3>
                                    <p class="font-18 text-muted">Hesap durumunuz ve aktiviteleriniz.</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="row h-100">
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect glow-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Bakiye</h4>
                                                        <a class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center" href="deposit.php">
                                                            <i class="mdi mdi-arrow-top-right"></i>
                                                        </a>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo number_format($user['balance'], 2, ',', '.'); ?> ₺</h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect glow-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Harcama</h4>
                                                        <a class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center" href="payments.php">
                                                            <i class="mdi mdi-arrow-top-right"></i>
                                                        </a>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo number_format($total_spent, 2, ',', '.'); ?> ₺</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-center h-100 w-100 mt-4 mt-md-0">
                                <img alt="Elo Boost" class="img-fluid floating-image" src="../theme/assets/hero-dashboard.png" style="max-height: 280px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Bakiye Yükle</h4>
                                    <p class="mb-2 text-muted">Hızlı ve güvenli ödeme seçenekleri</p>
                                    <a href="deposit.php" class="btn btn-glow btn-primary btn-sm">Bakiye Yükle</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="character-wrapper">
                                        <img src="../assets/img/characters/character1.png" alt="Karakter" class="img-fluid floating-image" style="max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Sipariş Ver</h4>
                                    <p class="mb-2 text-muted">Hemen yeni bir boost siparişi verin</p>
                                    <a href="new_order.php" class="btn btn-glow btn-primary btn-sm">Sipariş Ver</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="character-wrapper">
                                        <img src="../assets/img/characters/character2.png" alt="Karakter" class="img-fluid floating-image" style="max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Siparişlerim</h4>
                                    <p class="mb-2 text-muted">Tüm siparişlerinizi görüntüleyin</p>
                                    <a href="orders.php" class="btn btn-glow btn-primary btn-sm">Siparişleri Gör</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="character-wrapper">
                                        <img src="../assets/img/characters/character3.png" alt="Karakter" class="img-fluid floating-image" style="max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Destek</h4>
                                    <p class="mb-2 text-muted">Yardıma mı ihtiyacınız var?</p>
                                    <a href="support.php" class="btn btn-glow btn-primary btn-sm">Destek Al</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="character-wrapper">
                                        <img src="../assets/img/characters/character4.png" alt="Karakter" class="img-fluid floating-image" style="max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistikler -->
    <div class="row mt-4">
        <!-- Son Siparişleriniz -->
        <div class="col-xxl-8">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-white mb-0">Son Siparişleriniz</h5>
                        <a href="orders.php" class="btn btn-glow btn-sm btn-primary">Tümünü Gör</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-0">Henüz sipariş bulunmuyor.</p>
                            <a href="new_order.php" class="btn btn-glow btn-primary mt-3">
                                <i class="fas fa-plus"></i> Yeni Sipariş Ver
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-dark-gradient">
                                    <tr>
                                        <th class="text-white border-0">Oyun</th>
                                        <th class="text-white border-0">Durum</th>
                                        <th class="text-white border-0">İlerleme</th>
                                        <th class="text-white border-0">Fiyat</th>
                                        <th class="text-white border-0">Tarih</th>
                                        <th class="text-white border-0">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr class="glass-effect-light">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($order['game_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($order['game_image']); ?>" 
                                                             alt="" class="me-2" style="width: 32px;">
                                                    <?php endif; ?>
                                                    <span class="text-white"><?php echo htmlspecialchars($order['game_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?> bg-opacity-25 text-white glow-badge">
                                                    <?php echo getOrderStatusText($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress glow-effect" style="height: 6px; width: 100px;">
                                                    <div class="progress-bar neon-gradient" role="progressbar" 
                                                         style="width: <?php echo $order['progress']; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-glow"><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-glow btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Canlı İstatistikler -->
        <div class="col-xxl-4">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="card-title text-white mb-0">Canlı İstatistikler</h5>
                </div>
                <div class="card-body">
                    <div class="live-stats">
                        <!-- Aktif Boosterlar -->
                        <div class="stat-item glass-effect mb-4 p-3" style="border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <div class="avatar-sm bg-glow rounded-circle">
                                        <i class="fas fa-user-shield text-white"></i>
                                    </div>
                                </div>
                                <div class="stat-content flex-grow-1">
                                    <h4 class="text-white mb-1">Aktif Boosterlar</h4>
                                    <div class="d-flex align-items-baseline">
                                        <h2 class="text-glow mb-0 me-2">24</h2>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> +3
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Devam Eden Siparişler -->
                        <div class="stat-item glass-effect mb-4 p-3" style="border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <div class="avatar-sm bg-glow rounded-circle">
                                        <i class="fas fa-sync-alt text-white"></i>
                                    </div>
                                </div>
                                <div class="stat-content flex-grow-1">
                                    <h4 class="text-white mb-1">Devam Eden Siparişler</h4>
                                    <div class="d-flex align-items-baseline">
                                        <h2 class="text-glow mb-0 me-2">47</h2>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> +5
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tamamlanan Siparişler -->
                        <div class="stat-item glass-effect mb-4 p-3" style="border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <div class="avatar-sm bg-glow rounded-circle">
                                        <i class="fas fa-check-circle text-white"></i>
                                    </div>
                                </div>
                                <div class="stat-content flex-grow-1">
                                    <h4 class="text-white mb-1">Tamamlanan Siparişler</h4>
                                    <div class="d-flex align-items-baseline">
                                        <h2 class="text-glow mb-0 me-2">1,284</h2>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> +12
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Müşteri Memnuniyeti -->
                        <div class="stat-item glass-effect p-3" style="border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <div class="avatar-sm bg-glow rounded-circle">
                                        <i class="fas fa-smile text-white"></i>
                                    </div>
                                </div>
                                <div class="stat-content flex-grow-1">
                                    <h4 class="text-white mb-1">Müşteri Memnuniyeti</h4>
                                    <div class="d-flex align-items-baseline">
                                        <h2 class="text-glow mb-0 me-2">98%</h2>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> +2%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popüler Oyunlar ve Yeni Müşteriler -->
    <div class="row">
        <div class="col-xxl-8">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-header border-0 bg-dark-gradient p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Popüler Oyunlar</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-white">Oyun</th>
                                    <th class="text-white text-center">Aktif Sipariş</th>
                                    <th class="text-white text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-gamepad fa-lg text-purple me-3"></i>
                                            <span class="text-white">Valorant</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-25 text-white">24 Aktif</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="new_order.php" class="btn btn-sm btn-primary">Sipariş Ver</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-chess-knight fa-lg text-purple me-3"></i>
                                            <span class="text-white">League of Legends</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-25 text-white">18 Aktif</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="new_order.php" class="btn btn-sm btn-primary">Sipariş Ver</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-crosshairs fa-lg text-purple me-3"></i>
                                            <span class="text-white">CS:GO</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-25 text-white">15 Aktif</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="new_order.php" class="btn btn-sm btn-primary">Sipariş Ver</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-header border-0 bg-dark-gradient p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Yeni Müşteriler</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-gamepad text-purple"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 text-white">user123</h6>
                                    <small class="text-muted">Valorant</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">2 dk önce</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chess-knight text-purple"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 text-white">player456</h6>
                                    <small class="text-muted">League of Legends</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">5 dk önce</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-crosshairs text-purple"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 text-white">gamer789</h6>
                                    <small class="text-muted">CS:GO</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">8 dk önce</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-gamepad text-purple"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 text-white">pro321</h6>
                                    <small class="text-muted">Valorant</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">12 dk önce</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sipariş Detayları Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Sipariş Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="order-icon-circle mb-3">
                        <i class="fas fa-gamepad fa-3x"></i>
                    </div>
                    <h4 class="order-game mb-2"></h4>
                    <span class="order-status badge"></span>
                </div>
                
                <div class="order-details">
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Sipariş ID:</div>
                        <div class="col-7 order-id fw-bold"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Fiyat:</div>
                        <div class="col-7 order-price fw-bold"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Tarih:</div>
                        <div class="col-7 order-date"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Booster:</div>
                        <div class="col-7 order-booster"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">İlerleme:</div>
                        <div class="col-7">
                            <div class="progress">
                                <div class="progress-bar order-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <a href="#" class="btn btn-primary order-detail-link">Detayları Gör</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Genel Stiller */
:root {
    --purple: #6a11cb;
    --purple-light: #8e44ad;
    --purple-dark: #5a0fb0;
    --neon-blue: #00f3ff;
    --neon-purple: #9d4edd;
}

/* Dark Theme */
.dark-theme {
    background-color: #0a0b1e;
    color: #fff;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Kart Stilleri */
.card {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.hover-effect:hover {
    transform: translateY(-5px) rotateX(5deg);
    box-shadow: 0 15px 30px rgba(106, 17, 203, 0.2) !important;
}

.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}

/* Glow Effects */
.text-glow {
    color: var(--neon-blue);
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

.glow-effect {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.2);
}

.bg-glow {
    background: rgba(157, 78, 221, 0.3);
}

/* Character Wrapper */
.character-wrapper {
    position: relative;
    perspective: 1000px;
}

/* Floating Animation */
.floating-image {
    animation: floating 3s ease-in-out infinite;
    transform-style: preserve-3d;
}

@keyframes floating {
    0% {
        transform: translateY(0px) rotateY(0deg);
    }
    50% {
        transform: translateY(-10px) rotateY(5deg);
    }
    100% {
        transform: translateY(0px) rotateY(0deg);
    }
}

/* Button Styles */
.btn {
    padding: 0.8rem 1.5rem;
    font-weight: 500;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: 0.5s;
}

.btn:hover::before {
    left: 100%;
}

/* Chart Customization */
.apexcharts-canvas {
    background: transparent !important;
}

.apexcharts-title-text,
.apexcharts-legend-text {
    color: #fff !important;
}

.apexcharts-xaxis-label,
.apexcharts-yaxis-label {
    fill: #6c757d !important;
}

.apexcharts-grid line {
    stroke: rgba(255, 255, 255, 0.1) !important;
}

/* Glass Effect Light */
.glass-effect-light {
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.glass-effect-light:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* Avatar Styles */
.avatar-sm {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

/* Game Card Hover Effect */
.game-card {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
}

.game-card:hover {
    transform: translateY(-5px) rotateX(5deg);
    box-shadow: 0 15px 30px rgba(106, 17, 203, 0.2) !important;
}

.game-card .game-image {
    transition: all 0.3s ease;
}

.game-card:hover .game-image {
    transform: scale(1.1);
}

/* Customer List Styles */
.customer-list .customer-item {
    transition: all 0.3s ease;
}

.customer-list .customer-item:hover {
    transform: translateX(5px);
}

/* Table Styles */
.table > :not(caption) > * > * {
    background: transparent;
    color: #fff;
    border-bottom-color: rgba(255, 255, 255, 0.05);
}

.table > thead {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aylık Harcama Grafiği
    var monthlySpendingOptions = {
        series: [{
            name: 'Harcama',
            data: [<?php echo implode(',', array_values($spending_data)); ?>]
        }],
        chart: {
            type: 'area',
            height: 300,
            toolbar: {
                show: false
            },
            background: 'transparent'
        },
        colors: ['#9d4edd'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#9d4edd',
                        opacity: 0.4
                    },
                    {
                        offset: 100,
                        color: '#00f3ff',
                        opacity: 0.1
                    }
                ]
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: <?php echo json_encode($months); ?>,
            labels: {
                style: {
                    colors: '#6c757d'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6c757d'
                },
                formatter: function(value) {
                    return value.toFixed(2) + ' ₺';
                }
            }
        },
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.1)',
            strokeDashArray: 5
        },
        tooltip: {
            theme: 'dark'
        }
    };

    var monthlySpendingChart = new ApexCharts(document.querySelector("#monthlySpendingChart"), monthlySpendingOptions);
    monthlySpendingChart.render();

    // Oyun Dağılımı Grafiği
    var gameDistributionData = <?php echo json_encode(array_column($game_distribution, 'count')); ?>;
    var gameLabels = <?php echo json_encode(array_column($game_distribution, 'name')); ?>;

    var gameDistributionOptions = {
        series: gameDistributionData,
        chart: {
            type: 'donut',
            height: 300
        },
        labels: gameLabels,
        colors: ['#9d4edd', '#00f3ff', '#6a11cb', '#8e44ad', '#5a0fb0'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        },
        legend: {
            position: 'bottom',
            labels: {
                colors: '#fff'
            }
        },
        dataLabels: {
            enabled: true,
            style: {
                colors: ['#fff']
            },
            dropShadow: {
                enabled: true,
                blur: 3,
                opacity: 0.8
            }
        },
        stroke: {
            show: false
        },
        tooltip: {
            theme: 'dark'
        }
    };

    var gameDistributionChart = new ApexCharts(document.querySelector("#gameDistributionChart"), gameDistributionOptions);
    gameDistributionChart.render();
});
</script>

<?php require_once 'includes/footer.php'; ?>