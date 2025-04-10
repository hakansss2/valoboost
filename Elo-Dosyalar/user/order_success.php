<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

// Başarılı mesajı kontrolü
if (!isset($_SESSION['success_message']) || !isset($_SESSION['order_id'])) {
    header('Location: games.php');
    exit;
}

$success_message = $_SESSION['success_message'];
$order_id = $_SESSION['order_id'];

// Session'dan mesajları temizle
unset($_SESSION['success_message']);
unset($_SESSION['order_id']);

// Sipariş bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, g.name as game_name, g.image as game_image,
               r1.name as current_rank_name, r1.image as current_rank_image,
               r2.name as target_rank_name, r2.image as target_rank_image
        FROM orders o
        JOIN games g ON o.game_id = g.id
        JOIN ranks r1 ON o.current_rank_id = r1.id
        JOIN ranks r2 ON o.target_rank_id = r2.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
} catch(PDOException $e) {
    $order = null;
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-success">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-4">Siparişiniz Başarıyla Oluşturuldu!</h2>
                    
                    <?php if ($order): ?>
                        <div class="order-details bg-light p-4 rounded mb-4">
                            <div class="row align-items-center mb-3">
                                <div class="col-12">
                                    <h4 class="mb-3">Sipariş #<?php echo $order_id; ?></h4>
                                    <?php if ($order['game_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($order['game_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['game_name']); ?>"
                                             class="img-fluid mb-2" style="max-height: 60px;">
                                    <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($order['game_name']); ?></h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <small class="text-muted d-block mb-1">Mevcut Rank</small>
                                        <?php if ($order['current_rank_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($order['current_rank_name']); ?>"
                                                 class="img-fluid mb-2" style="max-height: 40px;">
                                        <?php endif; ?>
                                        <div><?php echo htmlspecialchars($order['current_rank_name']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <small class="text-muted d-block mb-1">Hedef Rank</small>
                                        <?php if ($order['target_rank_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($order['target_rank_name']); ?>"
                                                 class="img-fluid mb-2" style="max-height: 40px;">
                                        <?php endif; ?>
                                        <div><?php echo htmlspecialchars($order['target_rank_name']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="price-info border-top pt-3">
                                <h5 class="mb-0">
                                    Toplam Tutar: 
                                    <span class="text-primary">
                                        <?php echo number_format($order['price'], 2, ',', '.'); ?> ₺
                                    </span>
                                </h5>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <p class="text-muted mb-4">
                        Siparişinizin durumunu "Siparişlerim" sayfasından takip edebilirsiniz.
                    </p>
                    
                    <div class="d-grid gap-2">
                        <a href="orders.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-list-ul me-2"></i>
                            Siparişlerim
                        </a>
                        <a href="games.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>
                            Ana Sayfaya Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.order-details {
    background-color: rgba(25, 135, 84, 0.05);
}

@keyframes checkmark {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.fa-check-circle {
    animation: checkmark 0.5s ease-in-out;
}
</style>

<?php require_once 'includes/footer.php'; ?> 