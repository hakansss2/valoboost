<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece boosterların erişimine izin ver
if (!isBooster()) {
    redirect('../login.php');
}

// Tarih filtresi
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Kazanç geçmişini getir
try {
    $stmt = $conn->prepare("
        SELECT bp.*, o.id as order_id, g.name as game_name, 
               cr.name as current_rank, tr.name as target_rank
        FROM booster_payments bp
        LEFT JOIN orders o ON bp.order_id = o.id
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN ranks cr ON o.current_rank_id = cr.id
        LEFT JOIN ranks tr ON o.target_rank_id = tr.id
        WHERE bp.booster_id = ? AND DATE(bp.created_at) BETWEEN ? AND ?
        ORDER BY bp.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $start_date, $end_date]);
    $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Kazanç geçmişi getirilirken bir hata oluştu.";
    $earnings = [];
}

// İstatistikleri hesapla
$total_earnings = 0;
$completed_payments = 0;
$pending_payments = 0;
$total_orders = count($earnings);

foreach ($earnings as $earning) {
    if ($earning['status'] === 'completed') {
        $total_earnings += $earning['amount'];
        $completed_payments++;
    } else {
        $pending_payments += $earning['amount'];
    }
}

// Minimum çekim tutarını getir
$min_withdrawal = (float)getSetting('min_withdrawal');

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid py-4 px-4">
    <!-- Başlık ve Bilgi Kartı -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2 text-gray-800">Kazanç Geçmişi</h1>
                            <p class="mb-0 text-muted">Tamamlanan siparişlerden elde ettiğiniz kazançları görüntüleyin</p>
                        </div>
                        <div>
                            <?php if ($total_earnings >= $min_withdrawal): ?>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                                    <i class="fas fa-money-bill-wave me-2"></i>Ödeme Talebi Oluştur
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fas fa-money-bill-wave me-2"></i>Minimum Çekim Tutarına Ulaşılmadı
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow-sm h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Sipariş</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow-sm h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Toplam Kazanç</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_earnings, 2, ',', '.'); ?> ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow-sm h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Bekleyen Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($pending_payments, 2, ',', '.'); ?> ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow-sm h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Min. Çekim Tutarı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($min_withdrawal, 2, ',', '.'); ?> ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarih Filtresi -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Tarih Aralığı Filtrele</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-filter me-2"></i> Filtrele
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kazanç Tablosu -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Kazanç Geçmişi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="earningsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Sipariş No</th>
                            <th>Oyun</th>
                            <th>Rank Aralığı</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($earnings)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-2"></i>Seçilen tarih aralığında kazanç kaydı bulunamadı
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($earnings as $earning): ?>
                                <tr>
                                    <td>
                                        <a href="order.php?id=<?php echo $earning['order_id']; ?>" class="fw-bold text-primary">
                                            #<?php echo $earning['order_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($earning['game_name'] ?? 'Bilinmiyor'); ?></td>
                                    <td>
                                        <?php if ($earning['current_rank'] && $earning['target_rank']): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($earning['current_rank']); ?></span>
                                            <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($earning['target_rank']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Belirtilmemiş</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-success"><?php echo number_format($earning['amount'], 2, ',', '.'); ?> ₺</td>
                                    <td>
                                        <?php if ($earning['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Ödendi</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Beklemede</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($earning['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme Talebi Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Talebi Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="withdrawalForm" action="request_payment.php" method="POST">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Çekilecek Tutar</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   min="<?php echo $min_withdrawal; ?>" max="<?php echo $total_earnings; ?>" 
                                   value="<?php echo $total_earnings; ?>" step="0.01" required>
                            <span class="input-group-text">₺</span>
                        </div>
                        <div class="form-text">Minimum çekim tutarı: <?php echo number_format($min_withdrawal, 2, ',', '.'); ?> ₺</div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Ödeme Yöntemi</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Ödeme yöntemi seçin</option>
                            <option value="bank">Banka Havalesi</option>
                            <option value="papara">Papara</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    <div class="mb-3 bank-details d-none">
                        <label for="bank_name" class="form-label">Banka Adı</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name">
                    </div>
                    <div class="mb-3 bank-details d-none">
                        <label for="iban" class="form-label">IBAN</label>
                        <input type="text" class="form-control" id="iban" name="iban">
                    </div>
                    <div class="mb-3 papara-details d-none">
                        <label for="papara_number" class="form-label">Papara Numarası</label>
                        <input type="text" class="form-control" id="papara_number" name="papara_number">
                    </div>
                    <div class="mb-3 paypal-details d-none">
                        <label for="paypal_email" class="form-label">PayPal E-posta</label>
                        <input type="email" class="form-control" id="paypal_email" name="paypal_email">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notlar (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="withdrawalForm" class="btn btn-primary">Talep Oluştur</button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // DataTables
    $('#earningsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        },
        order: [[5, 'desc']], // Tarihe göre sırala
        pageLength: 10,
        responsive: true
    });
    
    // Ödeme yöntemi değiştiğinde ilgili alanları göster/gizle
    $('#payment_method').change(function() {
        $('.bank-details, .papara-details, .paypal-details').addClass('d-none');
        
        var method = $(this).val();
        if (method === 'bank') {
            $('.bank-details').removeClass('d-none');
        } else if (method === 'papara') {
            $('.papara-details').removeClass('d-none');
        } else if (method === 'paypal') {
            $('.paypal-details').removeClass('d-none');
        }
    });
});
</script>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
</style>

<?php require_once 'includes/footer.php'; ?> 