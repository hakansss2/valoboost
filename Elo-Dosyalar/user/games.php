<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bakiyesini getir
try {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_balance = $stmt->fetchColumn();
} catch(PDOException $e) {
    $user_balance = 0;
}

// Aktif oyunları getir
try {
    $stmt = $conn->prepare("SELECT * FROM games WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['message'] = 'Oyunlar yüklenirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
    $games = [];
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Boost Hizmetleri</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($games)): ?>
                        <div class="row g-4">
                            <?php foreach ($games as $game): ?>
                                <div class="col-md-6">
                                    <div class="card game-card h-100">
                                        <?php if ($game['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($game['image']); ?>" 
                                                 class="card-img-top p-3" 
                                                 alt="<?php echo htmlspecialchars($game['name']); ?>">
                                        <?php endif; ?>
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo htmlspecialchars($game['name']); ?></h5>
                                            <p class="text-muted mb-4">
                                                <?php echo htmlspecialchars($game['description'] ?? ''); ?>
                                            </p>
                                            <a href="new_order.php?game=<?php echo $game['id']; ?>" 
                                               class="btn btn-primary">
                                                Sipariş Ver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                            <h5>Aktif Oyun Bulunamadı</h5>
                            <p class="text-muted">Şu anda sipariş verebileceğiniz aktif bir oyun bulunmamaktadır.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Bakiyeniz</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo number_format($user_balance, 2, ',', '.'); ?> ₺</h3>
                    <a href="add_balance.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Bakiye Yükle
                    </a>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Sipariş Adımları</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Oyun seçin</li>
                        <li class="mb-2">Mevcut ve hedef rankı belirleyin</li>
                        <li class="mb-2">Ekstra seçenekleri işaretleyin</li>
                        <li class="mb-2">Özel isteklerinizi belirtin</li>
                        <li>Siparişinizi onaylayın</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.game-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.game-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
    border-color: var(--bs-primary);
}

.game-card img {
    max-height: 150px;
    object-fit: contain;
}
</style>

<?php require_once 'includes/footer.php'; ?> 