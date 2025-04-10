<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$ranks = isset($_POST['ranks']) ? $_POST['ranks'] : [];

if (empty($ranks)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veriler']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE ranks SET value = ? WHERE id = ?");
    
    foreach ($ranks as $rank) {
        $stmt->execute([$rank['value'], $rank['id']]);
    }
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}