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

// GET verilerini kontrol et
if (empty($_GET['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]);
    exit;
}

$order_id = (int)$_GET['order_id'];
$last_time = isset($_GET['last_time']) ? $_GET['last_time'] : 0;

try {
    // Siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND booster_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Sipariş bulunamadı veya bu siparişe erişim yetkiniz yok.');
    }
    
    // Yeni mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM order_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.order_id = ? AND m.created_at > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$order_id, date('Y-m-d H:i:s', $last_time)]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 