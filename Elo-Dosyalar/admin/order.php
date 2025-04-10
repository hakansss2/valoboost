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
        SELECT o.*, 
        u.username as user_username,
        b.username as booster_username,
        g.name as game_name,
        r1.name as current_rank,
        r2.name as target_rank
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

// Sipariş durumunu güncelle
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    try {
        $conn->beginTransaction();
        
        // Siparişi güncelle
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Eğer sipariş tamamlandıysa ve daha önce tamamlanmadıysa, tamamlanma tarihini güncelle
        if ($new_status === 'completed' && $order['status'] !== 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET completed_at = NOW() WHERE id = ?");
            $stmt->execute([$order_id]);
        }
        
        // Bildirim gönder
        if (function_exists('createOrderStatusNotification')) {
            createOrderStatusNotification($order['user_id'], $order_id, $new_status);
        }
        
        $conn->commit();
        $_SESSION['success'] = "Sipariş durumu başarıyla güncellendi.";
        header("Location: order.php?id=" . $order_id);
        exit;
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Sipariş güncellenirken bir hata oluştu.";
    }
} 

// İlerleme güncelle
if (isset($_POST['update_progress'])) {
    $new_progress = (int)$_POST['progress'];
    
    // İlerleme değerini kontrol et
    if ($new_progress < 0 || $new_progress > 100) {
        $_SESSION['error'] = "İlerleme değeri 0-100 arasında olmalıdır.";
    } else {
        try {
            // Siparişi güncelle
            $stmt = $conn->prepare("UPDATE orders SET progress = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_progress, $order_id]);
            
            $_SESSION['success'] = "Sipariş ilerlemesi başarıyla güncellendi.";
            header("Location: order.php?id=" . $order_id);
            exit;
        } catch(PDOException $e) {
            $_SESSION['error'] = "Sipariş güncellenirken bir hata oluştu.";
        }
    }
}

// Booster ata
if (isset($_POST['assign_booster'])) {
    $booster_id = (int)$_POST['booster_id'];
    
    if (!$booster_id) {
        $_SESSION['error'] = "Geçerli bir booster seçmelisiniz.";
    } else {
        try {
            // Siparişi güncelle
            $stmt = $conn->prepare("UPDATE orders SET booster_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$booster_id, $order_id]);
            
            $_SESSION['success'] = "Booster başarıyla atandı.";
            header("Location: order.php?id=" . $order_id);
            exit;
        } catch(PDOException $e) {
            $_SESSION['error'] = "Booster atanırken bir hata oluştu.";
        }
    }
}

// Sipariş mesajlarını getir
try {
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
    $messages = [];
}

// Sipariş durumu için renk sınıfları
function getStatusClass($status) {
    switch($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
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
        <i class="fas fa-shopping-cart fa-sm text-primary-300"></i>
        Sipariş Detayı #<?php echo $order_id; ?>
    </h1>
    <div class="d-flex">
        <a href="orders.php" class="btn btn-sm btn-secondary shadow-sm me-2">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Geri Dön
        </a>
        <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-edit fa-sm text-white-50 me-1"></i> Düzenle
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

<!-- Sipariş Detayları -->
<div class="row">
    <div class="col-lg-8">
        <!-- Sipariş Bilgileri Kartı -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Sipariş Bilgileri</h6>
                <span class="badge bg-<?php echo getStatusClass($order['status']); ?> rounded-pill" data-status>
                    <?php echo getStatusText($order['status']); ?>
                </span>
            </div>
            <div class="card-body bg-dark text-white">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5 class="text-white-50 mb-1 small text-uppercase">Sipariş ID</h5>
                        <p class="mb-3 text-white">#<?php echo $order['id']; ?></p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Kullanıcı</h5>
                        <p class="mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2 bg-primary rounded-circle">
                                    <span class="avatar-text"><?php echo strtoupper(substr($order['user_username'], 0, 1)); ?></span>
                                </div>
                                <div class="text-white"><?php echo htmlspecialchars($order['user_username']); ?></div>
                            </div>
                        </p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Oyun</h5>
                        <p class="mb-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($order['game_name']); ?></span>
                        </p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Mevcut Rank</h5>
                        <p class="mb-3 text-white"><?php echo htmlspecialchars($order['current_rank']); ?></p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Hedef Rank</h5>
                        <p class="mb-3 text-white"><?php echo htmlspecialchars($order['target_rank']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-white-50 mb-1 small text-uppercase">Booster</h5>
                        <p class="mb-3">
                            <?php if ($order['booster_username']): ?>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2 bg-info rounded-circle">
                                        <span class="avatar-text"><?php echo strtoupper(substr($order['booster_username'], 0, 1)); ?></span>
                                    </div>
                                    <div class="text-white"><?php echo htmlspecialchars($order['booster_username']); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="text-warning">Atanmamış</span>
                            <?php endif; ?>
                        </p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Fiyat</h5>
                        <p class="mb-3">
                            <span class="text-success fw-bold"><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</span>
                        </p>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">İlerleme</h5>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $order['progress']; ?>%">
                                <?php echo $order['progress']; ?>%
                            </div>
                        </div>
                        
                        <h5 class="text-white-50 mb-1 small text-uppercase">Oluşturulma Tarihi</h5>
                        <p class="mb-3 text-white"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                        
                        <?php if ($order['completed_at']): ?>
                            <h5 class="text-white-50 mb-1 small text-uppercase">Tamamlanma Tarihi</h5>
                            <p class="mb-3 text-white"><?php echo date('d.m.Y H:i', strtotime($order['completed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($order['notes']): ?>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-muted mb-1">Notlar</h5>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sipariş Mesajları Kartı -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Sipariş Mesajları</h6>
            </div>
            <div class="card-body bg-dark" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-<?php echo $message['role'] === 'admin' ? 'danger' : 'primary'; ?> rounded-circle">
                                    <span class="avatar-text"><?php echo strtoupper(substr($message['username'], 0, 1)); ?></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fw-bold text-white me-2"><?php echo htmlspecialchars($message['username']); ?></span>
                                    <small class="text-white-50"><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                                <div class="p-3 rounded" style="background: rgba(255,255,255,0.1);">
                                    <p class="text-white mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="text-white-50">Henüz mesaj bulunmuyor.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Durum Güncelleme Kartı -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Durum Güncelle</h6>
            </div>
            <div class="card-body bg-dark">
                <form id="statusForm" onsubmit="updateStatus(event)">
                    <div class="mb-3">
                        <label for="status" class="form-label text-white-50">Sipariş Durumu</label>
                        <select class="form-select bg-darker text-white border-secondary" id="status" name="status">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                            <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>Devam Ediyor</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Güncelle
                    </button>
                </form>
            </div>
        </div>
        
        <!-- İlerleme Güncelleme Kartı -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">İlerleme Güncelle</h6>
            </div>
            <div class="card-body bg-dark">
                <form id="progressForm" onsubmit="updateProgress(event)">
                    <div class="mb-3">
                        <label for="progress" class="form-label text-white-50">İlerleme Yüzdesi</label>
                        <input type="number" class="form-control bg-darker text-white border-secondary" id="progress" name="progress" min="0" max="100" value="<?php echo $order['progress']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Güncelle
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Booster Atama Kartı -->
        <?php if (!$order['booster_id']): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Booster Ata</h6>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="booster_id" class="form-label">Booster Seç</label>
                        <select class="form-select" id="booster_id" name="booster_id" required>
                            <option value="">Booster Seçin</option>
                            <?php
                            // Boosterları getir
                            try {
                                $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'booster' AND status = 'active' ORDER BY username ASC");
                                $stmt->execute();
                                $boosters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($boosters as $booster) {
                                    echo '<option value="' . $booster['id'] . '">' . htmlspecialchars($booster['username']) . '</option>';
                                }
                            } catch(PDOException $e) {
                                // Hata durumunda boş liste
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_booster" class="btn btn-primary w-100">Booster Ata</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Avatar stilleri */
.avatar {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-sm {
    width: 24px;
    height: 24px;
}

.avatar-text {
    color: white;
    font-size: 14px;
    font-weight: bold;
}

/* Kart stilleri */
.card {
    border: 1px solid rgba(255,255,255,0.1);
}

.card-header {
    background: rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.card-body {
    background: var(--darker-bg);
}

/* Progress bar stilleri */
.progress {
    background: rgba(255,255,255,0.1);
}

/* Metin stilleri */
.text-white-50 {
    color: rgba(255,255,255,0.5) !important;
}

/* Badge stilleri */
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

/* Form elemanları */
.form-select {
    background-color: #1a1f2d !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: #fff !important;
}

.form-select option {
    background-color: #1a1f2d;
    color: #fff;
}

/* Durum güncelleme kartı */
#statusForm .form-label,
#progressForm .form-label {
    color: rgba(255,255,255,0.7) !important;
}

/* Booster atama kartı */
#booster_id {
    background-color: #1a1f2d;
    border-color: rgba(255,255,255,0.1);
    color: #fff;
}
</style>

<script>
// Durum fonksiyonları
function getStatusClass(status) {
    switch(status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusText(status) {
    switch(status) {
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

// Güncelleme fonksiyonları
function updateStatus(e) {
    e.preventDefault();
    
    const status = document.getElementById('status').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Butonu devre dışı bırak ve yükleniyor göster
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Güncelleniyor...';
    
    // AJAX isteği gönder
    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=<?php echo $order_id; ?>&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Status badge'i güncelle
            const statusBadge = document.querySelector('.badge[data-status]');
            statusBadge.className = `badge bg-${getStatusClass(status)} rounded-pill`;
            statusBadge.textContent = getStatusText(status);
            
            // Başarı mesajı göster
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Sipariş durumu güncellendi.',
                showConfirmButton: false,
                timer: 1500,
                background: '#1e293b',
                color: '#fff'
            }).then(() => {
                // Eğer sipariş tamamlandıysa sayfayı yenile (tamamlanma tarihi için)
                if (status === 'completed') {
                    window.location.reload();
                }
            });
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message,
            confirmButtonText: 'Tamam',
            background: '#1e293b',
            color: '#fff'
        });
    })
    .finally(() => {
        // Butonu normal haline getir
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Güncelle';
    });
}

function updateProgress(e) {
    e.preventDefault();
    
    const progress = document.getElementById('progress').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Butonu devre dışı bırak ve yükleniyor göster
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Güncelleniyor...';
    
    // AJAX isteği gönder
    fetch('update_progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=<?php echo $order_id; ?>&progress=${progress}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Progress bar'ı güncelle
            document.querySelector('.progress-bar').style.width = progress + '%';
            document.querySelector('.progress-bar').textContent = progress + '%';
            
            // Başarı mesajı göster
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'İlerleme durumu güncellendi.',
                showConfirmButton: false,
                timer: 1500,
                background: '#1e293b',
                color: '#fff'
            });
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message,
            confirmButtonText: 'Tamam',
            background: '#1e293b',
            color: '#fff'
        });
    })
    .finally(() => {
        // Butonu normal haline getir
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Güncelle';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 