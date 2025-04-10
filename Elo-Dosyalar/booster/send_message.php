<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Booster kontrolü
if (!isBooster()) {
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim.'
    ]);
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['message']) || empty($_POST['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]);
    exit;
}

$order_id = (int)$_POST['order_id'];
$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

try {
    // Siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND booster_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Sipariş bulunamadı veya bu siparişe mesaj gönderme yetkiniz yok.');
    }
    
    // Mesajı kaydet
    $stmt = $conn->prepare("
        INSERT INTO order_messages (order_id, user_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$order_id, $user_id, $message]);
    
    // Müşteriye bildirim gönder
    createNotification(
        $order['user_id'],
        "Sipariş #$order_id için booster'dan yeni bir mesajınız var."
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesaj başarıyla gönderildi.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 