<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/paytr_config.php';

// POST verilerini al
$merchant_oid = isset($_POST['merchant_oid']) ? $_POST['merchant_oid'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$total_amount = isset($_POST['total_amount']) ? $_POST['total_amount'] : 0;
$hash = isset($_POST['hash']) ? $_POST['hash'] : '';

// Hash doğrulama
$hash_str = $merchant_oid . PAYTR_MERCHANT_SALT . $status . $total_amount;
$hash_check = base64_encode(hash_hmac('sha256', $hash_str, PAYTR_MERCHANT_KEY, true));

// Hash doğrulama başarısızsa işlemi sonlandır
if ($hash != $hash_check) {
    echo "PAYTR notification failed: bad hash";
    exit;
}

// Ödeme ID'si kontrolü
if (!$merchant_oid) {
    echo "PAYTR notification failed: no merchant_oid";
    exit;
}

// Ödeme bilgilerini getir
try {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$merchant_oid]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo "PAYTR notification failed: payment not found";
        exit;
    }
    
    // Ödeme zaten tamamlanmış mı kontrol et
    if ($payment['status'] === 'completed') {
        echo "OK";
        exit;
    }
    
    // Ödeme durumunu güncelle
    if ($status === 'success') {
        // Ödeme başarılı
        $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$merchant_oid]);
        
        // Kullanıcı bakiyesini güncelle
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$payment['amount'], $payment['user_id']]);
        
        // Bildirim oluştur
        createNotification($payment['user_id'], 'Ödeme Başarılı', 'Kredi kartı ile yaptığınız ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarındaki ödeme başarıyla tamamlandı ve bakiyenize eklendi.');
        
        // Admin için bildirim oluştur
        $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $admin_id) {
            createNotification($admin_id, 'Yeni ödeme alındı', 'Kullanıcı #' . $payment['user_id'] . ' tarafından ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarında kredi kartı ödemesi alındı.');
        }
        
        echo "OK";
    } else {
        // Ödeme başarısız
        $failed_reason_msg = isset($_POST['failed_reason_msg']) ? $_POST['failed_reason_msg'] : 'Bilinmeyen hata';
        
        $stmt = $conn->prepare("UPDATE payments SET status = 'failed', updated_at = NOW(), description = ? WHERE id = ?");
        $stmt->execute([$failed_reason_msg, $merchant_oid]);
        
        // Bildirim oluştur
        createNotification($payment['user_id'], 'Ödeme Başarısız', 'Kredi kartı ile yapmaya çalıştığınız ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ tutarındaki ödeme işlemi başarısız oldu. Sebep: ' . $failed_reason_msg);
        
        echo "OK";
    }
} catch(PDOException $e) {
    error_log("PayTR callback error: " . $e->getMessage());
    echo "PAYTR notification failed: database error";
    exit;
} 