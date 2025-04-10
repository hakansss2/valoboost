<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_POST['booster_id']) || !isset($_POST['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

$booster_id = (int)$_POST['booster_id'];
$amount = (float)$_POST['amount'];
$notes = $_POST['notes'] ?? null;

try {
    // Booster'ı kontrol et
    $stmt = $conn->prepare("
        SELECT b.*, u.username, u.id as user_id
        FROM boosters b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booster_id]);
    $booster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booster) {
        echo json_encode(['success' => false, 'message' => 'Booster bulunamadı']);
        exit;
    }

    // Bekleyen bakiyeyi kontrol et
    if ($amount > $booster['pending_balance']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ödeme tutarı bekleyen bakiyeden fazla olamaz'
        ]);
        exit;
    }

    // Transaction başlat
    $conn->beginTransaction();

    // Ödeme kaydı oluştur
    $stmt = $conn->prepare("
        INSERT INTO booster_payments (
            booster_id, amount, status, payment_date, notes
        ) VALUES (?, ?, 'completed', NOW(), ?)
    ");
    $stmt->execute([$booster_id, $amount, $notes]);

    // Booster bakiyelerini güncelle
    $stmt = $conn->prepare("
        UPDATE boosters 
        SET pending_balance = pending_balance - ?,
            withdrawn_balance = withdrawn_balance + ?,
            last_payment_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$amount, $amount, $booster_id]);

    // Booster'ın bakiyesini güncelle
    $stmt = $conn->prepare("
        UPDATE balances 
        SET balance = balance + ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$amount, $booster['user_id']]);

    // Bildirim ekle
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message) 
        VALUES (?, ?)
    ");
    $notification_message = number_format($amount, 2, ',', '.') . " ₺ tutarındaki ödemeniz bakiyenize aktarıldı.";
    $stmt->execute([$booster['user_id'], $notification_message]);

    // Transaction'ı tamamla
    $conn->commit();

    // Başarılı yanıt döndür
    echo json_encode([
        'success' => true,
        'message' => $booster['username'] . ' için ' . number_format($amount, 2, ',', '.') . ' ₺ ödeme bakiyeye aktarıldı'
    ]);

} catch(PDOException $e) {
    // Hata durumunda transaction'ı geri al
    $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ödeme kaydedilirken bir hata oluştu'
    ]);
} 