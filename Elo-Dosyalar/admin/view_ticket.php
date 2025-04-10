<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Destek talebi ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: support.php');
    exit;
}

$ticket_id = intval($_GET['id']);

try {
    // Destek talebi bilgilerini getir
    $stmt = $conn->prepare("
        SELECT t.*, u.username
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['message'] = 'Destek talebi bulunamadı.';
        $_SESSION['message_type'] = 'danger';
        header('Location: support.php');
        exit;
    }

    // Destek talebi mesajlarını getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM support_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Yeni mesaj gönderme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $message = trim($_POST['message']);
        
        if (!empty($message)) {
            $stmt = $conn->prepare("
                INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);

            // Destek talebinin son güncelleme zamanını güncelle
            $stmt = $conn->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$ticket_id]);

            header("Location: view_ticket.php?id=" . $ticket_id);
            exit;
        }
    }
} catch(PDOException $e) {
    $_SESSION['message'] = 'Veritabanı hatası oluştu.';
    $_SESSION['message_type'] = 'danger';
    header('Location: support.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Destek Talebi #<?php echo $ticket['id']; ?></h2>
                <a href="support.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                            <small class="text-muted">
                                Oluşturan: <?php echo htmlspecialchars($ticket['username']); ?> |
                                Tarih: <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $ticket['status'] == 'open' ? 'success' : 'secondary'; ?>">
                            <?php echo $ticket['status'] == 'open' ? 'Açık' : 'Kapalı'; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;" id="messages">
                    <?php foreach ($messages as $message): ?>
                        <div class="d-flex mb-3 <?php echo $message['role'] == 'admin' ? 'flex-row-reverse' : ''; ?>">
                            <div class="<?php echo $message['role'] == 'admin' ? 'ms-3' : 'me-3'; ?>">
                                <div class="bg-<?php echo $message['role'] == 'admin' ? 'primary' : 'light'; ?> rounded p-3" 
                                     style="max-width: 80%;">
                                    <div class="text-<?php echo $message['role'] == 'admin' ? 'white' : 'dark'; ?>">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                    <small class="text-<?php echo $message['role'] == 'admin' ? 'white' : 'muted'; ?>">
                                        <?php echo htmlspecialchars($message['username']); ?> |
                                        <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($ticket['status'] == 'open'): ?>
                    <div class="card-footer">
                        <form method="POST" id="messageForm">
                            <div class="form-group">
                                <textarea class="form-control" name="message" rows="3" 
                                          placeholder="Mesajınızı yazın..." required></textarea>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gönder
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card-footer text-center text-muted">
                        Bu destek talebi kapatılmıştır.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Mesajları en alta kaydır
    var messagesDiv = document.getElementById('messages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
});
</script>

<?php require_once 'includes/footer.php'; ?> 