<?php

// Yanıt gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message = isset($_POST['message']) ? clean($_POST['message']) : '';
    
    if (!empty($message)) {
        try {
            $conn->beginTransaction();

            // Mesajı ekle
            $stmt = $conn->prepare("
                INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);

            // Bildirim gönder
            createSupportTicketNotification($ticket['user_id'], $ticket_id, 'reply');

            $conn->commit();
            $_SESSION['success'] = "Yanıtınız başarıyla gönderildi.";
            redirect("ticket.php?id=" . $ticket_id);
        } catch(PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Yanıt gönderilirken bir hata oluştu.";
        }
    } else {
        $_SESSION['error'] = "Mesaj alanı boş bırakılamaz.";
    }
}

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = clean($_POST['status']);
    
    if (in_array($new_status, ['open', 'closed'])) {
        try {
            $conn->beginTransaction();

            // Durumu güncelle
            $stmt = $conn->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $ticket_id]);

            // Bildirim gönder
            if ($new_status === 'closed') {
                createSupportTicketNotification($ticket['user_id'], $ticket_id, 'status');
            }

            $conn->commit();
            $_SESSION['success'] = "Destek talebi durumu güncellendi.";
            redirect("ticket.php?id=" . $ticket_id);
        } catch(PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Durum güncellenirken bir hata oluştu.";
        }
    }
} 