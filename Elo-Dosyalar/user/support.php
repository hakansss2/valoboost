<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Destek taleplerini getir
try {
    // Destek talepleri istatistikleri
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
            MAX(created_at) as last_ticket_date
        FROM support_tickets 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tüm destek taleplerini getir
    $stmt = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM support_messages WHERE ticket_id = s.id) as message_count,
               (SELECT MAX(created_at) FROM support_messages WHERE ticket_id = s.id) as last_message_date
        FROM support_tickets s 
        WHERE s.user_id = ? 
        ORDER BY s.status = 'open' DESC, s.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['message'] = 'Destek talepleri yüklenirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
    $tickets = [];
    $stats = [
        'total_tickets' => 0,
        'open_tickets' => 0,
        'closed_tickets' => 0,
        'last_ticket_date' => null
    ];
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

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Destek</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active text-muted">Destek</li>
                        </ol>
                    </nav>
                </div>
                <a href="new_ticket.php" class="btn btn-glow btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Yeni Destek Talebi
                </a>
            </div>
        </div>
    </div>

    <!-- Ana Kart -->
    <div class="row">
        <div class="col-xxl-6">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex flex-column h-100">
                                <div class="flex-grow-1">
                                    <h3 class="fw-medium text-capitalize mt-0 mb-2 text-glow">Size Nasıl Yardımcı Olabiliriz?</h3>
                                    <p class="font-18 text-muted">7/24 destek ekibimiz size yardımcı olmak için hazır.</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="row h-100">
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Açık Talepler</h4>
                                                        <div class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center">
                                                            <i class="mdi mdi-ticket"></i>
                                                        </div>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo $stats['open_tickets']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Ortalama Yanıt</h4>
                                                        <div class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center">
                                                            <i class="mdi mdi-clock"></i>
                                                        </div>
                                                    </div>
                                                    <h2 class="mb-0 text-glow">15 dk</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <img src="../assets/img/characters/character4.png" alt="Support" class="img-fluid floating-image" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="row">
                <div class="col-md-6">
                    <div class="card glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="my-0 text-white mb-3">Sık Sorulan Sorular</h4>
                                    <div class="accordion" id="faqAccordion">
                                        <!-- Soru 1 -->
                                        <div class="accordion-item glass-effect mb-2">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button glass-effect text-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                                    Boost işlemi ne kadar sürer?
                                                </button>
                                            </h2>
                                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body text-muted">
                                                    Boost işlem süresi, mevcut rankınız ve hedef rankınız arasındaki farka göre değişmektedir. Ortalama olarak 24-72 saat içerisinde tamamlanmaktadır. Acil işlemler için "Hızlı Boost" seçeneğini tercih edebilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Soru 2 -->
                                        <div class="accordion-item glass-effect mb-2">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button glass-effect text-white collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                                    Ödeme yöntemleri nelerdir?
                                                </button>
                                            </h2>
                                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body text-muted">
                                                    Kredi kartı, banka havalesi/EFT ve Papara ile güvenli ödeme yapabilirsiniz. Tüm ödemeleriniz SSL güvenlik sertifikası ile korunmaktadır. İşlemleriniz anında hesabınıza yansımaktadır.
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Soru 3 -->
                                        <div class="accordion-item glass-effect">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button glass-effect text-white collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                                    Hesabım güvende mi?
                                                </button>
                                            </h2>
                                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body text-muted">
                                                    Tüm boosterlarımız profesyonel ve güvenilir kişilerdir. VPN kullanımı ve hesap güvenliği için gerekli tüm önlemler alınmaktadır. Ayrıca "Offline Mod" seçeneği ile hesabınızın boost sırasında çevrimdışı görünmesini sağlayabilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Discord</h4>
                                    <p class="mb-2 text-muted">Discord sunucumuza katılın</p>
                                    <a href="#" class="btn btn-glow btn-primary btn-sm">Katıl</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg rounded-circle bg-glow">
                                        <i class="fab fa-discord fa-2x text-white mt-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Destek Talepleri -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Destek Talepleriniz</h5>
                        <a href="new_ticket.php" class="btn btn-glow btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Yeni Talep
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted"></i>
                            </div>
                            <h4 class="text-white mb-3">Henüz Destek Talebiniz Bulunmuyor</h4>
                            <p class="text-muted mb-4">Yardıma ihtiyacınız varsa hemen bir destek talebi oluşturun!</p>
                            <a href="new_ticket.php" class="btn btn-glow btn-primary btn-lg px-5">
                                <i class="fas fa-plus me-2"></i>Yeni Talep Oluştur
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-dark table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-white">#</th>
                                        <th class="text-white">Konu</th>
                                        <th class="text-white">Durum</th>
                                        <th class="text-white">Son Güncelleme</th>
                                        <th class="text-white">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr class="glass-effect-light">
                                            <td class="text-white">#<?php echo $ticket['id']; ?></td>
                                            <td class="text-white"><?php echo $ticket['subject']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getTicketStatusClass($ticket['status']); ?> glow-badge">
                                                    <?php echo getTicketStatusText($ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-white"><?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?></td>
                                            <td>
                                                <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-glow btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-effect border-0">
            <div class="modal-header bg-dark-gradient border-0">
                <h5 class="modal-title text-white">Yeni Destek Talebi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newTicketForm">
                    <div class="mb-3">
                        <label for="subject" class="form-label text-white">Konu</label>
                        <input type="text" class="form-control glass-effect text-white" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label text-white">Mesajınız</label>
                        <textarea class="form-control glass-effect text-white" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-glow btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Theme */
.dark-theme {
    background-color: #0a0b1e;
    color: #fff;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.glass-effect-light {
    background: rgba(255, 255, 255, 0.02) !important;
}

/* Hover Effect */
.hover-effect {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.hover-effect:hover {
    transform: translateY(-5px) rotateX(5deg);
    box-shadow: 0 15px 30px rgba(106, 17, 203, 0.2) !important;
}

/* Glow Effects */
.text-glow {
    color: #00f3ff;
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

.glow-badge {
    box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.4);
}

/* Background Gradients */
.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}

/* Table Styles */
.table-dark {
    background-color: transparent;
}

.table-dark thead th {
    background-color: rgba(255, 255, 255, 0.05);
    border-bottom: none;
}

.table-dark td {
    border-color: rgba(255, 255, 255, 0.05);
}

/* Form Styles */
.form-control.glass-effect {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
}

.form-control.glass-effect:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
}

/* Avatar Styles */
.avatar-lg {
    height: 4rem;
    width: 4rem;
}

.bg-glow {
    background: rgba(106, 17, 203, 0.3);
}

/* Floating Animation */
@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.floating-image {
    animation: floating 3s ease-in-out infinite;
}

/* Modal Styles */
.modal-content.glass-effect {
    background: rgba(26, 27, 58, 0.95);
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Accordion Styles */
.accordion-item {
    background: transparent;
    border: none;
}

.accordion-button {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: none;
    padding: 1rem;
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background: rgba(255, 255, 255, 0.1) !important;
    color: var(--neon-blue);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
}

.accordion-button::after {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.accordion-button:not(.collapsed)::after {
    filter: invert(1) grayscale(100%) brightness(200%) hue-rotate(190deg);
}

.accordion-body {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-top: none;
    padding: 1rem;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
}

.accordion-button:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    transform: translateX(5px);
}

.accordion-button:focus {
    box-shadow: none;
    border-color: var(--neon-blue);
}
</style>

<script>
$(document).ready(function() {
    $('#newTicketForm').on('submit', function(e) {
        e.preventDefault();
        
        var subject = $('#subject').val().trim();
        var message = $('#message').val().trim();
        
        if (!subject || !message) {
            alert('Lütfen tüm alanları doldurun.');
            return;
        }
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/create_ticket.php',
            type: 'POST',
            data: {
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('Destek talebiniz oluşturuldu.');
                    window.location.reload();
                } else {
                    alert(response.message || 'Bir hata oluştu.');
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>