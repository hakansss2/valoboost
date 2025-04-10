<?php
require_once 'includes/header.php';

// Sipariş ID kontrolü
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    $_SESSION['error'] = "Geçersiz sipariş ID'si.";
    header("Location: orders.php");
    exit;
}

// Siparişi getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, u.username as user_username, b.username as booster_username,
               g.name as game_name, r1.name as current_rank_name, r2.name as target_rank_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users b ON o.booster_id = b.id
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN ranks r1 ON o.current_rank_id = r1.id
        LEFT JOIN ranks r2 ON o.target_rank_id = r2.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error'] = "Sipariş bulunamadı.";
        header("Location: orders.php");
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Bir hata oluştu.";
    header("Location: orders.php");
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $booster_id = isset($_POST['booster_id']) && $_POST['booster_id'] ? (int)$_POST['booster_id'] : null;
    $game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
    $current_rank_id = isset($_POST['current_rank_id']) ? (int)$_POST['current_rank_id'] : 0;
    $target_rank_id = isset($_POST['target_rank_id']) ? (int)$_POST['target_rank_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validasyon
    $errors = [];
    
    if (!$user_id) {
        $errors[] = "Kullanıcı seçilmelidir.";
    }
    
    if (!$game_id) {
        $errors[] = "Oyun seçilmelidir.";
    }
    
    if (!$current_rank_id) {
        $errors[] = "Mevcut rank seçilmelidir.";
    }
    
    if (!$target_rank_id) {
        $errors[] = "Hedef rank seçilmelidir.";
    }
    
    if ($price <= 0) {
        $errors[] = "Fiyat 0'dan büyük olmalıdır.";
    }
    
    if ($progress < 0 || $progress > 100) {
        $errors[] = "İlerleme 0-100 arasında olmalıdır.";
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Siparişi güncelle
            $stmt = $conn->prepare("
                UPDATE orders 
                SET user_id = ?, booster_id = ?, game_id = ?, current_rank_id = ?, target_rank_id = ?,
                    price = ?, status = ?, progress = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user_id, $booster_id, $game_id, $current_rank_id, $target_rank_id, 
                           $price, $status, $progress, $notes, $order_id]);
            
            // Eğer sipariş tamamlandıysa ve daha önce tamamlanmadıysa, tamamlanma tarihini güncelle
            if ($status === 'completed' && $order['status'] !== 'completed') {
                $stmt = $conn->prepare("UPDATE orders SET completed_at = NOW() WHERE id = ?");
                $stmt->execute([$order_id]);
            }
            
            $conn->commit();
            $_SESSION['success'] = "Sipariş başarıyla güncellendi.";
            header("Location: order.php?id=" . $order_id);
            exit;
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors[] = "Sipariş güncellenirken bir hata oluştu.";
        }
    }
}

// Kullanıcıları getir
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'user' AND status = 'active' ORDER BY username ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $users = [];
}

// Boosterları getir
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'booster' AND status = 'active' ORDER BY username ASC");
    $stmt->execute();
    $boosters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $boosters = [];
}

// Oyunları getir
try {
    $stmt = $conn->prepare("SELECT id, name FROM games WHERE status = 'active' ORDER BY name ASC");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $games = [];
}

// Rankları getir
try {
    $stmt = $conn->prepare("SELECT id, name, game_id FROM ranks ORDER BY game_id, display_order ASC");
    $stmt->execute();
    $ranks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $ranks = [];
}

// Sipariş durumu için Türkçe metinler
function getStatusText($status) {
    switch($status) {
        case 'pending':
            return 'Beklemede';
        case 'in_progress':
            return 'Devam Ediyor';
        case 'completed':
            return 'Tamamlandı';
        case 'cancelled':
            return 'İptal Edildi';
        default:
            return 'Bilinmiyor';
    }
}
?>

<!-- Başlık ve Butonlar -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-edit fa-sm text-primary-300"></i>
        Sipariş Düzenle #<?php echo $order_id; ?>
    </h1>
    <div class="d-flex">
        <a href="orders.php" class="btn btn-sm btn-secondary shadow-sm me-2">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Geri Dön
        </a>
        <a href="order.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-info shadow-sm">
            <i class="fas fa-eye fa-sm text-white-50 me-1"></i> Görüntüle
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Sipariş Düzenleme Formu -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Sipariş Bilgileri</h6>
    </div>
    <div class="card-body">
        <form action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Kullanıcı</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                        <?php echo $user['id'] == $order['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="booster_id" class="form-label">Booster</label>
                        <select class="form-select" id="booster_id" name="booster_id">
                            <option value="">Seçiniz</option>
                            <?php foreach ($boosters as $booster): ?>
                                <option value="<?php echo $booster['id']; ?>"
                                        <?php echo $booster['id'] == $order['booster_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($booster['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="game_id" class="form-label">Oyun</label>
                        <select class="form-select" id="game_id" name="game_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['id']; ?>"
                                        <?php echo $game['id'] == $order['game_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="current_rank_id" class="form-label">Mevcut Rank</label>
                        <select class="form-select" id="current_rank_id" name="current_rank_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($ranks as $rank): ?>
                                <option value="<?php echo $rank['id']; ?>"
                                        data-game-id="<?php echo $rank['game_id']; ?>"
                                        <?php echo $rank['id'] == $order['current_rank_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="target_rank_id" class="form-label">Hedef Rank</label>
                        <select class="form-select" id="target_rank_id" name="target_rank_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($ranks as $rank): ?>
                                <option value="<?php echo $rank['id']; ?>"
                                        data-game-id="<?php echo $rank['game_id']; ?>"
                                        <?php echo $rank['id'] == $order['target_rank_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="price" class="form-label">Fiyat</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo $order['price']; ?>" step="0.01" min="0" required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                            <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>Devam Ediyor</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="progress" class="form-label">İlerleme</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="progress" name="progress" 
                                   value="<?php echo $order['progress']; ?>" step="1" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notlar</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php 
                    echo htmlspecialchars($order['notes']); 
                ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Güncelle</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Oyun seçildiğinde ilgili rankları filtrele
    const gameSelect = document.getElementById('game_id');
    const currentRankSelect = document.getElementById('current_rank_id');
    const targetRankSelect = document.getElementById('target_rank_id');
    
    function filterRanks() {
        const gameId = gameSelect.value;
        
        // Mevcut rank seçeneklerini filtrele
        Array.from(currentRankSelect.options).forEach(option => {
            if (option.value === '') return; // Boş seçeneği atla
            
            const optionGameId = option.getAttribute('data-game-id');
            option.style.display = (optionGameId === gameId || !gameId) ? '' : 'none';
        });
        
        // Hedef rank seçeneklerini filtrele
        Array.from(targetRankSelect.options).forEach(option => {
            if (option.value === '') return; // Boş seçeneği atla
            
            const optionGameId = option.getAttribute('data-game-id');
            option.style.display = (optionGameId === gameId || !gameId) ? '' : 'none';
        });
    }
    
    gameSelect.addEventListener('change', filterRanks);
    
    // Sayfa yüklendiğinde de filtrele
    filterRanks();
});
</script>

<?php require_once 'includes/footer.php'; ?> 