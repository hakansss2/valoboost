<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Tüm bildirimleri okundu olarak işaretle
if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['success'] = "Tüm bildirimler okundu olarak işaretlendi.";
        redirect('notifications.php');
    } catch(PDOException $e) {
        $_SESSION['error'] = "Bildirimler güncellenirken bir hata oluştu.";
    }
}

// Tek bildirimi okundu olarak işaretle
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    try {
        $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $_SESSION['user_id']]);
        $_SESSION['success'] = "Bildirim okundu olarak işaretlendi.";
        redirect('notifications.php');
    } catch(PDOException $e) {
        $_SESSION['error'] = "Bildirim güncellenirken bir hata oluştu.";
    }
}

// Bildirimleri getir
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Bildirimler getirilirken bir hata oluştu.";
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimlerim - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <style>
    .notification-item {
        transition: background-color 0.3s;
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    .notification-item.unread {
        background-color: #e8f4ff;
    }
    .notification-item.unread:hover {
        background-color: #d8ebff;
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Ana İçerik -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Bildirimlerim</h1>
            <?php if (!empty($notifications)): ?>
                <form method="POST" action="" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Tümünü Okundu İşaretle
                    </button>
                </form>
            <?php endif; ?>
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

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bildirimler Listesi -->
        <div class="card shadow">
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Henüz bildiriminiz bulunmuyor.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item notification-item <?php echo $notification['status'] === 'unread' ? 'unread' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <span class="badge bg-primary rounded-pill">Yeni</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if ($notification['status'] === 'unread'): ?>
                                        <form method="POST" action="" class="ms-3">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-light" title="Okundu İşaretle">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/user.js"></script>
</body>
</html> 