<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece boosterların erişimine izin ver
if (!isBooster()) {
    redirect('../login.php');
}

// Aktif siparişleri getir
try {
    // Hata ayıklama için SQL sorgusunu yazdır
    $sql = "
        SELECT o.*, 
               g.name as game_name, g.image as game_image,
               u.username as user_username, u.email as user_email,
               cr.name as current_rank, cr.image as current_rank_image,
               tr.name as target_rank, tr.image as target_rank_image,
               0 as unread_messages
        FROM orders o
        JOIN games g ON o.game_id = g.id
        JOIN users u ON o.user_id = u.id
        LEFT JOIN ranks cr ON o.current_rank_id = cr.id
        LEFT JOIN ranks tr ON o.target_rank_id = tr.id
        WHERE o.booster_id = ? AND o.status = 'in_progress'
        ORDER BY o.created_at DESC
    ";
    
    // Sorguyu hazırla ve çalıştır
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Toplam sipariş sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE booster_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_orders = $stmt->fetchColumn();
    
    // Toplam kazanç potansiyeli
    $stmt = $conn->prepare("
        SELECT SUM(price) as total_potential
        FROM orders 
        WHERE booster_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_potential = $stmt->fetchColumn() ?: 0;
    
    // Komisyon oranını al (varsayılan %20)
    $commission_rate = (float)getSetting('commission_rate') ?: 20;
    // Booster'ın alacağı oran (100 - komisyon)
    $booster_rate = 100 - $commission_rate;
    // Potansiyel kazancı hesapla
    $total_potential = $total_potential * $booster_rate / 100;
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Siparişler getirilirken bir hata oluştu: " . $e->getMessage();
    $orders = [];
    $total_orders = 0;
    $total_potential = 0;
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Başlık ve Filtreler -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Aktif Siparişlerim</h1>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="refreshOrders">
                <i class="fas fa-sync-alt me-1"></i> Yenile
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Aktif Siparişler</h6>
                            <h2 class="mb-0 text-primary"><?php echo $total_orders; ?></h2>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-tasks text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Şu anda üzerinde çalıştığınız siparişler
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Potansiyel Kazanç</h6>
                            <h2 class="mb-0 text-success"><?php echo number_format($total_potential, 2, ',', '.'); ?> ₺</h2>
                        </div>
                        <div class="icon-circle bg-success bg-opacity-10">
                            <i class="fas fa-coins text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Tüm aktif siparişleri tamamladığınızda kazanacağınız tutar
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Ortalama Sipariş Değeri</h6>
                            <h2 class="mb-0 text-info">
                                <?php 
                                    $avg = $total_orders > 0 ? $total_potential / $total_orders : 0;
                                    echo number_format($avg, 2, ',', '.'); 
                                ?> ₺
                            </h2>
                        </div>
                        <div class="icon-circle bg-info bg-opacity-10">
                            <i class="fas fa-chart-line text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Aktif siparişlerinizin ortalama değeri
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparişler Tablosu -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i> Aktif Siparişler
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item filter-btn" data-filter="all" href="#">Tümü</a></li>
                        <li><a class="dropdown-item filter-btn" data-filter="message" href="#">Yeni Mesaj Olanlar</a></li>
                        <li><a class="dropdown-item filter-btn" data-filter="progress" href="#">İlerleme Kaydettiğim</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <img src="../assets/img/no-data.svg" alt="Sipariş Yok" class="mb-3" style="width: 200px; opacity: 0.7;">
                    <h4 class="text-muted">Aktif Sipariş Bulunmuyor</h4>
                    <p class="text-muted">Şu anda size atanmış aktif bir sipariş bulunmamaktadır.</p>
                    <p class="text-muted">Veritabanında <?php echo $total_orders; ?> aktif sipariş var, ancak görüntülenemiyor.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="ordersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Sipariş No</th>
                                <th>Oyun</th>
                                <th>Müşteri</th>
                                <th>Rank Bilgisi</th>
                                <th>İlerleme</th>
                                <th>Kazanç</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr class="order-row <?php echo $order['unread_messages'] > 0 ? 'has-message' : ''; ?> <?php echo $order['progress'] > 0 ? 'has-progress' : ''; ?>">
                                    <td>
                                        <span class="fw-bold">#<?php echo $order['id']; ?></span>
                                        <?php if ($order['unread_messages'] > 0): ?>
                                            <span class="badge bg-danger ms-1"><?php echo $order['unread_messages']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($order['game_image']): ?>
                                                <div class="avatar-sm me-2">
                                                    <img src="../<?php echo htmlspecialchars($order['game_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($order['game_name']); ?>"
                                                         class="img-fluid rounded">
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($order['game_name']); ?></div>
                                                <?php if (!empty($order['server'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['server']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($order['user_username']); ?>&background=random" 
                                                     class="img-fluid rounded-circle">
                                            </div>
                                            <div>
                                                <span class="d-block"><?php echo htmlspecialchars($order['user_username']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($order['current_rank_image']): ?>
                                                <div class="rank-badge me-2">
                                                    <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                         alt="" class="img-fluid" style="width: 32px; height: 32px;">
                                                </div>
                                            <?php endif; ?>
                                            <i class="fas fa-arrow-right text-muted mx-2"></i>
                                            <?php if ($order['target_rank_image']): ?>
                                                <div class="rank-badge">
                                                    <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                         alt="" class="img-fluid" style="width: 32px; height: 32px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="width: 150px;">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $order['progress']; ?>%"
                                                 aria-valuenow="<?php echo $order['progress']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $order['progress']; ?>% Tamamlandı</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success">
                                            <?php 
                                                $booster_amount = $order['price'] * $booster_rate / 100;
                                                echo number_format($booster_amount, 2, ',', '.'); 
                                            ?> ₺
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="d-block"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></span>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="order.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i> Detay
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="completeOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
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

<style>
.icon-circle {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 20px;
}

.avatar-sm {
    width: 36px;
    height: 36px;
    overflow: hidden;
}

.rank-badge img {
    border-radius: 50%;
    padding: 2px;
    background: #f8f9fc;
    border: 2px solid #e3e6f0;
    transition: all 0.2s;
}

.rank-badge img:hover {
    transform: scale(1.1);
    border-color: #4e73df;
}

.progress {
    border-radius: 10px;
    background-color: #f0f0f0;
}

.progress-bar {
    border-radius: 10px;
}

.table {
    color: rgba(255, 255, 255, 0.8) !important;
}

.table thead th {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    color: #fff !important;
    font-weight: 600;
    padding: 1rem;
    background: transparent !important;
}

.table td {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem;
    vertical-align: middle;
    background: transparent !important;
    color: #fff !important;
}

.table td .fw-bold,
.table td .d-block,
.table td small {
    color: #fff !important;
}

.table td .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

.table-hover tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
}

/* DataTables özelleştirmeleri */
.dataTables_wrapper .table {
    background: transparent !important;
}

.dataTables_wrapper .table thead th,
.dataTables_wrapper .table tbody td {
    background: transparent !important;
    color: #fff !important;
}

.dataTables_info,
.dataTables_length,
.dataTables_filter {
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
    color: #fff;
}

/* Metin Renkleri */
.text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

.text-white {
    color: #fff !important;
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
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // DataTables'ı başlat
    const ordersTable = $('#ordersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        },
        order: [[6, 'desc']],
        pageLength: 10,
        responsive: true
    });
    
    // Yenile butonu
    $('#refreshOrders').on('click', function() {
        window.location.reload();
    });
    
    // Filtreler
    $('.filter-btn').on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        
        if (filter === 'all') {
            ordersTable.search('').draw();
        } else if (filter === 'message') {
            ordersTable.search('has-message').draw();
        } else if (filter === 'progress') {
            ordersTable.search('has-progress').draw();
        }
    });
});

function completeOrder(orderId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu siparişi tamamlamak istediğinize emin misiniz?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1cc88a',
        cancelButtonColor: '#e74a3b',
        confirmButtonText: 'Evet, Tamamla',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'complete_order.php?id=' + orderId;
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 