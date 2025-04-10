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

// Talep ID kontrolü
$ticket_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz talep ID.']);
    exit;
}

try {
    // Talebin boostera ait olduğunu kontrol et
    $stmt = $conn->prepare("
        SELECT id 
        FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu destek talebine erişim yetkiniz yok.']);
        exit;
    }
    
    // Mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, 
               u.username,
               u.role = 'admin' as is_admin,
               DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') as created_at
        FROM support_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Okunmamış mesajları okundu olarak işaretle
    $stmt = $conn->prepare("
        UPDATE support_messages
        SET is_read = 1
        WHERE ticket_id = ? AND user_id != ? AND is_read = 0
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (PDOException $e) {
    error_log("Destek mesajları getirme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 