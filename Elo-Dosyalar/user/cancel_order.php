<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    die(json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim.'
    ]));
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]));
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];

try {
    // İşlemi başlat
    $conn->beginTransaction();
    
    // Siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ? AND status = 'pending'
        FOR UPDATE
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Sipariş bulunamadı veya iptal edilemez.');
    }
    
    // Siparişi iptal et
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'cancelled',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    
    // Kullanıcıya ödemeyi iade et
    $stmt = $conn->prepare("
        UPDATE users 
        SET balance = balance + ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$order['total_price'], $user_id]);
    
    // İade işlem kaydını oluştur
    $stmt = $conn->prepare("
        INSERT INTO transactions (
            user_id, type, amount, description, created_at
        ) VALUES (
            ?, 'refund', ?, ?, NOW()
        )
    ");
    $stmt->execute([
        $user_id,
        $order['total_price'],
        "Sipariş #$order_id iptali için iade"
    ]);
    
    // İşlemi tamamla
    $conn->commit();
    
    // Başarılı yanıt döndür
    echo json_encode([
        'success' => true,
        'message' => 'Sipariş başarıyla iptal edildi ve ödeme iade edildi.'
    ]);
    
} catch (Exception $e) {
    // Hata durumunda işlemi geri al
    $conn->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?> 