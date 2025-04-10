<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    try {
        // Önce rankın bilgilerini al
        $stmt = $conn->prepare("SELECT game_id, image FROM ranks WHERE id = ?");
        $stmt->execute([$id]);
        $rank = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rank) {
            // Rankı sil
            $stmt = $conn->prepare("DELETE FROM ranks WHERE id = ?");
            $stmt->execute([$id]);
            
            // Eğer resim varsa sil
            if ($rank['image'] && file_exists('../' . $rank['image'])) {
                unlink('../' . $rank['image']);
            }
            
            $_SESSION['message'] = 'Rank başarıyla silindi.';
            $_SESSION['message_type'] = 'success';
            
            // Rankları yeniden sırala
            $stmt = $conn->prepare("
                SELECT id FROM ranks 
                WHERE game_id = ? 
                ORDER BY value
            ");
            $stmt->execute([$rank['game_id']]);
            $ranks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sıralama değerlerini güncelle
            foreach ($ranks as $index => $r) {
                $stmt = $conn->prepare("UPDATE ranks SET value = ? WHERE id = ?");
                $stmt->execute([$index + 1, $r['id']]);
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Rank silinirken bir hata oluştu.';
        $_SESSION['message_type'] = 'danger';
    }
}

// Bir önceki sayfaya yönlendir
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;