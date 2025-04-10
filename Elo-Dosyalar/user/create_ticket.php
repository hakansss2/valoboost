<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON yanıtı için header
header('Content-Type: application/json');

// Debug için gelen verileri logla
error_log("POST verisi: " . print_r($_POST, true));

// Kullanıcı kontrolü
if (!isUser()) {
    error_log("Kullanıcı oturumu bulunamadı");
    echo json_encode(['success' => false, 'message' => 'Oturum süreniz dolmuş.']);
    exit;
}

// POST verilerini kontrol et
if (empty($_POST['subject']) || empty($_POST['message'])) {
    error_log("Eksik POST verileri");
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

error_log("User ID: " . $user_id);
error_log("Subject: " . $subject);
error_log("Message: " . $message);

// Boş alan kontrolü
if (empty($subject) || empty($message)) {
    error_log("Boş alanlar var");
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, status, created_at, updated_at) VALUES (?, ?, ?, 'open', NOW(), NOW())");
    $stmt->execute([$user_id, $subject, $message]);
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen tekrar deneyin.']);
}
?> 