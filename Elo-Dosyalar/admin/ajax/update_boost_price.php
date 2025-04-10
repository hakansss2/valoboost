<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Yönetici kontrolü
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// POST verilerini al
$game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
$current_rank_id = isset($_POST['current_rank_id']) ? (int)$_POST['current_rank_id'] : 0;
$target_rank_id = isset($_POST['target_rank_id']) ? (int)$_POST['target_rank_id'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;

// Veri kontrolü
if (!$game_id || !$current_rank_id || !$target_rank_id || $price < 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veriler']);
    exit;
}

try {
    // Önce bu fiyatın daha önce kaydedilip kaydedilmediğini kontrol et
    $check = $conn->prepare("
        SELECT id 
        FROM boost_prices 
        WHERE game_id = ? 
        AND current_rank_id = ? 
        AND target_rank_id = ?
    ");
    $check->execute([$game_id, $current_rank_id, $target_rank_id]);
    
    if ($check->fetch()) {
        // Güncelleme yap
        $stmt = $conn->prepare("
            UPDATE boost_prices 
            SET price = ? 
            WHERE game_id = ? 
            AND current_rank_id = ? 
            AND target_rank_id = ?
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
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
} 