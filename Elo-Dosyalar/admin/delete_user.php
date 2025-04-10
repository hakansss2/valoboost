<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    $_SESSION['error'] = "Yetkisiz erişim";
    redirect('index.php');
}

// ID kontrolü
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['error'] = "Geçersiz kullanıcı ID'si";
    redirect('users.php');
}

try {
    // Kullanıcıyı kontrol et
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "Kullanıcı bulunamadı";
        redirect('users.php');
    }
    
    // Admin kullanıcısını silmeye çalışıyorsa engelle
    if ($user['role'] === 'admin') {
        $_SESSION['error'] = "Admin kullanıcısı silinemez";
        redirect('users.php');
    }
    
    // İşlemi başlat
    $conn->beginTransaction();
    
    // Kullanıcının siparişlerini güncelle
    $stmt = $conn->prepare("UPDATE orders SET user_id = NULL WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Kullanıcının bildirimlerini sil
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Kullanıcının bakiyesini sil
    $stmt = $conn->prepare("DELETE FROM balances WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Kullanıcıyı sil
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
    
    // İşlemi tamamla
    $conn->commit();
    
    $_SESSION['success'] = "Kullanıcı başarıyla silindi";

} catch (PDOException $e) {
    // Hata durumunda işlemi geri al
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log('Kullanıcı silme hatası: ' . $e->getMessage());
    $_SESSION['error'] = "Kullanıcı silinirken bir hata oluştu";
}

redirect('users.php'); 