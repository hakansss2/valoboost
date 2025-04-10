<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Booster kontrolü
if (!isBooster()) {
    redirect('../login.php');
}

// Booster bilgilerini getir
$stmt = $conn->prepare("
    SELECT u.*, b.*
    FROM users u
    JOIN boosters b ON u.id = b.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$booster = $stmt->fetch(PDO::FETCH_ASSOC);

// Booster'ın oyunlarını getir
$stmt = $conn->prepare("
    SELECT g.id, g.name
    FROM games g
    JOIN booster_games bg ON g.id = bg.game_id
    WHERE bg.booster_id = ?
");
$stmt->execute([$booster['id']]);
$booster_games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm aktif oyunları getir
$stmt = $conn->prepare("SELECT id, name FROM games WHERE status = 'active' ORDER BY name");
$stmt->execute();
$all_games = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 glass-effect">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="mdi mdi-account-circle me-2"></i>Profil Bilgileri
                        </h5>
                    </div>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        <div class="row g-4">
                            <!-- Sol Kısım: Hesap ve Banka Bilgileri -->
                            <div class="col-lg-8">
                                <!-- Hesap Bilgileri -->
                                <div class="card bg-dark border-secondary mb-4">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-account me-2"></i>Hesap Bilgileri
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">Kullanıcı Adı</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-account"></i>
                                                    </span>
                                                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                                                           value="<?php echo htmlspecialchars($booster['username']); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">E-posta</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-email"></i>
                                                    </span>
                                                    <input type="email" name="email" class="form-control bg-dark text-white border-secondary" 
                                                           value="<?php echo htmlspecialchars($booster['email']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">Yeni Şifre</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-lock"></i>
                                                    </span>
                                                    <input type="password" name="password" class="form-control bg-dark text-white border-secondary" 
                                                           minlength="6">
                                                </div>
                                                <small class="text-muted">Şifreyi değiştirmek istemiyorsanız boş bırakın</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">Yeni Şifre (Tekrar)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-lock-check"></i>
                                                    </span>
                                                    <input type="password" name="password_confirm" class="form-control bg-dark text-white border-secondary" 
                                                           minlength="6">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Banka Bilgileri -->
                                <div class="card bg-dark border-secondary">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-bank me-2"></i>Banka Bilgileri
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label text-white">IBAN</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-credit-card"></i>
                                                    </span>
                                                    <input type="text" name="iban" class="form-control bg-dark text-white border-secondary" 
                                                           value="<?php echo htmlspecialchars($booster['iban'] ?? ''); ?>"
                                                           placeholder="TR__ ____ ____ ____ ____ ____ __">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">Banka Adı</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-bank"></i>
                                                    </span>
                                                    <input type="text" name="bank_name" class="form-control bg-dark text-white border-secondary" 
                                                           value="<?php echo htmlspecialchars($booster['bank_name'] ?? ''); ?>"
                                                           placeholder="Örn: Ziraat Bankası">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-white">Hesap Sahibi</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="mdi mdi-account-box"></i>
                                                    </span>
                                                    <input type="text" name="account_holder" class="form-control bg-dark text-white border-secondary" 
                                                           value="<?php echo htmlspecialchars($booster['account_holder'] ?? ''); ?>"
                                                           placeholder="Ad Soyad">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sağ Kısım: Oyun Tercihleri ve İstatistikler -->
                            <div class="col-lg-4">
                                <!-- Oyun Tercihleri -->
                                <div class="card bg-dark border-secondary mb-4">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-gamepad-variant me-2"></i>Oyun Tercihleri
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="game-list">
                                            <?php foreach ($all_games as $game): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="games[]" 
                                                           value="<?php echo $game['id']; ?>" id="game_<?php echo $game['id']; ?>"
                                                           <?php echo in_array($game['id'], array_column($booster_games, 'id')) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label text-white" for="game_<?php echo $game['id']; ?>">
                                                        <?php echo htmlspecialchars($game['name']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- İstatistikler -->
                                <div class="card bg-dark border-secondary">
                                    <div class="card-header border-secondary">
                                        <h6 class="card-title mb-0 text-white">
                                            <i class="mdi mdi-chart-bar me-2"></i>İstatistikler
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-white">Tamamlanan Siparişler</span>
                                                <span class="text-success"><?php echo $booster['completed_orders']; ?></span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo ($booster['completed_orders'] / max(1, $booster['total_orders'])) * 100; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-white">Başarı Oranı</span>
                                                <span class="text-info"><?php echo number_format($booster['success_rate'], 1); ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-info" style="width: <?php echo $booster['success_rate']; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-white">Ortalama Puan</span>
                                                <span class="text-warning"><?php echo number_format($booster['average_rating'], 1); ?>/5</span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-warning" style="width: <?php echo ($booster['average_rating'] / 5) * 100; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-2"></i>Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Genel Stiller */
.glass-effect {
    background: rgba(26, 27, 58, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Form Elemanları */
.form-control, .input-group-text {
    border-radius: 8px;
}

.input-group .form-control {
    border-start-start-radius: 0;
    border-end-start-radius: 0;
}

.input-group-text {
    border-end-end-radius: 0;
    border-start-end-radius: 0;
    width: 42px;
    justify-content: center;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
}

/* Oyun Listesi */
.game-list {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 10px;
}

.game-list::-webkit-scrollbar {
    width: 6px;
}

.game-list::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.game-list::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.5);
    border-radius: 3px;
}

.game-list::-webkit-scrollbar-thumb:hover {
    background: rgba(99, 102, 241, 0.7);
}

/* İstatistik Kartları */
.stat-item {
    padding: 10px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
}

.progress {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

/* Form Check */
.form-check-input {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Kartlar */
.card {
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    background: rgba(99, 102, 241, 0.1);
}

/* Butonlar */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
</style>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Şifre kontrolü
    const password = this.querySelector('input[name="password"]').value;
    const passwordConfirm = this.querySelector('input[name="password_confirm"]').value;
    
    if (password && password !== passwordConfirm) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Şifreler eşleşmiyor.',
            background: '#1e293b',
            color: '#fff'
        });
        return;
    }
    
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
    
    fetch('update_profile.php', {
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
        submitBtn.innerHTML = '<i class="mdi mdi-content-save me-2"></i>Değişiklikleri Kaydet';
    });
});

// IBAN formatı
document.querySelector('input[name="iban"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^\d]/g, '');
    let formatted = '';
    
    for (let i = 0; i < value.length; i++) {
        if (i === 2 || i === 6 || i === 10 || i === 14 || i === 18 || i === 22) {
            formatted += ' ';
        }
        formatted += value[i];
    }
    
    e.target.value = formatted;
});
</script>

<?php require_once 'includes/footer.php'; ?> 