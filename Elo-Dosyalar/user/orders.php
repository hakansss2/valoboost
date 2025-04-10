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
    // Siparişleri getir
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
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Veriler yüklenirken bir hata oluştu.";
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Siparişlerim</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active text-muted">Siparişler</li>
                        </ol>
                    </nav>
                </div>
                <a href="new_order.php" class="btn btn-glow btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Yeni Sipariş
                </a>
            </div>
        </div>
    </div>

    <!-- Sipariş Kartları -->
    <div class="row g-4">
        <?php if (empty($orders)): ?>
            <div class="col-12">
                <div class="card border-0 bg-dark-card glass-effect" style="border-radius: 20px;">
                    <div class="card-body text-center py-5">
                        <div class="empty-state-icon mb-4">
                            <i class="fas fa-shopping-cart fa-3x"></i>
                        </div>
                        <h4 class="text-white mb-3">Henüz Sipariş Bulunmuyor</h4>
                        <p class="text-muted mb-4">İlk siparişinizi oluşturarak boost yolculuğunuza başlayın!</p>
                        <a href="new_order.php" class="btn btn-glow btn-primary btn-lg px-5">
                            <i class="fas fa-plus me-2"></i>Hemen Sipariş Ver
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 h-100 glass-effect" style="border-radius: 20px;">
                        <div class="card-header border-0 bg-dark-gradient p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 text-white">Sipariş #<?php echo $order['id']; ?></h5>
                                    <span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?> bg-opacity-25 text-white glow-badge">
                                        <?php echo getOrderStatusText($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-price">
                                    <h5 class="mb-0 text-glow"><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Oyun Bilgisi -->
                            <div class="game-info d-flex align-items-center mb-4">
                                <div class="game-icon me-3">
                                    <img src="../<?php echo $order['game_image']; ?>" alt="<?php echo $order['game_name']; ?>" class="img-fluid" style="width: 48px;">
                                </div>
                                <div>
                                    <h6 class="text-white mb-1"><?php echo $order['game_name']; ?></h6>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2"><?php echo $order['current_rank']; ?></small>
                                        <i class="fas fa-arrow-right text-muted mx-2"></i>
                                        <small class="text-muted"><?php echo $order['target_rank']; ?></small>
                                    </div>
                                </div>
                            </div>

                            <?php if ($order['status'] === 'in_progress'): ?>
                                <div class="progress-info mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">İlerleme Durumu</span>
                                        <span class="text-glow"><?php echo $order['progress']; ?>%</span>
                                    </div>
                                    <div class="progress glow-effect" style="height: 10px;">
                                        <div class="progress-bar neon-gradient" role="progressbar" 
                                             style="width: <?php echo $order['progress']; ?>%"
                                             aria-valuenow="<?php echo $order['progress']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Sipariş Detayları -->
                            <div class="order-details mb-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="detail-item glass-effect p-3">
                                            <div class="text-muted small mb-1">Tarih</div>
                                            <div class="text-white"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <?php if ($order['booster_name']): ?>
                                        <div class="col-6">
                                            <div class="detail-item glass-effect p-3">
                                                <div class="text-muted small mb-1">Booster</div>
                                                <div class="text-white"><?php echo htmlspecialchars($order['booster_name']); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Butonlar -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-glow btn-primary btn-sm">
                                        <i class="fas fa-eye me-2"></i>Detayları Gör
                                    </a>
                                </div>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="button" 
                                            class="btn btn-glow btn-danger btn-sm cancel-order"
                                            data-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-times me-2"></i>İptal Et
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Sipariş Detayları Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 glass-effect" style="border-radius: 20px;">
            <div class="modal-header bg-dark-gradient border-0">
                <h5 class="modal-title text-white" id="orderModalLabel">Sipariş Detayları</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="order-icon-circle glow-effect mb-3">
                        <i class="fas fa-gamepad fa-3x"></i>
                    </div>
                    <h4 class="order-game text-white mb-2"></h4>
                    <span class="order-status badge glow-badge"></span>
                </div>
                
                <div class="order-details">
                    <div class="detail-item glass-effect mb-3">
                        <div class="row align-items-center">
                            <div class="col-5 text-muted">Sipariş ID:</div>
                            <div class="col-7 order-id text-white"></div>
                        </div>
                    </div>
                    <div class="detail-item glass-effect mb-3">
                        <div class="row align-items-center">
                            <div class="col-5 text-muted">Fiyat:</div>
                            <div class="col-7 order-price text-glow"></div>
                        </div>
                    </div>
                    <div class="detail-item glass-effect mb-3">
                        <div class="row align-items-center">
                            <div class="col-5 text-muted">Tarih:</div>
                            <div class="col-7 order-date text-white"></div>
                        </div>
                    </div>
                    <div class="detail-item glass-effect mb-3">
                        <div class="row align-items-center">
                            <div class="col-5 text-muted">Booster:</div>
                            <div class="col-7 order-booster text-white"></div>
                        </div>
                    </div>
                    <div class="detail-item glass-effect mb-3">
                        <div class="row align-items-center">
                            <div class="col-5 text-muted">İlerleme:</div>
                            <div class="col-7">
                                <div class="progress glow-effect">
                                    <div class="progress-bar neon-gradient order-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Kapat</button>
                <a href="#" class="btn btn-glow btn-primary order-detail-link">Detayları Gör</a>
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
    background: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Kart Stilleri */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}

.bg-dark-card {
    background: #1a1b3a;
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

.glow-badge {
    box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.5);
}

/* Neon Gradient */
.neon-gradient {
    background: linear-gradient(to right, var(--neon-purple), var(--neon-blue));
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
}

/* Game Image */
.game-image-wrapper {
    position: relative;
    padding: 10px;
}

.game-image-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(106, 17, 203, 0.1);
    border-radius: 15px;
    z-index: -1;
    transition: all 0.3s ease;
}

.game-image-wrapper:hover::before {
    transform: scale(1.1);
    background: rgba(106, 17, 203, 0.2);
}

/* Rank Stilleri */
.rank-image-wrapper {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 15px;
    transition: all 0.3s ease;
    transform-style: preserve-3d;
}

.rank-image-wrapper:hover {
    transform: translateY(-3px) rotateY(10deg);
    box-shadow: 0 5px 15px rgba(106, 17, 203, 0.2);
}

.rank-arrow {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(106, 17, 203, 0.1);
    border-radius: 50%;
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.2);
}

/* Progress Bar */
.progress {
    background-color: rgba(106, 17, 203, 0.1);
    border-radius: 10px;
    overflow: hidden;
    height: 8px;
}

/* Buton Stilleri */
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

.neon-border {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.neon-border:hover {
    border-color: var(--neon-red);
    box-shadow: 0 0 20px rgba(255, 0, 0, 0.4);
}

/* Modal Stilleri */
.modal-content {
    border: none;
    box-shadow: 0 15px 30px rgba(0,0,0,0.3);
}

.order-icon-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--purple) 0%, var(--purple-light) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    box-shadow: 0 0 30px rgba(106, 17, 203, 0.4);
}

/* Animasyonlar */
@keyframes glow {
    0% {
        box-shadow: 0 0 5px rgba(106, 17, 203, 0.2);
    }
    50% {
        box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
    }
    100% {
        box-shadow: 0 0 5px rgba(106, 17, 203, 0.2);
    }
}

.glow-animation {
    animation: glow 2s infinite;
}
</style>

<script>
$(document).ready(function() {
    // Sipariş iptal işlemi
    $('.cancel-order').click(function() {
        const orderId = $(this).data('id');
        
        Swal.fire({
            title: 'Siparişi İptal Et',
            text: 'Bu siparişi iptal etmek istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, İptal Et',
            cancelButtonText: 'Vazgeç',
            background: '#1a1b3a',
            color: '#fff',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'cancel_order.php',
                    method: 'POST',
                    data: { order_id: orderId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Başarılı!',
                                text: 'Sipariş başarıyla iptal edildi.',
                                icon: 'success',
                                background: '#1a1b3a',
                                color: '#fff'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Hata!',
                                text: response.message,
                                icon: 'error',
                                background: '#1a1b3a',
                                color: '#fff'
                            });
                        }
                    }
                });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>