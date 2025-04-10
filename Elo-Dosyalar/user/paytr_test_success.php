<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Sayfa başlığı
$page_title = 'Ödeme Başarılı';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <i class="fas fa-check-circle fa-4x"></i>
                        </div>
                    </div>
                    <h2 class="mb-3">Test Ödemesi Başarılı!</h2>
                    <p class="text-muted mb-4">Test ödemesi başarıyla tamamlandı.</p>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Test Bilgileri</h5>
                        <p>Bu sayfa PayTR test ödemesi başarılı olduğunda gösterilir.</p>
                        <p>GET Parametreleri:</p>
                        <pre class="bg-light p-3"><?php echo print_r($_GET, true); ?></pre>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="paytr_test.php" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-redo me-2"></i> Tekrar Test Et
                        </a>
                        <a href="deposit.php" class="btn btn-outline-secondary btn-lg px-5">
                            <i class="fas fa-arrow-left me-2"></i> Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 