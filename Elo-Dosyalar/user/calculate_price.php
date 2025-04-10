<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Parametreleri al
$current_rank_id = isset($_GET['current_rank_id']) ? (int)$_GET['current_rank_id'] : 0;
$target_rank_id = isset($_GET['target_rank_id']) ? (int)$_GET['target_rank_id'] : 0;

if (!$current_rank_id || !$target_rank_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    // Fiyatı hesapla
    $price = calculatePrice($current_rank_id, $target_rank_id);
    
    // JSON olarak döndür
    header('Content-Type: application/json');
    echo json_encode(['price' => $price]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 