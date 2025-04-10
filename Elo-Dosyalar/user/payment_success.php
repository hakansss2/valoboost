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
$total_amount = isset($_POST['total_amount']) ? $_POST['total_amount'] : (isset($_GET['total_amount']) ? $_GET['total_amount'] : 0);

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
    
    // Ödeme zaten tamamlanmış mı kontrol et
    if ($payment['status'] === 'completed') {
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
                                <i class="fas fa-check-circle text-success fa-5x"></i>
                            </div>
                            <h2 class="mb-3">Ödeme Başarılı</h2>
                            <p class="lead mb-4">Ödemeniz daha önce başarıyla tamamlanmıştır.</p>
                            <div class="d-grid gap-2 col-md-6 mx-auto">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>
                                    Ana Sayfaya Dön
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        require_once 'includes/footer.php';
        exit;
    }
    
    // Ödeme durumunu güncelle
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$merchant_oid]);
    
    // Kullanıcı bakiyesini güncelle
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$payment['amount'], $_SESSION['user_id']]);
    
    // Bildirim oluştur
    createNotification($_SESSION['user_id'], 'Ödeme Başarılı', 'Kredi kartı ile yaptığınız ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarındaki ödeme başarıyla tamamlandı ve bakiyenize eklendi.');
    
    // Admin için bildirim oluştur
    $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $admin_id) {
        createNotification($admin_id, 'Yeni ödeme alındı', 'Kullanıcı #' . $_SESSION['user_id'] . ' tarafından ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarında kredi kartı ödemesi alındı.');
    }
    
} catch(PDOException $e) {
    error_log("Ödeme güncelleme hatası: " . $e->getMessage());
    // Hata olsa bile sayfayı göster
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
                        <i class="fas fa-check-circle text-success fa-5x"></i>
                    </div>
                    <h2 class="mb-3">Ödeme Başarılı</h2>
                    <p class="lead mb-4">Ödemeniz başarıyla tamamlandı ve bakiyenize eklendi.</p>
                    
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
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            Ana Sayfaya Dön
                        </a>
                        <a href="payments.php" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>
                            Ödeme Geçmişi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 