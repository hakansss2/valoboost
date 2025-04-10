<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Yönetici kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST verilerini kontrol et
$booster_id = isset($_POST['booster_id']) ? (int)$_POST['booster_id'] : 0;
$game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;

if (!$booster_id || !$game_id) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz booster veya oyun ID']));
}

try {
    // Booster kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'booster'");
    $stmt->execute([$booster_id]);
    if ($stmt->rowCount() === 0) {
        throw new Exception('Booster bulunamadı');
    }

    // Oyun kontrolü
    $stmt = $conn->prepare("SELECT id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    if ($stmt->rowCount() === 0) {
        throw new Exception('Oyun bulunamadı');
    }

    // Oyun zaten atanmış mı kontrol et
    $stmt = $conn->prepare("SELECT id FROM booster_games WHERE booster_id = ? AND game_id = ?");
    $stmt->execute([$booster_id, $game_id]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Bu oyun zaten booster\'a atanmış');
    }

    // Oyunu ata
    $stmt = $conn->prepare("INSERT INTO booster_games (booster_id, game_id) VALUES (?, ?)");
    $stmt->execute([$booster_id, $game_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Oyun başarıyla atandı'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 