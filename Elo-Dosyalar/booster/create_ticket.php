<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Booster kontrolü
if (!isBooster()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

if (empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Konu ve mesaj alanları zorunludur.']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Destek talebini oluştur
    $stmt = $conn->prepare("
        INSERT INTO support_tickets (user_id, subject, status, created_at, updated_at)
        VALUES (?, ?, 'open', NOW(), NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $subject]);
    $ticket_id = $conn->lastInsertId();
    
    // İlk mesajı ekle
    $stmt = $conn->prepare("
        INSERT INTO support_messages (ticket_id, user_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Destek talebiniz başarıyla oluşturuldu.',
        'ticket_id' => $ticket_id
    ]);
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Destek talebi oluşturma hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 