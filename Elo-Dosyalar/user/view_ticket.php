<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    $_SESSION['message'] = 'Geçersiz destek talebi ID\'si.';
    $_SESSION['message_type'] = 'danger';
    header('Location: support.php');
    exit;
}

// Destek talebini getir
try {
    $stmt = $conn->prepare("
        SELECT t.*, u.username
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['message'] = 'Destek talebi bulunamadı.';
        $_SESSION['message_type'] = 'danger';
        header('Location: support.php');
        exit;
    }

    // Mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM support_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Yeni mesaj gönderildi mi?
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $message = clean($_POST['message']);
        
        if (!empty($message)) {
            try {
                // Mesajı kaydet
                $stmt = $conn->prepare("
                    INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$ticket_id, $user_id, $message]);
                
                // Destek talebini güncelle
                $stmt = $conn->prepare("
                    UPDATE support_tickets 
                    SET updated_at = NOW(), status = 'open'
                    WHERE id = ?
                ");
                $stmt->execute([$ticket_id]);
                
                // Yöneticilere bildirim gönder
                $stmt = $conn->prepare("
                    SELECT id FROM users WHERE role = 'admin'
                ");
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($admins as $admin_id) {
                    createSupportTicketNotification(
                        $admin_id, 
                        $ticket_id, 
                        'new_message', 
                        'Destek talebi #' . $ticket_id . ' için yeni bir mesaj var.'
                    );
                }
                
                // Sayfayı yenile
                header('Location: view_ticket.php?id=' . $ticket_id);
                exit;
            } catch(PDOException $e) {
                $_SESSION['message'] = 'Mesaj gönderilirken bir hata oluştu.';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Mesaj boş olamaz.';
            $_SESSION['message_type'] = 'danger';
        }
    }
} catch(PDOException $e) {
    $_SESSION['message'] = 'Destek talebi yüklenirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
    header('Location: support.php');
    exit;
}

// Destek talebi durumlarına göre renk sınıfları
function getTicketStatusClass($status) {
    switch ($status) {
        case 'open':
            return 'success';
        case 'closed':
            return 'secondary';
        default:
            return 'primary';
    }
}

// Destek talebi durumlarına göre metin
function getTicketStatusText($status) {
    switch ($status) {
        case 'open':
            return 'Açık';
        case 'closed':
            return 'Kapalı';
        default:
            return 'Bilinmiyor';
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
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

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card welcome-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Destek Talebi #<?php echo $ticket['id']; ?></h2>
                            <p class="text-white-50 mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                        </div>
                        <span class="badge bg-<?php echo getTicketStatusClass($ticket['status']); ?> px-3 py-2">
                            <?php echo getTicketStatusText($ticket['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Destek Talebi Detayları -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Talep Detayları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Konu</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Durum</label>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo getTicketStatusClass($ticket['status']); ?> px-3 py-2">
                                <?php echo getTicketStatusText($ticket['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Oluşturan</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($ticket['username']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Oluşturulma Tarihi</label>
                        <p class="mb-0"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
                    </div>
                    <div>
                        <label class="form-label text-muted small text-uppercase">Son Güncelleme</label>
                        <p class="mb-0"><?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Hızlı Bilgiler -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-lightbulb me-2"></i>
                        Hızlı Bilgiler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Destek ekibimiz en kısa sürede talebinize yanıt verecektir. Mesai saatleri içinde genellikle 2 saat içinde yanıt alırsınız.
                    </div>
                </div>
            </div>

            <!-- Geri Dön Butonu -->
            <a href="support.php" class="btn btn-primary w-100 mb-4">
                <i class="fas fa-arrow-left me-2"></i>
                Destek Taleplerine Dön
            </a>
        </div>

        <div class="col-md-8">
            <!-- Mesajlar -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-comments me-2"></i>
                        Mesajlar
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="chat-messages p-4" id="chatMessages" style="height: 500px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Henüz mesaj bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message mb-4 <?php echo $message['user_id'] == $user_id ? 'message-right' : 'message-left'; ?>">
                                    <div class="message-content">
                                        <div class="message-header mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">
                                                    <?php echo htmlspecialchars($message['username']); ?>
                                                    <?php if ($message['role'] == 'admin'): ?>
                                                        <span class="badge bg-danger ms-1">Admin</span>
                                                    <?php endif; ?>
                                                </span>
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="message-body">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($ticket['status'] == 'open'): ?>
                        <div class="chat-input border-top p-3">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger mb-3">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="messageForm">
                                <div class="form-group">
                                    <label for="message" class="form-label fw-bold mb-2">Yanıtınız</label>
                                    <textarea class="form-control mb-3" id="message" name="message" rows="3" 
                                              placeholder="Mesajınızı yazın..." required></textarea>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Mesajınız destek ekibimize iletilecektir.</small>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Gönder
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted p-4 border-top">
                            <i class="fas fa-lock me-2"></i>
                            Bu destek talebi kapatılmıştır. Yeni bir sorunuz varsa yeni bir destek talebi oluşturabilirsiniz.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.message {
    max-width: 85%;
}

.message-left {
    margin-right: auto;
}

.message-right {
    margin-left: auto;
}

.message-content {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.message-right .message-content {
    background: #e3f2fd;
}

.message-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding-bottom: 8px;
}

.message-body {
    padding-top: 8px;
    white-space: pre-line;
}

.chat-messages {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,.2) transparent;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,.2);
    border-radius: 3px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mesajları en alta kaydır
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Form gönderildiğinde
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Gönderiliyor...';
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 