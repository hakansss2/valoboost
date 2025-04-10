<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Ödeme bilgilerini al
$merchant_oid = isset($_POST['merchant_oid']) ? $_POST['merchant_oid'] : (isset($_GET['merchant_oid']) ? $_GET['merchant_oid'] : 0);
$status = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');
$failed_reason_code = isset($_POST['failed_reason_code']) ? $_POST['failed_reason_code'] : (isset($_GET['failed_reason_code']) ? $_GET['failed_reason_code'] : '');
$failed_reason_msg = isset($_POST['failed_reason_msg']) ? $_POST['failed_reason_msg'] : (isset($_GET['failed_reason_msg']) ? $_GET['failed_reason_msg'] : 'Bilinmeyen hata');

// Ödeme ID'si kontrolü
if (!$merchant_oid) {
    $_SESSION['error'] = "Geçersiz ödeme bilgisi.";
    redirect('deposit.php');
}

// Ödeme durumunu güncelle
try {
    // Önce ödemenin kullanıcıya ait olup olmadığını kontrol et
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
    $stmt->execute([$merchant_oid, $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        $_SESSION['error'] = "Geçersiz ödeme bilgisi.";
        redirect('deposit.php');
    }
    
    // Ödeme durumunu güncelle
    $stmt = $conn->prepare("UPDATE payments SET status = 'failed', updated_at = NOW(), description = ? WHERE id = ?");
    $stmt->execute([$failed_reason_msg, $merchant_oid]);
    
    // Bildirim oluştur
    createNotification($_SESSION['user_id'], 'Ödeme Başarısız', 'Kredi kartı ile yapmaya çalıştığınız ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarındaki ödeme işlemi başarısız oldu. Sebep: ' . $failed_reason_msg);
    
} catch(PDOException $e) {
    error_log("Ödeme güncelleme hatası: " . $e->getMessage());
    // Hata olsa bile sayfayı göster
}

// Sayfa başlığı
$page_title = 'Ödeme Başarısız';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger fa-5x"></i>
                    </div>
                    <h2 class="mb-3">Ödeme Başarısız</h2>
                    <p class="lead mb-4">Ödeme işleminiz sırasında bir sorun oluştu.</p>
                    
                    <div class="alert alert-danger mb-4">
                        <h5 class="alert-heading">Hata Nedeni:</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($failed_reason_msg); ?></p>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 text-md-end text-start">
                                    <p class="mb-2"><strong>Sipariş No:</strong></p>
                                </div>
                                <div class="col-md-6 text-md-start text-start">
                                    <p class="mb-2">#<?php echo htmlspecialchars($merchant_oid); ?></p>
                                </div>
                                
                                <div class="col-md-6 text-md-end text-start">
                                    <p class="mb-2"><strong>Tutar:</strong></p>
                                </div>
                                <div class="col-md-6 text-md-start text-start">
                                    <p class="mb-2"><?php echo number_format($payment['amount'], 2, ',', '.'); ?> ₺</p>
                                </div>
                                
                                <div class="col-md-6 text-md-end text-start">
                                    <p class="mb-2"><strong>Tarih:</strong></p>
                                </div>
                                <div class="col-md-6 text-md-start text-start">
                                    <p class="mb-2"><?php echo date('d.m.Y H:i'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 col-md-6 mx-auto">
                        <a href="deposit.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-redo me-2"></i>
                            Tekrar Dene
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>
                            Ana Sayfaya Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 