<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// POST verilerini kontrol et
if (empty($_POST['subject']) || empty($_POST['message'])) {
    $_SESSION['error'] = 'Lütfen tüm alanları doldurun.';
    redirect('new_ticket.php');
}

$user_id = $_SESSION['user_id'];
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

try {
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, status, created_at, updated_at) VALUES (?, ?, ?, 'open', NOW(), NOW())");
    $stmt->execute([$user_id, $subject, $message]);
    
    $_SESSION['success'] = 'Destek talebiniz başarıyla oluşturuldu.';
    redirect('support.php');
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    redirect('new_ticket.php');
} 