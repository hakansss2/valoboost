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

// JSON verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$payment_id = $input['payment_id'] ?? 0;
$booster_id = $input['booster_id'] ?? 0;

if (!$payment_id || !$booster_id) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz ödeme bilgileri']));
}

try {
    // Ödeme bilgilerini kontrol et
    $stmt = $conn->prepare("
        SELECT bp.*, b.user_id, u.username
        FROM booster_payments bp
        JOIN boosters b ON bp.booster_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE bp.id = ? AND bp.booster_id = ? AND bp.status = 'pending'
    ");
    $stmt->execute([$payment_id, $booster_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Ödeme bulunamadı veya daha önce onaylanmış');
    }

    // Transaction başlat
    $conn->beginTransaction();

    // Ödemeyi tamamlandı olarak işaretle
    $stmt = $conn->prepare("
        UPDATE booster_payments 
        SET status = 'completed',
            payment_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment_id]);

    // Booster'ın pending_balance ve withdrawn_balance değerlerini güncelle
    $stmt = $conn->prepare("
        UPDATE boosters 
        SET pending_balance = pending_balance - ?,
            withdrawn_balance = withdrawn_balance + ?
        WHERE id = ?
    ");
    $stmt->execute([$payment['amount'], $payment['amount'], $booster_id]);

    // Kullanıcının bakiyesini güncelle
    $stmt = $conn->prepare("
        UPDATE balances 
        SET balance = balance + ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$payment['amount'], $payment['user_id']]);

    // Bildirim ekle
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message) 
        VALUES (?, ?)
    ");
    $notification_message = number_format($payment['amount'], 2, ',', '.') . " ₺ tutarındaki ödemeniz bakiyenize aktarıldı.";
    $stmt->execute([$payment['user_id'], $notification_message]);

    // Transaction'ı tamamla
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $payment['username'] . ' için ' . number_format($payment['amount'], 2, ',', '.') . ' ₺ ödeme onaylandı'
    ]);

} catch (Exception $e) {
    // Hata durumunda transaction'ı geri al
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Ödeme onaylama hatası: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ödeme onaylanırken bir hata oluştu: ' . $e->getMessage()
    ]);
} 