<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Yönetici kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST verilerini kontrol et
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;

// Validasyon
if (!$order_id) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz sipariş ID']));
}

if ($progress < 0 || $progress > 100) {
    die(json_encode(['success' => false, 'message' => 'İlerleme değeri 0-100 arasında olmalıdır']));
}

try {
    // Siparişi güncelle
    $stmt = $conn->prepare("UPDATE orders SET progress = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$progress, $order_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'İlerleme durumu güncellendi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sipariş bulunamadı veya güncelleme yapılamadı']);
    }
    
} catch (PDOException $e) {
    error_log('İlerleme güncelleme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
} 