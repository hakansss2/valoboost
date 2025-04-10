<?php
// Güvenli input temizleme
function clean($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kullanıcı rolü kontrolü
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

// Admin kontrolü
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Booster kontrolü
function isBooster() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'booster';
}

// Kullanıcı kontrolü
function isUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

// Yönlendirme
function redirect($url) {
    header("Location: $url");
    exit;
}

// Bildirim oluşturma
function createNotification($user_id, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':message', $message);
    return $stmt->execute();
}

// Sipariş durumu değişikliği bildirimi
function createOrderStatusNotification($user_id, $order_id, $status) {
    $message = '';
    switch ($status) {
        case 'in_progress':
            $message = "#{$order_id} numaralı siparişiniz işleme alındı. Booster'ınız çalışmaya başladı.";
            break;
        case 'completed':
            $message = "#{$order_id} numaralı siparişiniz başarıyla tamamlandı!";
            break;
        case 'cancelled':
            $message = "#{$order_id} numaralı siparişiniz iptal edildi.";
            break;
    }
    if (!empty($message)) {
        createNotification($user_id, $message);
    }
}

// Ödeme durumu değişikliği bildirimi
function createPaymentStatusNotification($user_id, $payment_id, $status, $amount) {
    $message = '';
    switch ($status) {
        case 'completed':
            $message = "#{$payment_id} numaralı " . formatMoney($amount) . " tutarındaki ödemeniz onaylandı.";
            break;
        case 'failed':
            $message = "#{$payment_id} numaralı " . formatMoney($amount) . " tutarındaki ödemeniz başarısız oldu.";
            break;
        case 'refunded':
            $message = "#{$payment_id} numaralı " . formatMoney($amount) . " tutarındaki ödemeniz iade edildi.";
            break;
    }
    if (!empty($message)) {
        createNotification($user_id, $message);
    }
}

// Destek talebi bildirimi
function createSupportTicketNotification($user_id, $ticket_id, $type, $message = '') {
    switch ($type) {
        case 'reply':
            $notification = "#{$ticket_id} numaralı destek talebinize yeni bir yanıt var.";
            break;
        case 'status':
            $notification = "#{$ticket_id} numaralı destek talebiniz kapatıldı.";
            break;
        case 'custom':
            $notification = $message;
            break;
    }
    if (!empty($notification)) {
        createNotification($user_id, $notification);
    }
}

// Bakiye değişikliği bildirimi
function createBalanceNotification($user_id, $amount, $type, $description = '') {
    $formatted_amount = formatMoney(abs($amount));
    $message = '';
    
    if ($type === 'add') {
        $message = "Hesabınıza {$formatted_amount} tutarında bakiye eklendi.";
    } elseif ($type === 'subtract') {
        $message = "Hesabınızdan {$formatted_amount} tutarında bakiye düşüldü.";
    }
    
    if (!empty($description)) {
        $message .= " ({$description})";
    }
    
    if (!empty($message)) {
        createNotification($user_id, $message);
    }
}

// Para formatı
function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' ₺';
}

// Tarih formatı
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

// Sipariş durumu metni
function getOrderStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Beklemede';
        case 'in_progress':
            return 'Devam Ediyor';
        case 'completed':
            return 'Tamamlandı';
        case 'cancelled':
            return 'İptal Edildi';
        default:
            return 'Bilinmiyor';
    }
}

// Sipariş durumu rengi
function getOrderStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Kullanıcı bilgilerini getir
function getUserById($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Rank bilgilerini getir
function getRankById($rank_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM ranks WHERE id = :id");
    $stmt->bindParam(':id', $rank_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Oyun bilgilerini getir
function getGameById($game_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = :id");
    $stmt->bindParam(':id', $game_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Sipariş bilgilerini getir
function getOrderById($order_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->bindParam(':id', $order_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fiyat hesaplama
function calculatePrice($current_rank_id, $target_rank_id) {
    global $conn;
    
    // Rankları al
    $stmt = $conn->prepare("SELECT value FROM ranks WHERE id = :id");
    
    $stmt->bindParam(':id', $current_rank_id);
    $stmt->execute();
    $current_rank = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->bindParam(':id', $target_rank_id);
    $stmt->execute();
    $target_rank = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_rank || !$target_rank) {
        return 0;
    }
    
    // Rank farkını hesapla
    $rank_difference = $target_rank['value'] - $current_rank['value'];
    
    if ($rank_difference <= 0) {
        return 0;
    }
    
    // Her rank için 20₺ olarak hesapla (bu değer ayarlardan alınabilir)
    return $rank_difference * 20;
}

// Site ayarlarını getir
function getSetting($key) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return null;
    }
}

// Base URL'i getir
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $path = str_replace('\\', '/', $path);
    $path = $path !== '/' ? rtrim($path, '/') . '/' : '/';
    return $protocol . $host . $path;
}

// Güvenli input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Rastgele string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Dosya yükleme fonksiyonu
 * @param array $file $_FILES dizisinden gelen dosya bilgisi
 * @param string $upload_dir Yükleme yapılacak dizin
 * @return string|false Başarılı ise dosya yolu, başarısız ise false
 */
function uploadFile($file, $upload_dir) {
    // İzin verilen dosya türleri
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico'
    ];
    
    // Dosya türü kontrolü
    if (!isset($allowed_types[$file['type']])) {
        return false;
    }
    
    // Yükleme dizini kontrolü
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Benzersiz dosya adı oluştur
    $extension = $allowed_types[$file['type']];
    $filename = uniqid('upload_') . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Dosyayı yükle
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return str_replace('../', '', $filepath);
    }
    
    return false;
}

// Bildirim gönder
function sendNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $title, $message, $type]);
    } catch (PDOException $e) {
        return false;
    }
}

// Okunmamış bildirim sayısı
function getUnreadNotificationCount($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Giriş yapmış kullanıcının tipini döndürür
 * @return string Kullanıcı tipi ('admin', 'booster', 'user')
 */
function getUserType() {
    if (!isLoggedIn()) {
        return 'guest';
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['user_type'] ?? 'user';
    } catch(PDOException $e) {
        return 'user';
    }
}
?> 