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
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validasyon
if (!$order_id) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz sipariş ID']));
}

$valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz sipariş durumu']));
}

try {
    $conn->beginTransaction();

    // Mevcut siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT o.*, b.id as booster_id, b.user_id as booster_user_id
        FROM orders o
        LEFT JOIN boosters b ON o.booster_id = b.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Sipariş bulunamadı');
    }

    // Siparişi güncelle
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // Eğer sipariş tamamlandıysa ve daha önce tamamlanmadıysa
    if ($status === 'completed' && $order['status'] !== 'completed') {
        // Tamamlanma tarihini güncelle
        $stmt = $conn->prepare("UPDATE orders SET completed_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        // Booster bakiyelerini güncelle
        if ($order['booster_id']) {
            // Booster'ın bekleyen bakiyesini güncelle
            $stmt = $conn->prepare("
                UPDATE boosters 
                SET pending_balance = pending_balance + ?,
                    total_balance = total_balance + ?
                WHERE id = ?
            ");
            $stmt->execute([$order['booster_earnings'], $order['booster_earnings'], $order['booster_id']]);

            // Booster'a bildirim gönder
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message) 
                VALUES (?, ?)
            ");
            $notification_message = "Sipariş #" . $order_id . " tamamlandı. " . 
                                  number_format($order['booster_earnings'], 2, ',', '.') . 
                                  " ₺ bakiyenize eklendi.";
            $stmt->execute([$order['booster_user_id'], $notification_message]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Sipariş durumu güncellendi']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Sipariş durumu güncelleme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
} 