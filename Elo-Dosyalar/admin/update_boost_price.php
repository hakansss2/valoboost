<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
$current_rank_id = isset($_POST['current_rank_id']) ? (int)$_POST['current_rank_id'] : 0;
$target_rank_id = isset($_POST['target_rank_id']) ? (int)$_POST['target_rank_id'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;

if (!$game_id || !$current_rank_id || !$target_rank_id || $price < 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veriler']);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO boost_prices (game_id, current_rank_id, target_rank_id, price)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE price = ?
    ");
    $stmt->execute([$game_id, $current_rank_id, $target_rank_id, $price, $price]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}