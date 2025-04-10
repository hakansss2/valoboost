<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: games.php');
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $game_id = (int)$_POST['game_id'];
    $current_rank_id = (int)$_POST['current_rank_id'];
    $target_rank_id = (int)$_POST['target_rank_id'];
    $notes = trim($_POST['notes'] ?? '');

    // Rank bilgilerini kontrol et
    $stmt = $conn->prepare("
        SELECT r1.value as current_value, r2.value as target_value
        FROM ranks r1, ranks r2
        WHERE r1.id = ? AND r2.id = ? AND r1.game_id = ? AND r2.game_id = ?
    ");
    $stmt->execute([$current_rank_id, $target_rank_id, $game_id, $game_id]);
    $ranks = $stmt->fetch();
    
    if (!$ranks) {
        throw new Exception('Geçersiz rank seçimi.');
    }
    
    // Hedef rank kontrolü
    if ($ranks['current_value'] >= $ranks['target_value']) {
        throw new Exception('Hedef rank mevcut ranktan yüksek olmalıdır.');
    }
    
    // Fiyat hesapla
    $stmt = $conn->prepare("
        SELECT price
        FROM boost_prices 
        WHERE game_id = ? AND current_rank_id = ? AND target_rank_id = ?
    ");
    $stmt->execute([$game_id, $current_rank_id, $target_rank_id]);
    $price = $stmt->fetchColumn();
    
    if (!$price) {
        throw new Exception('Bu rank kombinasyonu için fiyat bulunamadı.');
    }
    
    // Kullanıcı bakiyesini kontrol et
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $balance = (float)$stmt->fetchColumn();
    
    if ($balance < $price) {
        throw new Exception('Yetersiz bakiye. Lütfen bakiye yükleyin.');
    }
    
    // İşlemi başlat
    $conn->beginTransaction();
    
    // Siparişi kaydet
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, game_id, current_rank_id, target_rank_id,
            price, status, notes, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 'pending', ?, NOW()
        )
    ");
    $stmt->execute([
        $user_id, $game_id, $current_rank_id, $target_rank_id,
        $price, $notes
    ]);
    $order_id = $conn->lastInsertId();
    
    // Bakiyeyi güncelle
    $stmt = $conn->prepare("
        UPDATE users 
        SET balance = balance - ? 
        WHERE id = ?
    ");
    $stmt->execute([$price, $user_id]);
    
    // İşlemi tamamla
    $conn->commit();
    
    // Başarılı mesajını session'a kaydet
    $_SESSION['success_message'] = 'Siparişiniz başarıyla oluşturuldu!';
    $_SESSION['order_id'] = $order_id;
    
    // Başarılı sayfasına yönlendir
    header('Location: order_success.php');
    exit;
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?> 