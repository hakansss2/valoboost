<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['game_id']) || !isset($_GET['current_rank_id']) || !isset($_GET['target_rank_id'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

$game_id = (int)$_GET['game_id'];
$current_rank_id = (int)$_GET['current_rank_id'];
$target_rank_id = (int)$_GET['target_rank_id'];
$priority = isset($_GET['priority']) ? (int)$_GET['priority'] : 0;
$streaming = isset($_GET['streaming']) ? (int)$_GET['streaming'] : 0;

try {
    // Rank bilgilerini al
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
    
    // Fiyat bilgilerini al
    $stmt = $conn->prepare("
        SELECT price, priority_multiplier, streaming_multiplier
        FROM boost_prices 
        WHERE game_id = ? AND current_rank_id = ? AND target_rank_id = ?
    ");
    $stmt->execute([$game_id, $current_rank_id, $target_rank_id]);
    $price_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$price_data) {
        throw new Exception('Bu rank kombinasyonu için fiyat bulunamadı.');
    }
    
    $base_price = (float)$price_data['price'];
    $total_price = $base_price;
    
    // Ekstra seçenekleri hesapla
    if ($priority) {
        $total_price *= (float)$price_data['priority_multiplier'];
    }
    if ($streaming) {
        $total_price *= (float)$price_data['streaming_multiplier'];
    }
    
    // İndirim hesapla
    $original_price = null;
    $discount = null;
    $rank_difference = $ranks['target_value'] - $ranks['current_value'];
    
    if ($rank_difference >= 10) {
        $original_price = $total_price;
        $discount = '%15';
        $total_price *= 0.85;
    } elseif ($rank_difference >= 5) {
        $original_price = $total_price;
        $discount = '%10';
        $total_price *= 0.90;
    }
    
    echo json_encode([
        'success' => true,
        'price' => number_format($total_price, 2, ',', '.'),
        'original_price' => $original_price ? number_format($original_price, 2, ',', '.') : null,
        'discount' => $discount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 