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

// GET parametrelerini kontrol et
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$last_time = isset($_GET['last_time']) ? (int)$_GET['last_time'] : 0;

if ($order_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz sipariş ID.'
    ]);
    exit;
}

try {
    // Siparişin varlığını ve kullanıcıya ait olduğunu kontrol et
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Sipariş bulunamadı veya bu siparişe erişim yetkiniz yok.'
        ]);
        exit;
    }

    // Yeni mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM order_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.order_id = ? AND UNIX_TIMESTAMP(m.created_at) > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$order_id, $last_time]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Mesajlar kontrol edilirken bir hata oluştu.'
    ]);
} 