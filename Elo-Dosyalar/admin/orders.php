<?php
require_once 'includes/header.php';

// Siparişleri getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
        u.username as user_username,
        b.username as booster_username,
        g.name as game_name,
        g.image as game_image,
        r1.name as current_rank,
        r1.image as current_rank_image,
        r2.name as target_rank,
        r2.image as target_rank_image
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users b ON o.booster_id = b.id
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN ranks r1 ON o.current_rank_id = r1.id
        LEFT JOIN ranks r2 ON o.target_rank_id = r2.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Siparişler yüklenirken bir hata oluştu.";
    $orders = [];
}

// Sipariş istatistiklerini getir
try {
    // Toplam sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();
    
    // Bekleyen sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetchColumn();
    
    // Devam eden sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'in_progress'");
    $in_progress_orders = $stmt->fetchColumn();
    
    // Tamamlanan sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
    $completed_orders = $stmt->fetchColumn();
    
    // İptal edilen sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'");
    $cancelled_orders = $stmt->fetchColumn();
    
    // Toplam kazanç
    $stmt = $conn->query("SELECT SUM(price) FROM orders WHERE status != 'cancelled'");
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
    // Son 7 gündeki siparişler
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $orders_last_week = $stmt->fetchColumn();
    
    // Aylık sipariş istatistikleri (son 6 ay)
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(price) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sipariş ayları, sayıları ve gelirleri için diziler oluştur
    $order_months = [];
    $order_counts = [];
    $order_revenues = [];
    
    foreach ($monthly_orders as $data) {
        $date = new DateTime($data['month'] . '-01');
        $order_months[] = $date->format('M Y');
        $order_counts[] = $data['count'];
        $order_revenues[] = $data['revenue'];
    }
    
    // Sipariş durumu dağılımı
    $order_status_data = [
        'pending' => $pending_orders,
        'in_progress' => $in_progress_orders,
        'completed' => $completed_orders,
        'cancelled' => $cancelled_orders
    ];
    
} catch(PDOException $e) {
    $_SESSION['error'] = "İstatistikler yüklenirken bir hata oluştu.";
}

// Sipariş durumu için renk sınıfları
function getStatusClass($status) {
    switch($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Sipariş durumu için Türkçe metinler
function getStatusText($status) {
    switch($status) {
        case 'pending':
            return 'Beklemede';
        case 'in_progress':
            return 'Devam Ediyor';
        case 'completed':
            return 'Tamamlandı';
        case 'cancelled':
            return 'İptal Edildi';
        default:
            return 'Bilinmiyor';
    }
}
?>

<div class="container-fluid py-4">
    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
        <!-- Toplam Sipariş -->
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon">
                    <i class="mdi mdi-cart"></i>
                </div>
                <h4 class="mb-2">Toplam Sipariş</h4>
                <h2 class="mb-0"><?php echo number_format($total_orders); ?></h2>
                <div class="mt-3">
                    <span class="badge bg-success">
                        <i class="mdi mdi-check me-1"></i><?php echo number_format($completed_orders); ?> Tamamlandı
                    </span>
                </div>
            </div>
        </div>

        <!-- Bekleyen Siparişler -->
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon" style="background: linear-gradient(45deg, #ffa726, #fb8c00);">
                    <i class="mdi mdi-clock"></i>
                </div>
                <h4 class="mb-2">Bekleyen Sipariş</h4>
                <h2 class="mb-0"><?php echo number_format($pending_orders); ?></h2>
                <div class="mt-3">
                    <span class="badge bg-warning">
                        <i class="mdi mdi-clock-outline me-1"></i>İşlem Bekliyor
                    </span>
                </div>
            </div>
        </div>

        <!-- Devam Eden Siparişler -->
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon" style="background: linear-gradient(45deg, #29b6f6, #0288d1);">
                    <i class="mdi mdi-progress-clock"></i>
                </div>
                <h4 class="mb-2">Devam Eden</h4>
                <h2 class="mb-0"><?php echo number_format($in_progress_orders); ?></h2>
                <div class="mt-3">
                    <span class="badge bg-info">
                        <i class="mdi mdi-trending-up me-1"></i>Süreç Devam Ediyor
                    </span>
                </div>
            </div>
        </div>

        <!-- Toplam Kazanç -->
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon" style="background: linear-gradient(45deg, #66bb6a, #43a047);">
                    <i class="mdi mdi-cash-multiple"></i>
                </div>
                <h4 class="mb-2">Toplam Kazanç</h4>
                <h2 class="mb-0"><?php echo number_format($total_revenue, 2, ',', '.'); ?> ₺</h2>
                <div class="mt-3">
                    <span class="badge bg-success">
                        <i class="mdi mdi-chart-line me-1"></i>Gelir
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparişler Tablosu -->
    <div class="card fade-in">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="mdi mdi-cart-outline me-2"></i>Tüm Siparişler
            </h5>
            <div>
                <button class="btn btn-primary btn-sm me-2" id="refreshData">
                    <i class="mdi mdi-refresh me-1"></i>Yenile
                </button>
                <a href="add_order.php" class="btn btn-success btn-sm">
                    <i class="mdi mdi-plus me-1"></i>Yeni Sipariş
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Oyun</th>
                            <th>Müşteri</th>
                            <th>Booster</th>
                            <th>Rank Bilgisi</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($order['game_image']): ?>
                                        <img src="/<?php echo htmlspecialchars($order['game_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['game_name']); ?>"
                                             class="me-2" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div class="me-2 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded" 
                                             style="width: 32px; height: 32px;">
                                            <i class="mdi mdi-gamepad-variant text-primary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($order['game_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($order['user_username']); ?>&background=random" 
                                         class="rounded-circle me-2" width="32" height="32">
                                    <?php echo htmlspecialchars($order['user_username']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($order['booster_username']): ?>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($order['booster_username']); ?>&background=random" 
                                             class="rounded-circle me-2" width="32" height="32">
                                        <?php echo htmlspecialchars($order['booster_username']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning">Atanmadı</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($order['current_rank_image']): ?>
                                        <img src="/<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['current_rank']); ?>"
                                             class="me-2" style="width: 32px; height: 32px; object-fit: contain;">
                                    <?php endif; ?>
                                    <i class="mdi mdi-arrow-right mx-2"></i>
                                    <?php if ($order['target_rank_image']): ?>
                                        <img src="/<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['target_rank']); ?>"
                                             class="me-2" style="width: 32px; height: 32px; object-fit: contain;">
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="text-success fw-bold">
                                    <?php echo number_format($order['price'], 2, ',', '.'); ?> ₺
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getStatusClass($order['status']); ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm"
                                            onclick="editOrder(<?php echo $order['id']; ?>)">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-success btn-sm"
                                                onclick="assignBooster(<?php echo $order['id']; ?>)">
                                            <i class="mdi mdi-account-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* İstatistik Kartları */
.stats-card {
    background: rgba(26, 27, 58, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 1.5rem;
    height: 100%;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.stats-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-bottom: 1rem;
}

/* Tablo Stilleri */
.table {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0;
}

.table thead th {
    background: rgba(0, 0, 0, 0.2);
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.table td {
    vertical-align: middle;
    border-color: rgba(255, 255, 255, 0.1);
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* Badge Stilleri */
.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

/* Button Stilleri */
.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 1px;
}

.btn-group .btn i {
    font-size: 1rem;
}

/* Animasyonlar */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* DataTables Özelleştirmeleri */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    color: rgba(255, 255, 255, 0.8) !important;
    margin: 1rem 0;
}

.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
    border-radius: 0.5rem;
    padding: 0.375rem 0.75rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
    border-radius: 0.5rem;
    margin: 0 2px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #fff !important;
}
</style>

<script>
$(document).ready(function() {
    // DataTables başlat
    $('#ordersTable').DataTable({
        order: [[7, 'desc']], // Tarihe göre sırala
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        }
    });

    // Yenile butonu
    $('#refreshData').click(function() {
        location.reload();
    });
});

// Sipariş detaylarını görüntüle
function viewOrderDetails(orderId) {
    window.location.href = 'order.php?id=' + orderId;
}

// Sipariş düzenle
function editOrder(orderId) {
    window.location.href = 'edit_order.php?id=' + orderId;
}

// Booster ata
function assignBooster(orderId) {
    window.location.href = 'assign_booster.php?id=' + orderId;
}
</script>

<?php require_once 'includes/footer.php'; ?>