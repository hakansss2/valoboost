<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Hata ayıklama için log dosyasına yazalım
error_log("update_ticket_status.php çağrıldı");
error_log("POST verisi: " . print_r($_POST, true));

header('Content-Type: application/json');

// Yönetici kontrolü
if (!isAdmin()) {
    error_log("Yetkisiz erişim hatası");
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Geçersiz istek yöntemi: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

// Gerekli parametreleri kontrol et
if (!isset($_POST['ticket_id']) || !isset($_POST['status'])) {
    error_log("Gerekli parametreler eksik");
    echo json_encode(['success' => false, 'message' => 'Gerekli parametreler eksik.']);
    exit;
}

$ticket_id = intval($_POST['ticket_id']);
$status = $_POST['status'];

// Debug bilgisi
error_log("Ticket ID: " . $ticket_id);
error_log("Status: " . $status);

// Durum değerini kontrol et
if (!in_array($status, ['open', 'closed'])) {
    error_log("Geçersiz durum değeri: " . $status);
    echo json_encode(['success' => false, 'message' => 'Geçersiz durum değeri.']);
    exit;
}

try {
    // Destek talebinin varlığını kontrol et
    $stmt = $conn->prepare("SELECT id, user_id FROM support_tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        error_log("Destek talebi bulunamadı: " . $ticket_id);
        echo json_encode(['success' => false, 'message' => 'Destek talebi bulunamadı.']);
        exit;
    }

    error_log("Destek talebi bulundu: " . print_r($ticket, true));

    // Destek talebinin durumunu güncelle
    $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $ticket_id]);
    
    error_log("Güncelleme sonucu: " . ($result ? "Başarılı" : "Başarısız"));
    error_log("Etkilenen satır sayısı: " . $stmt->rowCount());

    if (!$result || $stmt->rowCount() === 0) {
        error_log("Güncelleme başarısız oldu");
        echo json_encode(['success' => false, 'message' => 'Destek talebi güncellenemedi.']);
        exit;
    }

    // Durum değişikliği hakkında bir mesaj ekle
    $admin_id = $_SESSION['user_id'];
    $message = $status == 'open' ? 'Destek talebi yeniden açıldı.' : 'Destek talebi kapatıldı.';
    
    $stmt = $conn->prepare("
        INSERT INTO support_messages (ticket_id, user_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $result = $stmt->execute([$ticket_id, $admin_id, $message]);
    
    error_log("Mesaj ekleme sonucu: " . ($result ? "Başarılı" : "Başarısız"));

    // Başarılı yanıt döndür
    echo json_encode([
        'success' => true, 
        'message' => 'Destek talebi durumu güncellendi.',
        'status' => $status,
        'ticket_id' => $ticket_id
    ]);

} catch(PDOException $e) {
    error_log("PDO Hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Genel Hata: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
} 