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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['progress'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]);
    exit;
}

$order_id = (int)$_POST['order_id'];
$progress = (int)$_POST['progress'];
$user_id = $_SESSION['user_id'];

try {
    // Siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND booster_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Sipariş bulunamadı veya güncelleme yetkiniz yok.');
    }
    
    // İlerleme değerini kontrol et
    if ($progress < 0 || $progress > 100) {
        throw new Exception('İlerleme değeri 0-100 arasında olmalıdır.');
    }
    
    // İlerlemeyi güncelle
    $stmt = $conn->prepare("
        UPDATE orders 
        SET progress = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$progress, $order_id]);
    
    // Eğer ilerleme %100 ise siparişi tamamla
    if ($progress === 100) {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'completed',
                completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        
        // Müşteriye bildirim gönder
        createNotification(
            $order['user_id'],
            "Sipariş #$order_id tamamlandı! Boost işlemi başarıyla tamamlanmıştır."
        );
    } else {
        // İlerleme güncellemesi hakkında müşteriye bildirim gönder
        createNotification(
            $order['user_id'],
            "Sipariş #$order_id için ilerleme %$progress olarak güncellendi."
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'İlerleme başarıyla güncellendi.',
        'progress' => $progress,
        'completed' => ($progress === 100)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 