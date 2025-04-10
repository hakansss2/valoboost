<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Booster kontrolü
if (!isBooster()) {
    redirect('../login.php');
}

// Yeni destek talebi oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_ticket') {
        $subject = clean($_POST['subject']);
        $message = clean($_POST['message']);
        
        if (empty($subject) || empty($message)) {
            $_SESSION['error'] = "Konu ve mesaj alanları zorunludur.";
        } else {
            try {
                $conn->beginTransaction();
                
                // Destek talebini oluştur
                $stmt = $conn->prepare("
                    INSERT INTO support_tickets (user_id, subject, status, created_at, updated_at)
                    VALUES (?, ?, 'open', NOW(), NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], $subject]);
                $ticket_id = $conn->lastInsertId();
                
                // İlk mesajı ekle
                $stmt = $conn->prepare("
                    INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
                
                $conn->commit();
                $_SESSION['success'] = "Destek talebiniz başarıyla oluşturuldu.";
                redirect('support.php');
            } catch (PDOException $e) {
                $conn->rollBack();
                $_SESSION['error'] = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'add_message' && isset($_POST['ticket_id'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $message = clean($_POST['message']);
        
        if (empty($message)) {
            $_SESSION['error'] = "Mesaj alanı boş bırakılamaz.";
        } else {
            try {
                // Talebin kullanıcıya ait olduğunu kontrol et
                $stmt = $conn->prepare("
                    SELECT id FROM support_tickets 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$ticket_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    // Mesajı ekle
                    $stmt = $conn->prepare("
                        INSERT INTO support_messages (ticket_id, user_id, message, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
                    
                    // Talebin durumunu güncelle
                    $stmt = $conn->prepare("
                        UPDATE support_tickets 
                        SET status = 'open', updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$ticket_id]);
                    
                    $_SESSION['success'] = "Mesajınız başarıyla gönderildi.";
                } else {
                    $_SESSION['error'] = "Geçersiz talep.";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Destek taleplerini getir
try {
    $stmt = $conn->prepare("
        SELECT t.*, 
               COUNT(m.id) as message_count,
               MAX(m.created_at) as last_message_date
        FROM support_tickets t
        LEFT JOIN support_messages m ON t.id = m.ticket_id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.status = 'open' DESC, t.updated_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Destek talepleri alınırken bir hata oluştu.";
    $tickets = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-white">Destek Talepleri</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="fas fa-plus me-2"></i> Yeni Destek Talebi
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-5">
                            <img src="../assets/img/no-data.svg" alt="Destek Talebi Yok" class="mb-3" style="width: 200px; opacity: 0.7;">
                            <h4 class="text-white">Henüz Destek Talebi Yok</h4>
                            <p class="text-muted">Yeni bir destek talebi oluşturmak için yukarıdaki butonu kullanabilirsiniz.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Talep No</th>
                                        <th>Konu</th>
                                        <th>Durum</th>
                                        <th>Son Güncelleme</th>
                                        <th>Mesaj Sayısı</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>#<?php echo $ticket['id']; ?></td>
                                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'open' => 'success',
                                                    'in_progress' => 'warning',
                                                    'closed' => 'secondary'
                                                ];
                                                $status_text = [
                                                    'open' => 'Açık',
                                                    'in_progress' => 'İşlemde',
                                                    'closed' => 'Kapalı'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$ticket['status']]; ?>">
                                                    <?php echo $status_text[$ticket['status']]; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?></td>
                                            <td><?php echo $ticket['message_count']; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary view-ticket" 
                                                        data-ticket-id="<?php echo $ticket['id']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#viewTicketModal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Destek Talebi Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Yeni Destek Talebi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_ticket">
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Konu</label>
                        <input type="text" name="subject" class="form-control bg-dark text-white" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Mesajınız</label>
                        <textarea name="message" class="form-control bg-dark text-white" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Destek Talebi Görüntüleme Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Destek Talebi #<span id="ticketNumber"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="messageList" class="mb-4" style="max-height: 400px; overflow-y: auto;"></div>
                
                <form method="POST" id="replyForm">
                    <input type="hidden" name="action" value="add_message">
                    <input type="hidden" name="ticket_id" id="ticketId">
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Yanıtınız</label>
                        <textarea name="message" class="form-control bg-dark text-white" rows="3" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Yanıtla</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Mesaj Stilleri */
.message {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 10px;
}

.message-user {
    background: rgba(var(--bs-primary-rgb), 0.1);
    margin-left: 2rem;
}

.message-admin {
    background: rgba(255, 255, 255, 0.1);
    margin-right: 2rem;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.message-content {
    white-space: pre-wrap;
}

/* Modal Stilleri */
.modal-content {
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.form-control {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff !important;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
}

/* Tablo Stilleri */
.table {
    color: rgba(255, 255, 255, 0.8) !important;
    margin-bottom: 0;
}

.table thead th {
    background: transparent !important;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    color: #fff !important;
    font-weight: 600;
    padding: 1rem;
}

.table tbody td {
    background: transparent !important;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8) !important;
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

/* DataTables özelleştirmeleri */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    color: rgba(255, 255, 255, 0.8) !important;
}

.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
}

.dataTables_wrapper .dataTables_length select option {
    background: rgba(26, 27, 58, 0.95) !important;
    color: #fff !important;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-color: transparent;
}

.page-link {
    background: transparent;
    border-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.page-link:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--neon-blue);
}

/* Card Stilleri */
.card {
    background: rgba(26, 27, 58, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 20px;
}

.card-body {
    background: transparent !important;
}

/* Badge Stilleri */
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.badge.bg-success {
    background: linear-gradient(135deg, #1cc88a, #1cc88a) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #f6c23e, #f6c23e) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #858796, #858796) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Destek talebini görüntüleme
    document.querySelectorAll('.view-ticket').forEach(button => {
        button.addEventListener('click', function() {
            const ticketId = this.dataset.ticketId;
            document.getElementById('ticketNumber').textContent = ticketId;
            document.getElementById('ticketId').value = ticketId;
            
            // Mesajları getir
            fetch(`get_ticket_messages.php?id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const messageList = document.getElementById('messageList');
                        messageList.innerHTML = '';
                        
                        data.messages.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${message.is_admin ? 'message-admin' : 'message-user'}`;
                            
                            messageDiv.innerHTML = `
                                <div class="message-header">
                                    <strong class="text-white">${message.username}</strong>
                                    <span class="text-muted">${message.created_at}</span>
                                </div>
                                <div class="message-content text-white">
                                    ${message.message}
                                </div>
                            `;
                            
                            messageList.appendChild(messageDiv);
                        });

                        // Mesajları en alta kaydır
                        messageList.scrollTop = messageList.scrollHeight;
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 