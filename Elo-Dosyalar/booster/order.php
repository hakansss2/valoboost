<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece boosterların erişimine izin ver
if (!isBooster()) {
    redirect('../login.php');
}

// Sipariş ID kontrolü
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    $_SESSION['error'] = "Geçersiz sipariş ID'si.";
    redirect('orders.php');
}

// Siparişi getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               g.name as game_name, g.image as game_image,
               u.username as user_username, u.id as user_id,
               cr.name as current_rank, cr.image as current_rank_image,
               tr.name as target_rank, tr.image as target_rank_image
        FROM orders o
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN ranks cr ON o.current_rank_id = cr.id
        LEFT JOIN ranks tr ON o.target_rank_id = tr.id
        WHERE o.id = ? AND o.booster_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Sipariş bulunamadı.";
        redirect('orders.php');
    }
    
    // Mesajları getir
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.role
        FROM order_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.order_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Bir hata oluştu.";
    redirect('orders.php');
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
    $note = isset($_POST['note']) ? clean($_POST['note']) : '';
    $errors = [];

    // Validasyon
    if ($progress < 0 || $progress > 100) {
        $errors[] = "İlerleme değeri 0-100 arasında olmalıdır.";
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // İlerlemeyi güncelle
            $stmt = $conn->prepare("UPDATE orders SET progress = ? WHERE id = ?");
            $stmt->execute([$progress, $order_id]);

            // Not ekle
            if (!empty($note)) {
                $stmt = $conn->prepare("
                    INSERT INTO order_notes (order_id, user_id, note, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$order_id, $_SESSION['user_id'], $note]);
            }

            // İlerleme 100 ise siparişi tamamla
            if ($progress == 100) {
                // Siparişi tamamla
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET status = 'completed', 
                        completed_at = NOW(),
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$order_id]);

                // Müşteriye bildirim gönder
                createNotification(
                    $order['user_id'],
                    "Sipariş #$order_id tamamlandı! Boost işlemi başarıyla tamamlanmıştır."
                );

                // Booster ödemesini oluştur
                $commission_rate = (float)getSetting('commission_rate') ?: 20; // Varsayılan %20
                $booster_rate = 100 - $commission_rate; // Booster'ın alacağı oran
                $booster_amount = $order['price'] * ($booster_rate / 100);

                $stmt = $conn->prepare("
                    INSERT INTO booster_payments (booster_id, order_id, amount, status, created_at)
                    VALUES (?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], $order_id, $booster_amount]);
            }

            $conn->commit();
            $_SESSION['success'] = "Sipariş başarıyla güncellendi.";
            
            if ($progress == 100) {
                redirect('completed.php');
            } else {
                redirect('order.php?id=' . $order_id);
            }
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors[] = "Sipariş güncellenirken bir hata oluştu.";
        }
    }
}

// Sipariş notlarını getir
try {
    $stmt = $conn->prepare("
        SELECT n.*, u.username
        FROM order_notes n
        LEFT JOIN users u ON n.user_id = u.id
        WHERE n.order_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$order_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $notes = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sipariş Detayları -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Sipariş #<?php echo $order['id']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($order['game_image']): ?>
                            <img src="../<?php echo htmlspecialchars($order['game_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['game_name']); ?>"
                                 class="img-fluid mb-2" style="max-height: 80px;">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($order['game_name']); ?></h4>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Müşteri Bilgileri</h6>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle fa-2x me-2 text-primary"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($order['user_username']); ?></div>
                                <small class="text-muted">Müşteri ID: #<?php echo $order['user_id']; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Rank Bilgileri</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center">
                                <?php if ($order['current_rank_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                         alt="" class="img-fluid mb-2" style="height: 50px;">
                                <?php endif; ?>
                                <div class="small">
                                    <div class="fw-bold">Mevcut</div>
                                    <?php echo htmlspecialchars($order['current_rank']); ?>
                                </div>
                            </div>
                            <i class="fas fa-arrow-right text-muted"></i>
                            <div class="text-center">
                                <?php if ($order['target_rank_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                         alt="" class="img-fluid mb-2" style="height: 50px;">
                                <?php endif; ?>
                                <div class="small">
                                    <div class="fw-bold">Hedef</div>
                                    <?php echo htmlspecialchars($order['target_rank']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">İlerleme Durumu</h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $order['progress']; ?>%"
                                 aria-valuenow="<?php echo $order['progress']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo $order['progress']; ?>%
                            </div>
                        </div>
                    </div>
                    
                    <!-- İlerleme Güncelleme Formu -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">İlerleme Durumu</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($order['status'] === 'in_progress'): ?>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="progress" class="form-label">İlerleme (%)</label>
                                        <input type="number" class="form-control" id="progress" name="progress"
                                               value="<?php echo $order['progress']; ?>" min="0" max="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="note" class="form-label">Not (Opsiyonel)</label>
                                        <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>
                                        Güncelle
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle text-success fa-3x"></i>
                                    </div>
                                    <h5>Sipariş Tamamlandı</h5>
                                    <p class="text-muted mb-0">Bu sipariş için ilerleme güncellemesi yapamazsınız.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Alanı -->
        <div class="col-md-8">
            <div class="card shadow-lg border-0 bg-dark text-light">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Müşteri ile Sohbet
                    </h5>
                </div>
                <div class="card-body p-0 bg-dark">
                    <div class="chat-messages p-4" id="chatMessages" style="height: 500px; overflow-y: auto; background: linear-gradient(to bottom, #1a1a2e, #16213e);">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <div class="mb-4">
                                    <i class="fas fa-comments fa-4x mb-3 text-primary"></i>
                                </div>
                                <h5 class="text-light">Henüz mesaj bulunmuyor</h5>
                                <p class="text-muted">Müşteri ile iletişime geçmek için aşağıdan mesaj gönderebilirsiniz.</p>
                            </div>
                        <?php else: ?>
                            <div class="chat-date-divider mb-4">
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                    <?php echo date('d F Y', strtotime($messages[0]['created_at'])); ?>
                                </span>
                            </div>
                            <?php 
                            $lastDate = date('Y-m-d', strtotime($messages[0]['created_at']));
                            foreach ($messages as $index => $message): 
                                $messageDate = date('Y-m-d', strtotime($message['created_at']));
                                if ($messageDate != $lastDate && $index > 0):
                                    $lastDate = $messageDate;
                            ?>
                                <div class="chat-date-divider my-4">
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                        <?php echo date('d F Y', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                                <div class="message mb-3 <?php echo $message['user_id'] == $_SESSION['user_id'] ? 'message-right' : 'message-left'; ?>">
                                    <div class="message-content">
                                        <div class="message-header mb-1">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($message['username']); ?>&background=random" 
                                                         class="img-fluid rounded-circle">
                                                </div>
                                                <span class="fw-bold">
                                                    <?php echo htmlspecialchars($message['username']); ?>
                                                    <?php if ($message['role'] == 'booster'): ?>
                                                        <span class="badge bg-primary ms-1">Booster</span>
                                                    <?php elseif ($message['role'] == 'admin'): ?>
                                                        <span class="badge bg-danger ms-1">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info ms-1">Müşteri</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="message-body">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input border-top p-3 bg-dark">
                        <form id="messageForm" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="input-group">
                                <textarea name="message" class="form-control bg-dark text-light border-secondary" 
                                          placeholder="Mesajınızı yazın..." rows="2" required></textarea>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle me-1"></i> Enter tuşu ile gönderebilirsiniz
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Genel Stil */
.container-fluid {
    color: #fff;
}

/* Kart Stilleri */
.card {
    background: rgba(26, 27, 58, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 20px;
}

.card-header {
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-header h5 {
    color: #fff !important;
}

.card-body {
    color: #fff;
}

/* Metin Renkleri */
.text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

.fw-bold, .h5, h5, h6, .h6 {
    color: #fff !important;
}

/* Form Elemanları */
.form-control {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: #fff !important;
}

.form-label {
    color: #fff !important;
}

/* Mesajlar Bölümü */
.chat-message {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.chat-message.user-message {
    background: rgba(106, 17, 203, 0.2);
}

.chat-message.booster-message {
    background: rgba(37, 117, 252, 0.2);
}

/* Progress Bar */
.progress {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.progress-bar {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 10px;
}

/* Notlar Bölümü */
.note-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* Badge ve İkonlar */
.badge {
    padding: 0.5em 1em;
}

.badge.bg-success {
    background: linear-gradient(135deg, #1cc88a, #1cc88a) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #f6c23e, #f6c23e) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #e74a3b, #e74a3b) !important;
}

/* Butonlar */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: white;
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #1cc88a, #1cc88a);
    border: none;
}

.btn-warning {
    background: linear-gradient(135deg, #f6c23e, #f6c23e);
    border: none;
}

/* Avatar ve Resimler */
.avatar-lg {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.2);
}

.rank-badge img {
    border-radius: 50%;
    padding: 2px;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.2s;
}

.rank-badge img:hover {
    transform: scale(1.1);
    border-color: var(--primary-color);
}

.bg-gradient-primary {
    background: linear-gradient(to right, #4e73df, #224abe);
}

.avatar-xs {
    width: 24px;
    height: 24px;
    overflow: hidden;
}

.message {
    max-width: 80%;
    position: relative;
}

.message-left {
    margin-right: auto;
}

.message-right {
    margin-left: auto;
}

.message-content {
    border-radius: 15px;
    padding: 12px 15px;
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.message-left .message-content {
    background: #2a2d3a;
    border-top-left-radius: 0;
}

.message-right .message-content {
    background: #3a3f9e;
    border-top-right-radius: 0;
    color: #fff;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    font-size: 0.85rem;
}

.message-body {
    word-break: break-word;
}

.chat-date-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 1rem 0;
    text-align: center;
}

.chat-messages {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.2) transparent;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background-color: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.chat-input textarea {
    resize: none;
    border-radius: 20px;
    background-color: #2a2d3a !important;
}

.chat-input textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    border-color: #4e73df;
}

.chat-input .btn {
    border-radius: 20px;
}

.btn-outline-secondary {
    color: #adb5bd;
    border-color: #495057;
}

.btn-outline-secondary:hover {
    background-color: #343a40;
    border-color: #6c757d;
    color: #fff;
}

.card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.card-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 1.5rem;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(78, 115, 223, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(78, 115, 223, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(78, 115, 223, 0);
    }
}

.badge.bg-success {
    animation: pulse 2s infinite;
}
</style>

<script>
// Mesajları en alta kaydır
function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Sayfa yüklendiğinde mesajları en alta kaydır
document.addEventListener('DOMContentLoaded', scrollToBottom);

let messageCheckInterval;

function startMessageCheck() {
    // Sipariş durumunu kontrol et
    const orderStatus = '<?php echo $order['status']; ?>';
    
    // Eğer sipariş tamamlandıysa veya iptal edildiyse mesaj kontrolünü başlatma
    if (orderStatus === 'completed' || orderStatus === 'cancelled') {
        return;
    }
    
    // Mesaj kontrolünü başlat
    let lastCheck = Math.floor(Date.now() / 1000);
    
    messageCheckInterval = setInterval(() => {
        fetch(`check_new_messages.php?order_id=<?php echo $order_id; ?>&last_time=${lastCheck}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    window.location.reload();
                }
                lastCheck = Math.floor(Date.now() / 1000);
            })
            .catch(error => console.error('Error:', error));
    }, 10000);
}

function stopMessageCheck() {
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
    }
}

// Sayfa yüklendiğinde mesaj kontrolünü başlat
document.addEventListener('DOMContentLoaded', startMessageCheck);

// Sayfadan ayrılırken interval'i temizle
window.addEventListener('unload', stopMessageCheck);

// Mesaj gönderme
document.getElementById('messageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Sipariş durumunu kontrol et
    const orderStatus = '<?php echo $order['status']; ?>';
    if (orderStatus === 'completed' || orderStatus === 'cancelled') {
        Swal.fire({
            icon: 'warning',
            title: 'Uyarı',
            text: 'Bu sipariş için artık mesaj gönderemezsiniz.'
        });
        return;
    }
    
    const form = this;
    const submitButton = form.querySelector('button[type="submit"]');
    const message = form.querySelector('textarea[name="message"]').value.trim();
    
    if (!message) return;
    
    // Butonu devre dışı bırak
    submitButton.disabled = true;
    
    // Mesajı gönder
    fetch('send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            order_id: form.querySelector('input[name="order_id"]').value,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Formu temizle
            form.reset();
            // Sayfayı yenile
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Mesaj gönderilirken bir hata oluştu.'
        });
    })
    .finally(() => {
        submitButton.disabled = false;
    });
});

// Enter tuşu ile mesaj gönderme
document.querySelector('textarea[name="message"]')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('messageForm').dispatchEvent(new Event('submit'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 