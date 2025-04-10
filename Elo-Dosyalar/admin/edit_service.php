<?php
require_once 'includes/header.php';

// Hizmet ID kontrolü
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$service_id) {
    $_SESSION['error'] = "Geçersiz hizmet ID'si.";
    header("Location: services.php");
    exit;
}

// Hizmeti getir
try {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        $_SESSION['error'] = "Hizmet bulunamadı.";
        header("Location: services.php");
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Bir hata oluştu.";
    header("Location: services.php");
    exit;
}

// Oyunları getir
try {
    $stmt = $conn->prepare("SELECT id, name FROM games WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Oyunlar getirilirken bir hata oluştu.";
    $games = [];
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
    $name = isset($_POST['name']) ? clean($_POST['name']) : '';
    $description = isset($_POST['description']) ? clean($_POST['description']) : '';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $status = isset($_POST['status']) ? clean($_POST['status']) : 'active';
    
    $errors = [];

    // Validasyon
    if (!$game_id) {
        $errors[] = "Oyun seçilmelidir.";
    }

    if (empty($name)) {
        $errors[] = "Hizmet adı gereklidir.";
    }

    if ($price <= 0) {
        $errors[] = "Fiyat 0'dan büyük olmalıdır.";
    }

    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = "Geçersiz durum.";
    }

    if (empty($errors)) {
        try {
            // Hizmeti güncelle
            $stmt = $conn->prepare("
                UPDATE services 
                SET game_id = ?, name = ?, description = ?, price = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$game_id, $name, $description, $price, $status, $service_id]);
            
            $_SESSION['success'] = "Hizmet başarıyla güncellendi.";
            header("Location: services.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Hizmet güncellenirken bir hata oluştu.";
        }
    }
}
?>

<!-- Başlık -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Hizmet Düzenle</h1>
    <a href="services.php" class="btn btn-secondary">
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

<!-- Hizmet Düzenleme Formu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="" id="editServiceForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="game_id" class="form-label">Oyun</label>
                        <select class="form-select" id="game_id" name="game_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['id']; ?>"
                                        <?php echo $game['id'] == $service['game_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Hizmet Adı</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($service['name']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="price" class="form-label">Fiyat</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo $service['price']; ?>" step="0.01" min="0" required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo $service['status'] == 'active' ? 'selected' : ''; ?>>
                                Aktif
                            </option>
                            <option value="inactive" <?php echo $service['status'] == 'inactive' ? 'selected' : ''; ?>>
                                Pasif
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                    echo htmlspecialchars($service['description']); 
                ?></textarea>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
                <a href="services.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> İptal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form değişikliklerini izle
    watchFormChanges('editServiceForm');
});
</script>

<?php require_once 'includes/footer.php'; ?> 