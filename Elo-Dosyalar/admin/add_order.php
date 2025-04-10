<?php
require_once 'includes/header.php';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $booster_id = isset($_POST['booster_id']) ? (int)$_POST['booster_id'] : null;
    $game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $details = isset($_POST['details']) ? clean($_POST['details']) : '';
    $status = isset($_POST['status']) ? clean($_POST['status']) : 'pending';
    
    $errors = [];

    // Validasyon
    if (!$user_id) {
        $errors[] = "Kullanıcı seçilmelidir.";
    }

    if (!$game_id) {
        $errors[] = "Oyun seçilmelidir.";
    }

    if (!$service_id) {
        $errors[] = "Hizmet seçilmelidir.";
    }

    if ($price <= 0) {
        $errors[] = "Fiyat 0'dan büyük olmalıdır.";
    }

    if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
        $errors[] = "Geçersiz sipariş durumu.";
    }

    if (empty($errors)) {
        try {
            // Siparişi ekle
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, booster_id, game_id, service_id, price, details, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $booster_id, $game_id, $service_id, $price, $details, $status]);
            
            $_SESSION['success'] = "Sipariş başarıyla eklendi.";
            header("Location: orders.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Sipariş eklenirken bir hata oluştu.";
        }
    }
}

// Kullanıcıları getir
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'user' ORDER BY username");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $users = [];
}

// Boosterları getir
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'booster' ORDER BY username");
    $stmt->execute();
    $boosters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $boosters = [];
}

// Oyunları getir
try {
    $stmt = $conn->prepare("SELECT id, name FROM games ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $games = [];
}

// Hizmetleri getir
try {
    $stmt = $conn->prepare("SELECT id, name FROM services ORDER BY name");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $services = [];
}
?>

<!-- Başlık -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Yeni Sipariş</h1>
    <a href="orders.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Geri Dön
    </a>
</div>

<!-- Hata Mesajları -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Sipariş Ekleme Formu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="" id="addOrderForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Kullanıcı</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
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
                                <option value="<?php echo $booster['id']; ?>">
                                    <?php echo htmlspecialchars($booster['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Booster daha sonra da atanabilir.</small>
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
                                <option value="<?php echo $game['id']; ?>">
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Hizmet</label>
                        <select class="form-select" id="service_id" name="service_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="price" class="form-label">Fiyat</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="price" name="price" 
                                   step="0.01" min="0" required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Beklemede</option>
                            <option value="in_progress">Devam Ediyor</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="cancelled">İptal Edildi</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="details" class="form-label">Detaylar</label>
                <textarea class="form-control" id="details" name="details" rows="3"></textarea>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> İptal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select2 için kullanıcı ve booster seçimlerini etkinleştir
    $('#user_id, #booster_id').select2({
        theme: 'bootstrap-5'
    });

    // Form değişikliklerini izle
    watchFormChanges('addOrderForm');
});
</script>

<?php require_once 'includes/footer.php'; ?> 