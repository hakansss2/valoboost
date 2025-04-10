<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: boost_prices.php');
    exit;
}

$game_id = (int)$_POST['game_id'];
$prices = $_POST['prices'] ?? [];

try {
    // Her bir fiyat için güncelleme yap
    foreach ($prices as $key => $price) {
        if ($price === '') continue; // Boş değerleri atla
        
        // Rank ID'lerini ayır
        list($current_rank_id, $target_rank_id) = explode('_', $key);
        $price = (float)$price;
        
        // Fiyat kaydı var mı kontrol et
        $check = $conn->prepare("
            SELECT id FROM boost_prices 
            WHERE game_id = ? AND current_rank_id = ? AND target_rank_id = ?
        ");
        $check->execute([$game_id, $current_rank_id, $target_rank_id]);
        
        if ($check->fetch()) {
            // Güncelle
            $stmt = $conn->prepare("
                UPDATE boost_prices 
                SET price = ? 
                WHERE game_id = ? AND current_rank_id = ? AND target_rank_id = ?
            ");
            $stmt->execute([$price, $game_id, $current_rank_id, $target_rank_id]);
        } else {
            // Yeni kayıt ekle
            $stmt = $conn->prepare("
                INSERT INTO boost_prices (game_id, current_rank_id, target_rank_id, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$game_id, $current_rank_id, $target_rank_id, $price]);
        }
    }
    
    $_SESSION['message'] = 'Fiyatlar başarıyla kaydedildi.';
    $_SESSION['message_type'] = 'success';
} catch (PDOException $e) {
    $_SESSION['message'] = 'Fiyatlar kaydedilirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
}

// Sayfaya geri dön
header('Location: boost_prices.php?game_id=' . $game_id);
exit; 