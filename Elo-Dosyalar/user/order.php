<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

// Sipariş ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Sipariş bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, g.name as game_name, g.image as game_image,
               r1.name as current_rank_name, r1.image as current_rank_image,
               r2.name as target_rank_name, r2.image as target_rank_image,
               u.username as booster_username
        FROM orders o
        JOIN games g ON o.game_id = g.id
        JOIN ranks r1 ON o.current_rank_id = r1.id
        JOIN ranks r2 ON o.target_rank_id = r2.id
        LEFT JOIN users u ON o.booster_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Sipariş bulunamadı.";
        header('Location: orders.php');
        exit;
    }
    
    // Mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM order_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.order_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Bir hata oluştu.";
    header('Location: orders.php');
    exit;
}

// Yeni mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = clean($_POST['message']);
    
    if (!empty($message)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO order_messages (order_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$order_id, $user_id, $message]);
            
            header("Location: order.php?id=" . $order_id);
            exit;
        } catch(PDOException $e) {
            $_SESSION['error'] = "Mesaj gönderilemedi.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Sipariş Detayı #<?php echo $order_id; ?></h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="orders.php" class="text-purple-light">Siparişler</a></li>
                            <li class="breadcrumb-item active text-muted">Sipariş #<?php echo $order_id; ?></li>
                        </ol>
                    </nav>
                </div>
                <a href="orders.php" class="btn btn-glow btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Siparişlere Dön
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sipariş Detayları -->
        <div class="col-lg-4">
            <div class="card border-0 glass-effect mb-4" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="card-title text-white mb-0">Sipariş Bilgileri</h5>
                </div>
                <div class="card-body">
                    <!-- Oyun Bilgisi -->
                    <div class="game-info d-flex align-items-center mb-4">
                        <div class="game-icon me-3">
                            <img src="../<?php echo $order['game_image']; ?>" alt="<?php echo $order['game_name']; ?>" 
                                 class="img-fluid" style="width: 48px;">
                        </div>
                        <div>
                            <h6 class="text-white mb-1"><?php echo $order['game_name']; ?></h6>
                            <span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?> bg-opacity-25 text-white glow-badge">
                                <?php echo getOrderStatusText($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Rank Bilgileri -->
                    <div class="rank-info glass-effect p-3 mb-4" style="border-radius: 15px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Mevcut Rank</small>
                                <img src="../<?php echo $order['current_rank_image']; ?>" alt="<?php echo $order['current_rank_name']; ?>" 
                                     class="img-fluid mb-2" style="width: 48px;">
                                <span class="d-block text-white"><?php echo $order['current_rank_name']; ?></span>
                            </div>
                            <div class="rank-arrow">
                                <i class="fas fa-arrow-right text-purple-light"></i>
                            </div>
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Hedef Rank</small>
                                <img src="../<?php echo $order['target_rank_image']; ?>" alt="<?php echo $order['target_rank_name']; ?>" 
                                     class="img-fluid mb-2" style="width: 48px;">
                                <span class="d-block text-white"><?php echo $order['target_rank_name']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- İlerleme Durumu -->
                    <?php if ($order['status'] === 'in_progress'): ?>
                        <div class="progress-info glass-effect p-3 mb-4" style="border-radius: 15px;">
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
                    <div class="order-details">
                        <div class="detail-item glass-effect p-3 mb-3" style="border-radius: 15px;">
                            <div class="row">
                                <div class="col-5 text-muted">Sipariş No:</div>
                                <div class="col-7 text-white">#<?php echo $order['id']; ?></div>
                            </div>
                        </div>
                        <div class="detail-item glass-effect p-3 mb-3" style="border-radius: 15px;">
                            <div class="row">
                                <div class="col-5 text-muted">Fiyat:</div>
                                <div class="col-7 text-glow"><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</div>
                            </div>
                        </div>
                        <div class="detail-item glass-effect p-3 mb-3" style="border-radius: 15px;">
                            <div class="row">
                                <div class="col-5 text-muted">Tarih:</div>
                                <div class="col-7 text-white"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php if ($order['booster_username']): ?>
                            <div class="detail-item glass-effect p-3 mb-3" style="border-radius: 15px;">
                                <div class="row">
                                    <div class="col-5 text-muted">Booster:</div>
                                    <div class="col-7 text-white"><?php echo $order['booster_username']; ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($order['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-outline-danger btn-lg w-100 neon-border mt-3"
                                onclick="cancelOrder(<?php echo $order['id']; ?>, <?php echo $order['price']; ?>)">
                            <i class="fas fa-times me-2"></i>Siparişi İptal Et
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Alanı -->
        <div class="col-lg-8">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="card-title text-white mb-0">Mesajlar</h5>
                </div>
                <div class="card-body">
                    <div class="chat-messages p-4" style="height: 400px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Henüz mesaj bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="chat-message mb-4 <?php echo $message['user_id'] == $user_id ? 'text-end' : ''; ?>">
                                    <div class="d-inline-block glass-effect p-3" 
                                         style="border-radius: 15px; max-width: 80%; 
                                                <?php echo $message['user_id'] == $user_id ? 'background: rgba(106, 17, 203, 0.1) !important;' : ''; ?>">
                                        <div class="message-header d-flex justify-content-between align-items-center mb-2">
                                            <span class="username text-purple-light">
                                                <?php echo $message['username']; ?>
                                                <?php if ($message['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger ms-1">Admin</span>
                                                <?php elseif ($message['role'] === 'booster'): ?>
                                                    <span class="badge bg-primary ms-1">Booster</span>
                                                <?php endif; ?>
                                            </span>
                                            <small class="text-muted ms-2">
                                                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="message-content text-white">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Mesaj Gönderme Formu -->
                    <form action="" method="post" class="mt-4">
                        <div class="input-group">
                            <textarea class="form-control glass-effect border-0 text-white" 
                                      name="message" rows="3" 
                                      placeholder="Mesajınızı yazın..."
                                      style="background: rgba(255, 255, 255, 0.05) !important; resize: none;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-glow btn-primary w-100 mt-3">
                            <i class="fas fa-paper-plane me-2"></i>Gönder
                        </button>
                    </form>
                </div>
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

/* Chat Stilleri */
.chat-messages {
    scrollbar-width: thin;
    scrollbar-color: var(--purple) transparent;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background-color: var(--purple);
    border-radius: 20px;
}

/* Form Stilleri */
.form-control {
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
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
</style>

<script>
// Sipariş iptal fonksiyonu
function cancelOrder(orderId, price) {
    Swal.fire({
        title: 'Siparişi İptal Et',
        html: `Bu siparişi iptal etmek istediğinize emin misiniz?<br>
              <small class="text-muted">İade Tutarı: ${price.toFixed(2)} ₺</small>`,
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
            window.location.href = `cancel_order.php?id=${orderId}`;
        }
    });
}

// Mesaj alanını otomatik olarak aşağı kaydır
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
});
</script>

<?php require_once 'includes/footer.php'; ?> 