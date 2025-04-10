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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi']));
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$booster_id = isset($_POST['booster_id']) ? (int)$_POST['booster_id'] : 0;

if (!$order_id || !$booster_id) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz sipariş veya booster ID']));
}

try {
    // İşlemi başlat
    $conn->beginTransaction();

    // Siparişi kontrol et
    $stmt = $conn->prepare("
        SELECT o.*, u.id as user_id 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Sipariş bulunamadı');
    }

    if ($order['status'] !== 'pending') {
        throw new Exception('Bu sipariş için booster atanamaz. Mevcut durum: ' . $order['status']);
    }

    // Booster'ı kontrol et
    $stmt = $conn->prepare("
        SELECT id, username, status 
        FROM users 
        WHERE id = ? AND role = 'booster'
    ");
    $stmt->execute([$booster_id]);
    $booster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booster) {
        throw new Exception('Booster bulunamadı');
    }

    if ($booster['status'] !== 'active') {
        throw new Exception('Seçilen booster aktif değil');
    }

    // Siparişe booster ata
    $stmt = $conn->prepare("
        UPDATE orders 
        SET booster_id = ?,
            status = 'in_progress',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND status = 'pending'
    ");
    $result = $stmt->execute([$booster_id, $order_id]);

    if (!$result || $stmt->rowCount() === 0) {
        throw new Exception('Sipariş güncellenirken bir hata oluştu');
    }

    // Bildirimleri ekle
    // Booster'a bildirim
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message) 
        VALUES (?, ?)
    ");
    $boosterMessage = "Size yeni bir sipariş atandı. Sipariş #" . $order_id;
    $stmt->execute([$booster_id, $boosterMessage]);

    // Müşteriye bildirim
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message) 
        VALUES (?, ?)
    ");
    $userMessage = "Siparişiniz (#" . $order_id . ") booster'a atandı ve işleme alındı.";
    $stmt->execute([$order['user_id'], $userMessage]);

    // İşlemi tamamla
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booster başarıyla atandı'
    ]);

} catch (Exception $e) {
    // Hata durumunda işlemi geri al
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Booster atama hatası: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
    ]);
} 