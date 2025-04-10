<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isUser()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

if (!$game_id) {
    echo json_encode(['error' => 'Invalid game ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM ranks WHERE game_id = ? ORDER BY value");
    $stmt->execute([$game_id]);
    $ranks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($ranks);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}