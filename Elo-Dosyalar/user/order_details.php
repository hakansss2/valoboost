<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Sipariş ID kontrolü
if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = (int)$_GET['id'];

// Sipariş detaylarını getir
try {
    $stmt = $conn->prepare("SELECT o.*, g.name as game_name, g.image as game_image, 
                           b.username as booster_name, b.avatar as booster_avatar,
                           cr.name as current_rank, cr.image as current_rank_image,
                           tr.name as target_rank, tr.image as target_rank_image
                           FROM orders o 
                           LEFT JOIN games g ON o.game_id = g.id
                           LEFT JOIN users b ON o.booster_id = b.id
                           LEFT JOIN ranks cr ON o.current_rank_id = cr.id
                           LEFT JOIN ranks tr ON o.target_rank_id = tr.id
                           WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        redirect('orders.php');
    }
} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Sipariş detayları yüklenirken bir hata oluştu.";
    redirect('orders.php');
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Sipariş Detayları</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="orders.php" class="text-purple-light">Siparişler</a></li>
                            <li class="breadcrumb-item active text-muted">Sipariş #<?php echo $order['id']; ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="orders.php" class="btn btn-outline-light btn-glow">
                        <i class="fas fa-arrow-left me-2"></i>Siparişlere Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sipariş Özeti -->
    <div class="row">
        <div class="col-xxl-8">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-4">
                                <img src="../<?php echo $order['game_image']; ?>" alt="<?php echo $order['game_name']; ?>" class="game-logo me-3">
                                <div>
                                    <h4 class="text-white mb-1"><?php echo $order['game_name']; ?></h4>
                                    <span class="badge bg-<?php echo getStatusColor($order['status']); ?> rounded-pill">
                                        <?php echo getStatusText($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="rank-progress mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="../<?php echo $order['current_rank_image']; ?>" alt="<?php echo $order['current_rank']; ?>" class="rank-image me-2">
                                        <span class="text-white"><?php echo $order['current_rank']; ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="text-white"><?php echo $order['target_rank']; ?></span>
                                        <img src="../<?php echo $order['target_rank_image']; ?>" alt="<?php echo $order['target_rank']; ?>" class="rank-image ms-2">
                                    </div>
                                </div>
                                <div class="progress glass-effect" style="height: 10px;">
                                    <div class="progress-bar progress-glow" role="progressbar" style="width: <?php echo $order['progress']; ?>%"></div>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="detail-item glass-effect p-3">
                                            <small class="text-muted d-block">Sipariş No</small>
                                            <span class="text-white">#<?php echo $order['id']; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="detail-item glass-effect p-3">
                                            <small class="text-muted d-block">Tarih</small>
                                            <span class="text-white"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="detail-item glass-effect p-3">
                                            <small class="text-muted d-block">Tutar</small>
                                            <span class="text-glow"><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="detail-item glass-effect p-3">
                                            <small class="text-muted d-block">Tahmini Süre</small>
                                            <span class="text-white"><?php echo $order['estimated_time']; ?> Saat</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4 mt-md-0">
                            <?php if ($order['booster_id']): ?>
                            <div class="booster-card glass-effect p-4 text-center">
                                <div class="booster-avatar mx-auto mb-3">
                                    <?php if ($order['booster_avatar']): ?>
                                        <img src="../<?php echo $order['booster_avatar']; ?>" alt="<?php echo $order['booster_name']; ?>" class="rounded-circle">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h5 class="text-white mb-2"><?php echo $order['booster_name']; ?></h5>
                                <span class="badge bg-success mb-3">Booster</span>
                                <div class="booster-stats">
                                    <div class="row g-3">
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h4 class="text-glow mb-1">98%</h4>
                                                <small class="text-muted">Başarı</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h4 class="text-glow mb-1">4.9</h4>
                                                <small class="text-muted">Puan</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h4 class="text-glow mb-1">+500</h4>
                                                <small class="text-muted">Sipariş</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="booster-card glass-effect p-4 text-center">
                                <div class="waiting-animation mb-3">
                                    <i class="fas fa-spinner fa-spin fa-3x text-glow"></i>
                                </div>
                                <h5 class="text-white mb-2">Booster Bekleniyor</h5>
                                <p class="text-muted mb-0">Siparişiniz en kısa sürede bir booster'a atanacaktır.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 mt-4 mt-xxl-0">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">Sipariş Seçenekleri</h5>
                </div>
                <div class="card-body p-4">
                    <div class="options-list">
                        <?php
                        $options = json_decode($order['options'], true) ?? [];
                        $optionIcons = [
                            'stream' => 'fa-video',
                            'priority' => 'fa-bolt',
                            'offline' => 'fa-user-secret',
                            'speed' => 'fa-tachometer-alt'
                        ];
                        $optionNames = [
                            'stream' => 'Yayın İzleme',
                            'priority' => 'Öncelikli Sipariş',
                            'offline' => 'Offline Mod',
                            'speed' => 'Hızlı Boost'
                        ];
                        foreach ($options as $option): ?>
                        <div class="option-item glass-effect p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="option-icon me-3">
                                    <i class="fas <?php echo $optionIcons[$option]; ?> text-glow"></i>
                                </div>
                                <div>
                                    <h6 class="text-white mb-1"><?php echo $optionNames[$option]; ?></h6>
                                    <small class="text-muted">Aktif</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($order['notes']): ?>
                    <div class="notes-section mt-4">
                        <h6 class="text-white mb-3">Sipariş Notları</h6>
                        <div class="glass-effect p-3">
                            <p class="text-muted mb-0"><?php echo nl2br($order['notes']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- İlerleme Geçmişi -->
    <div class="row mt-4">
        <div class="col-xxl-8">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">İlerleme Geçmişi</h5>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon bg-success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content glass-effect p-3">
                                <h6 class="text-white mb-1">Sipariş Oluşturuldu</h6>
                                <p class="text-muted mb-0">Siparişiniz başarıyla oluşturuldu ve sistem tarafından onaylandı.</p>
                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php if ($order['booster_id']): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-info">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="timeline-content glass-effect p-3">
                                <h6 class="text-white mb-1">Booster Atandı</h6>
                                <p class="text-muted mb-0"><?php echo $order['booster_name']; ?> siparişinizi üstlendi.</p>
                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($order['booster_assigned_at'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['status'] == 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-success">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="timeline-content glass-effect p-3">
                                <h6 class="text-white mb-1">Sipariş Tamamlandı</h6>
                                <p class="text-muted mb-0">Hedef ranka başarıyla ulaşıldı.</p>
                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($order['completed_at'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 mt-4 mt-xxl-0">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">Destek</h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center">
                        <img src="../assets/img/support.png" alt="Support" class="img-fluid mb-4" style="max-height: 150px;">
                        <h5 class="text-white mb-3">Yardıma mı ihtiyacınız var?</h5>
                        <p class="text-muted mb-4">Siparişinizle ilgili her türlü sorunuz için destek ekibimize ulaşabilirsiniz.</p>
                        <a href="support.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-glow">
                            <i class="fas fa-headset me-2"></i>Destek Talebi Oluştur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Theme */
.dark-theme {
    background-color: #0a0b1e;
    color: #fff;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Game Logo */
.game-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

/* Rank Images */
.rank-image {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

/* Progress Bar */
.progress-glow {
    background: linear-gradient(to right, var(--neon-purple), var(--neon-blue));
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
}

/* Booster Card */
.booster-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--neon-blue);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
}

.booster-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 3rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    height: 100%;
    width: 2px;
    background: rgba(255, 255, 255, 0.1);
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-icon {
    position: absolute;
    left: -3rem;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
}

.timeline-content {
    margin-left: 1rem;
    border-radius: 15px;
}

/* Option Icons */
.option-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Animations */
@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.floating-image {
    animation: floating 3s ease-in-out infinite;
}

/* Glow Effects */
.text-glow {
    color: var(--neon-blue);
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

/* Background Gradients */
.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}
</style>

<?php require_once 'includes/footer.php'; ?> 