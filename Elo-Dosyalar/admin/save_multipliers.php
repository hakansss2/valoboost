<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Admin kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = (int)$_POST['game_id'];
    $priority_multiplier = (float)$_POST['priority_multiplier'];
    $streaming_multiplier = (float)$_POST['streaming_multiplier'];

    try {
        $stmt = $conn->prepare("
            UPDATE boost_prices 
            SET priority_multiplier = ?, streaming_multiplier = ?
            WHERE game_id = ?
        ");
        
        $stmt->execute([$priority_multiplier, $streaming_multiplier, $game_id]);
        
        $_SESSION['message'] = 'Çarpanlar başarıyla güncellendi.';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Hata: Çarpanlar güncellenirken bir sorun oluştu.';
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: boost_prices.php?game_id=' . $game_id);
exit; 