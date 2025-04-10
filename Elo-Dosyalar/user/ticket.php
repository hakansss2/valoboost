<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Talep ID kontrolü
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$ticket_id) {
    $_SESSION['error'] = "Geçersiz destek talebi ID'si.";
    redirect('support.php');
}

// Destek talebini getir
try {
    $stmt = $conn->prepare("
        SELECT t.*, u.username
        FROM support_tickets t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['error'] = "Destek talebi bulunamadı.";
        redirect('support.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Destek talebi getirilirken bir hata oluştu.";
    redirect('support.php');
}

// Yeni mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_message') {
        $message = isset($_POST['message']) ? clean($_POST['message']) : '';
        
        if (!empty($message)) {
            try {
                // Mesajı kaydet
                $stmt = $conn->prepare("
                    INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
                
                // Talebin durumunu güncelle
                if ($ticket['status'] === 'closed') {
                    $stmt = $conn->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
                    $stmt->execute([$ticket_id]);
                }
                
                $_SESSION['success'] = "Mesajınız gönderildi.";
                redirect("ticket.php?id=$ticket_id");
            } catch(PDOException $e) {
                $errors[] = "Mesaj gönderilirken bir hata oluştu.";
            }
        } else {
            $errors[] = "Mesaj içeriği boş olamaz.";
        }
    }
    elseif ($_POST['action'] === 'close_ticket' && $ticket['status'] === 'open') {
        try {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            $_SESSION['success'] = "Destek talebi kapatıldı.";
            redirect("ticket.php?id=$ticket_id");
        } catch(PDOException $e) {
            $errors[] = "Talep kapatılırken bir hata oluştu.";
        }
    }
}

// Mesajları getir
try {
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM support_messages m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Mesajlar getirilirken bir hata oluştu.";
    $messages = [];
}

// Durum renkleri ve etiketleri
$status_colors = [
    'open' => 'success',
    'closed' => 'secondary'
];

$status_labels = [
    'open' => 'Açık',
    'closed' => 'Kapalı'
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Talebi #<?php echo $ticket_id; ?> - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <style>
    body {
        background-color: #0a0b1e;
        color: #fff;
    }

    .card {
        background: rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
    }

    .message {
        margin-bottom: 1.5rem;
    }

    .message .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(106, 17, 203, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
    }

    .message .content {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 1rem;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .message.admin .content {
        background: rgba(106, 17, 203, 0.1);
        border: 1px solid rgba(106, 17, 203, 0.2);
    }

    .message .meta {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .btn-glow {
        box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
    }

    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff !important;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
        border-color: rgba(106, 17, 203, 0.5);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .text-muted {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .alert {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .alert-success {
        background: rgba(25, 135, 84, 0.2);
        border-color: rgba(25, 135, 84, 0.4);
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.2);
        border-color: rgba(220, 53, 69, 0.4);
    }

    .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    </style>
</head>
<body class="dark-theme">
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Ana İçerik -->
    <div class="container-fluid py-4 techui-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 text-white">
                    Destek Talebi #<?php echo $ticket_id; ?>
                    <span class="badge bg-<?php echo $status_colors[$ticket['status']]; ?> ms-2">
                        <?php echo $status_labels[$ticket['status']]; ?>
                    </span>
                </h4>
                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($ticket['subject']); ?>
                </p>
            </div>
            <div>
                <?php if ($ticket['status'] === 'open'): ?>
                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Destek talebini kapatmak istediğinizden emin misiniz?');">
                        <input type="hidden" name="action" value="close_ticket">
                        <button type="submit" class="btn btn-danger btn-glow">
                            <i class="fas fa-times me-2"></i>Talebi Kapat
                        </button>
                    </form>
                <?php endif; ?>
                <a href="support.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Mesajlar -->
        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <!-- İlk Mesaj -->
                <div class="message">
                    <div class="d-flex">
                        <div class="avatar me-3">
                            <?php echo strtoupper(substr($ticket['username'], 0, 1)); ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="content">
                                <div class="meta mb-2">
                                    <strong class="text-white"><?php echo htmlspecialchars($ticket['username']); ?></strong> ·
                                    <span title="<?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>">
                                        <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-text">
                                    <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diğer Mesajlar -->
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['role'] === 'admin' ? 'admin' : ''; ?>">
                        <div class="d-flex">
                            <div class="avatar me-3">
                                <?php echo strtoupper(substr($message['username'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="content">
                                    <div class="meta mb-2">
                                        <strong class="text-white"><?php echo htmlspecialchars($message['username']); ?></strong> ·
                                        <span title="<?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>">
                                            <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($ticket['status'] === 'open'): ?>
                    <div class="mt-4">
                        <form method="POST" action="" id="messageForm">
                            <input type="hidden" name="action" value="send_message">
                            <div class="form-group">
                                <label for="message" class="form-label text-white mb-2">Yanıtınız</label>
                                <textarea class="form-control mb-3" id="message" name="message" rows="3" 
                                          placeholder="Mesajınızı yazın..." required></textarea>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Mesajınız destek ekibimize iletilecektir.</small>
                                    <button type="submit" class="btn btn-primary btn-glow">
                                        <i class="fas fa-paper-plane me-2"></i>Gönder
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted p-4 border-top border-secondary">
                        <i class="fas fa-lock me-2"></i>
                        Bu destek talebi kapatılmıştır. Yeni bir sorunuz varsa yeni bir destek talebi oluşturabilirsiniz.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/user.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form değişikliklerini izle
        watchFormChanges('messageForm');
    });
    </script>
</body>
</html> 