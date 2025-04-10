<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Kullanıcı bilgilerini getir
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kullanıcının sipariş istatistiklerini getir
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_orders,
            SUM(CASE WHEN status != 'cancelled' THEN price ELSE 0 END) as total_spent
        FROM orders 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kullanıcının son işlemlerini getir
    $stmt = $conn->prepare("
        SELECT 'order' as type, id, created_at, price as amount, status, NULL as description
        FROM orders
        WHERE user_id = ?
        UNION ALL
        SELECT 'payment' as type, id, created_at, amount, status, payment_method as description
        FROM payments
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Kullanıcı bilgileri getirilirken bir hata oluştu.";
    redirect('index.php');
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $errors = [];
        
        // Profil güncelleme
        if ($_POST['action'] === 'update_profile') {
            $username = isset($_POST['username']) ? clean($_POST['username']) : '';
            $email = isset($_POST['email']) ? clean($_POST['email']) : '';
            $discord = isset($_POST['discord']) ? clean($_POST['discord']) : '';
            
            // Validasyon
            if (empty($username)) {
                $errors[] = "Kullanıcı adı gereklidir.";
            }
            
            if (empty($email)) {
                $errors[] = "E-posta adresi gereklidir.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Geçerli bir e-posta adresi girin.";
            }
            
            // Kullanıcı adı ve e-posta benzersiz olmalı
            if ($username !== $user['username']) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $_SESSION['user_id']]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Bu kullanıcı adı zaten kullanılıyor.";
                }
            }
            
            if ($email !== $user['email']) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
                }
            }
            
            // Hata yoksa güncelle
            if (empty($errors)) {
                try {
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, discord = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $discord, $_SESSION['user_id']]);
                    
                    $_SESSION['username'] = $username;
                    $_SESSION['message'] = "Profil bilgileriniz başarıyla güncellendi.";
                    $_SESSION['message_type'] = "success";
                    
                    // Güncel kullanıcı bilgilerini getir
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch(PDOException $e) {
                    $errors[] = "Profil güncellenirken bir hata oluştu.";
                }
            }
        }
        
        // Şifre değiştirme
        if ($_POST['action'] === 'change_password') {
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Validasyon
            if (empty($current_password)) {
                $errors[] = "Mevcut şifre gereklidir.";
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = "Mevcut şifre yanlış.";
            }
            
            if (empty($new_password)) {
                $errors[] = "Yeni şifre gereklidir.";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "Şifreniz en az 6 karakter olmalıdır.";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "Şifreler eşleşmiyor.";
            }
            
            // Hata yoksa güncelle
            if (empty($errors)) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET password = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    $_SESSION['message'] = "Şifreniz başarıyla değiştirildi.";
                    $_SESSION['message_type'] = "success";
                } catch(PDOException $e) {
                    $errors[] = "Şifre değiştirilirken bir hata oluştu.";
                }
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Sol Sütun - Profil Bilgileri -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                <i class="fas fa-user me-2"></i>Profil Bilgileri
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                                <i class="fas fa-key me-2"></i>Şifre Değiştir
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profil Bilgileri Sekmesi -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <?php if (!empty($errors) && $_POST['action'] === 'update_profile'): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <label for="username" class="col-sm-3 col-form-label">Kullanıcı Adı</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="email" class="col-sm-3 col-form-label">E-posta Adresi</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="discord" class="col-sm-3 col-form-label">Discord</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="discord" name="discord" value="<?php echo htmlspecialchars($user['discord'] ?? ''); ?>" placeholder="Örn: kullanıcı#1234">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="created_at" class="col-sm-3 col-form-label">Kayıt Tarihi</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control-plaintext" id="created_at" value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Şifre Değiştir Sekmesi -->
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                
                                <?php if (!empty($errors) && $_POST['action'] === 'change_password'): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <label for="current_password" class="col-sm-3 col-form-label">Mevcut Şifre</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="new_password" class="col-sm-3 col-form-label">Yeni Şifre</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Şifreniz en az 6 karakter olmalıdır.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="confirm_password" class="col-sm-3 col-form-label">Şifre Tekrar</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>Şifreyi Değiştir
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Sütun - Kullanıcı Özeti ve İstatistikler -->
        <div class="col-lg-4">
            <!-- Kullanıcı Özeti -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="avatar-circle mb-3">
                        <span class="avatar-text"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (!empty($user['discord'])): ?>
                        <p class="text-muted mb-3">
                            <i class="fab fa-discord me-1"></i> <?php echo htmlspecialchars($user['discord']); ?>
                        </p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center">
                        <div class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?> px-3 py-2">
                            <?php echo $user['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Bakiye</span>
                            <h5 class="mb-0"><?php echo number_format($user['balance'], 2, ',', '.'); ?> ₺</h5>
                        </div>
                        <a href="deposit.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-1"></i> Bakiye Yükle
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- İstatistikler -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>İstatistikler</h6>
                </div>
                <div class="card-body">
                    <div class="row g-0">
                        <div class="col-6 border-end border-bottom p-3 text-center">
                            <h3 class="mb-1"><?php echo $stats['total_orders'] ?? 0; ?></h3>
                            <div class="text-muted small">Toplam Sipariş</div>
                        </div>
                        <div class="col-6 border-bottom p-3 text-center">
                            <h3 class="mb-1"><?php echo $stats['completed_orders'] ?? 0; ?></h3>
                            <div class="text-muted small">Tamamlanan</div>
                        </div>
                        <div class="col-6 border-end p-3 text-center">
                            <h3 class="mb-1"><?php echo $stats['active_orders'] ?? 0; ?></h3>
                            <div class="text-muted small">Aktif Sipariş</div>
                        </div>
                        <div class="col-6 p-3 text-center">
                            <h3 class="mb-1"><?php echo number_format($stats['total_spent'] ?? 0, 0, ',', '.'); ?> ₺</h3>
                            <div class="text-muted small">Toplam Harcama</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Aktiviteler -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Son Aktiviteler</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">Henüz aktivite bulunmuyor.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_activities as $activity): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($activity['type'] === 'order'): ?>
                                                <span class="badge bg-primary me-2">Sipariş</span>
                                            <?php else: ?>
                                                <span class="badge bg-success me-2">Ödeme</span>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                #<?php echo $activity['id']; ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div>
                                            <?php if ($activity['type'] === 'order'): ?>
                                                <span class="badge bg-<?php echo getOrderStatusColor($activity['status']); ?>">
                                                    <?php echo getOrderStatusText($activity['status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <small>
                                                    <?php echo htmlspecialchars($activity['description'] ?? ''); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <strong><?php echo number_format($activity['amount'], 2, ',', '.'); ?> ₺</strong>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #4e73df;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
}

.avatar-text {
    font-size: 32px;
    color: white;
    font-weight: bold;
}

.nav-tabs .nav-link {
    color: #5a5c69;
    border: none;
    padding: 0.75rem 1rem;
}

.nav-tabs .nav-link.active {
    color: #4e73df;
    border-bottom: 2px solid #4e73df;
    background-color: transparent;
}

.card {
    border: none;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}
</style>

<?php require_once 'includes/footer.php'; ?> 