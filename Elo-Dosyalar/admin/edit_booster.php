<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    redirect('../login.php');
}

// Booster ID kontrolü
$booster_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$booster_id) {
    $_SESSION['error'] = "Geçersiz booster ID'si.";
    header("Location: boosters.php");
    exit;
}

// Booster bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT u.*, b.*
        FROM users u
        JOIN boosters b ON u.id = b.user_id
        WHERE b.id = ?
    ");
    $stmt->execute([$booster_id]);
    $booster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booster) {
        $_SESSION['error'] = "Booster bulunamadı.";
        header("Location: boosters.php");
        exit;
    }

    // Booster'ın oyunlarını getir
    $stmt = $conn->prepare("SELECT game_id FROM booster_games WHERE booster_id = ?");
    $stmt->execute([$booster_id]);
    $booster_games = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $_SESSION['error'] = "Booster bilgileri getirilirken bir hata oluştu.";
    header("Location: boosters.php");
    exit;
}

// Aktif oyunları getir
try {
    $stmt = $conn->prepare("SELECT id, name FROM games WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Oyunlar yüklenirken bir hata oluştu.";
    $games = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="mdi mdi-account-edit me-2"></i>Booster Düzenle
                        </h5>
                        <a href="boosters.php" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left me-2"></i>Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="editBoosterForm">
                        <input type="hidden" name="id" value="<?php echo $booster_id; ?>">
                        
                        <div class="row g-4">
                            <!-- Hesap Bilgileri -->
                            <div class="col-md-6">
                                <div class="card bg-dark border-secondary h-100">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-account me-2"></i>Hesap Bilgileri
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label text-white">Kullanıcı Adı</label>
                                            <input type="text" name="username" class="form-control bg-dark text-white border-secondary"
                                                   value="<?php echo htmlspecialchars($booster['username']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">E-posta Adresi</label>
                                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary"
                                                   value="<?php echo htmlspecialchars($booster['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">Yeni Şifre</label>
                                            <input type="password" name="password" class="form-control bg-dark text-white border-secondary"
                                                   minlength="6">
                                            <small class="text-muted">Şifreyi değiştirmek istemiyorsanız boş bırakın.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">Yeni Şifre (Tekrar)</label>
                                            <input type="password" name="password_confirm" class="form-control bg-dark text-white border-secondary"
                                                   minlength="6">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">Durum</label>
                                            <select name="status" class="form-select bg-dark text-white border-secondary" required>
                                                <option value="active" <?php echo $booster['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="inactive" <?php echo $booster['status'] === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banka Bilgileri -->
                            <div class="col-md-6">
                                <div class="card bg-dark border-secondary h-100">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-bank me-2"></i>Banka Bilgileri
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label text-white">IBAN</label>
                                            <input type="text" name="iban" class="form-control bg-dark text-white border-secondary"
                                                   value="<?php echo htmlspecialchars($booster['iban'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">Banka Adı</label>
                                            <input type="text" name="bank_name" class="form-control bg-dark text-white border-secondary"
                                                   value="<?php echo htmlspecialchars($booster['bank_name'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-white">Hesap Sahibi</label>
                                            <input type="text" name="account_holder" class="form-control bg-dark text-white border-secondary"
                                                   value="<?php echo htmlspecialchars($booster['account_holder'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Oyunlar -->
                            <div class="col-12">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-gamepad-variant me-2"></i>Oyunlar
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <?php foreach ($games as $game): ?>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="games[]" 
                                                               value="<?php echo $game['id']; ?>" id="game_<?php echo $game['id']; ?>"
                                                               <?php echo in_array($game['id'], $booster_games) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label text-white" for="game_<?php echo $game['id']; ?>">
                                                            <?php echo htmlspecialchars($game['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-2"></i>Kaydet
                            </button>
                            <a href="boosters.php" class="btn btn-secondary ms-2">
                                <i class="mdi mdi-close me-2"></i>İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('editBoosterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Oyun kontrolü
    const checkboxes = this.querySelectorAll('input[name="games[]"]:checked');
    if (checkboxes.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'En az bir oyun seçmelisiniz.',
            background: '#1e293b',
            color: '#fff'
        });
        return;
    }
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Kaydediliyor...';
    
    fetch('update_booster.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500,
                background: '#1e293b',
                color: '#fff'
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message,
            background: '#1e293b',
            color: '#fff'
        });
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="mdi mdi-content-save me-2"></i>Kaydet';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 