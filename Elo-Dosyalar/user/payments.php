<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Ödeme istatistiklerini getir
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
        FROM payments 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ödemeleri getir
    $stmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Veriler yüklenirken bir hata oluştu.";
}

// Ödeme durumlarına göre renk sınıfları
function getPaymentStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'failed':
            return 'danger';
        default:
            return 'primary';
    }
}

// Ödeme durumlarına göre metin
function getPaymentStatusText($status) {
    switch ($status) {
        case 'completed':
            return 'Tamamlandı';
        case 'pending':
            return 'Beklemede';
        case 'failed':
            return 'Başarısız';
        default:
            return 'Bilinmiyor';
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Ödemelerim</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active text-muted">Ödemeler</li>
                        </ol>
                    </nav>
                </div>
                <a href="deposit.php" class="btn btn-glow btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Bakiye Yükle
                </a>
            </div>
        </div>
    </div>

    <!-- Ana Kartlar -->
    <div class="row mt-4">
        <div class="col-xxl-6">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex flex-column h-100">
                                <div class="flex-grow-1">
                                    <h3 class="fw-medium text-capitalize mt-0 mb-2 text-glow">Ödeme İşlemlerinizi Yönetin</h3>
                                    <p class="font-18 text-muted">Güvenli ve hızlı ödeme seçenekleri ile bakiye yükleyin.</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="row h-100">
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Toplam İşlem</h4>
                                                        <div class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center">
                                                            <i class="mdi mdi-credit-card"></i>
                                                        </div>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo $stats['total_payments']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Toplam Tutar</h4>
                                                        <div class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center">
                                                            <i class="mdi mdi-cash"></i>
                                                        </div>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo number_format($stats['total_amount'], 2, ',', '.'); ?> ₺</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <img src="../assets/img/characters/character3.png" alt="Payment" class="img-fluid floating-image" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Bakiye Yükle</h4>
                                    <p class="mb-2 text-muted">Hızlı ve güvenli ödeme</p>
                                    <a href="deposit.php" class="btn btn-glow btn-primary btn-sm">Yükle</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg rounded-circle bg-glow">
                                        <i class="fas fa-plus-circle fa-2x text-white mt-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Başarılı</h4>
                                    <h2 class="mb-2 text-glow"><?php echo $stats['completed_payments']; ?></h2>
                                    <p class="text-muted">Tamamlanan ödemeler</p>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg rounded-circle bg-glow">
                                        <i class="fas fa-check-circle fa-2x text-white mt-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Bekleyen</h4>
                                    <h2 class="mb-2 text-glow"><?php echo $stats['pending_payments']; ?></h2>
                                    <p class="text-muted">Bekleyen ödemeler</p>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg rounded-circle bg-glow">
                                        <i class="fas fa-clock fa-2x text-white mt-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Başarısız</h4>
                                    <h2 class="mb-2 text-glow"><?php echo $stats['failed_payments']; ?></h2>
                                    <p class="text-muted">Başarısız ödemeler</p>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg rounded-circle bg-glow">
                                        <i class="fas fa-times-circle fa-2x text-white mt-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ödeme Geçmişi -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Ödeme Geçmişi</h5>
                        <a href="deposit.php" class="btn btn-glow btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Yeni Ödeme
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-4">
                                <i class="fas fa-credit-card fa-3x text-muted"></i>
                            </div>
                            <h4 class="text-white mb-3">Henüz Ödeme İşleminiz Bulunmuyor</h4>
                            <p class="text-muted mb-4">İlk bakiye yükleme işleminizi gerçekleştirin!</p>
                            <a href="deposit.php" class="btn btn-glow btn-primary btn-lg px-5">
                                <i class="fas fa-plus me-2"></i>Bakiye Yükle
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-dark table-centered mb-0" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th class="text-white">#</th>
                                        <th class="text-white">Tutar</th>
                                        <th class="text-white">Ödeme Yöntemi</th>
                                        <th class="text-white">Durum</th>
                                        <th class="text-white">Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr class="glass-effect-light">
                                            <td class="text-white">#<?php echo $payment['id']; ?></td>
                                            <td class="text-glow"><?php echo number_format($payment['amount'], 2, ',', '.'); ?> ₺</td>
                                            <td class="text-white"><?php echo $payment['payment_method']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getPaymentStatusColor($payment['status']); ?> glow-badge">
                                                    <?php echo getPaymentStatusText($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-white"><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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

.glass-effect-light {
    background: rgba(255, 255, 255, 0.02) !important;
}

/* Hover Effect */
.hover-effect {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.hover-effect:hover {
    transform: translateY(-5px) rotateX(5deg);
    box-shadow: 0 15px 30px rgba(106, 17, 203, 0.2) !important;
}

/* Glow Effects */
.text-glow {
    color: #00f3ff;
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.glow-text {
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

.glow-badge {
    box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.4);
}

/* Background Gradients */
.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}

/* Table Styles */
.table-dark {
    background-color: transparent;
}

.table-dark thead th {
    background-color: rgba(255, 255, 255, 0.05);
    border-bottom: none;
}

.table-dark td {
    border-color: rgba(255, 255, 255, 0.05);
}

/* Floating Animation */
@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.floating-image {
    animation: floating 3s ease-in-out infinite;
}

/* Avatar Styles */
.avatar-lg {
    height: 4rem;
    width: 4rem;
}

.bg-glow {
    background-color: rgba(255, 255, 255, 0.2) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTables
    if ($.fn.DataTable) {
        $('#paymentsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
            },
            order: [[0, 'desc']]
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>