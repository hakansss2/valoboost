<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header ayarla
header('Content-Type: application/json');

// Kullanıcı kontrolü
if (!isUser()) {
    echo json_encode([
        'success' => false,
        'message' => 'Bu işlem için yetkiniz yok.'
    ]);
    exit;
}

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek yöntemi.'
    ]);
    exit;
}

// Gerekli alanların kontrolü
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message) || $order_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Gerekli alanlar boş bırakılamaz.'
    ]);
    exit;
}

try {
    // Siparişin varlığını ve kullanıcıya ait olduğunu kontrol et
    $stmt = $conn->prepare("
        SELECT o.*, u.id as booster_id 
        FROM orders o 
        LEFT JOIN users u ON o.booster_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Sipariş bulunamadı veya bu siparişe mesaj gönderme yetkiniz yok.'
        ]);
        exit;
    }

    // Mesajı veritabanına kaydet
    $stmt = $conn->prepare("
        INSERT INTO order_messages (order_id, user_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$order_id, $_SESSION['user_id'], $message]);

    // Booster'a bildirim gönder
    if ($order['booster_id']) {
        $notification_message = "Sipariş #" . $order_id . " için müşteriden yeni bir mesaj var.";
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$order['booster_id'], $notification_message]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Mesajınız başarıyla gönderildi.'
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Mesaj gönderilirken bir hata oluştu.'
    ]);
} 