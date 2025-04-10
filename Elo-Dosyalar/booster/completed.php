<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece boosterların erişimine izin ver
if (!isBooster()) {
    redirect('../login.php');
}

// Tamamlanan siparişleri getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               g.name as game_name, g.image as game_image,
               u.username as user_username, u.email as user_email,
               cr.name as current_rank, cr.image as current_rank_image,
               tr.name as target_rank, tr.image as target_rank_image,
               bp.amount as booster_amount,
               bp.status as payment_status
        FROM orders o
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN ranks cr ON o.current_rank_id = cr.id
        LEFT JOIN ranks tr ON o.target_rank_id = tr.id
        LEFT JOIN booster_payments bp ON o.id = bp.order_id
        WHERE o.booster_id = ? AND o.status = 'completed'
        ORDER BY o.completed_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Son 6 ayın kazanç grafiği için veri
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(o.completed_at, '%Y-%m') as month,
            SUM(bp.amount) as earnings
        FROM orders o
        JOIN booster_payments bp ON o.id = bp.order_id
        WHERE o.booster_id = ? AND o.status = 'completed'
        AND o.completed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(o.completed_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $earnings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Oyun dağılımı grafiği için veri
    $stmt = $conn->prepare("
        SELECT 
            g.name as game_name,
            COUNT(*) as order_count
        FROM orders o
        JOIN games g ON o.game_id = g.id
        WHERE o.booster_id = ? AND o.status = 'completed'
        GROUP BY g.name
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $games_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Siparişler getirilirken bir hata oluştu.";
    $orders = [];
    $earnings_data = [];
    $games_data = [];
}

// Toplam kazanç ve bekleyen ödeme hesapla
$total_earnings = 0;
$pending_payments = 0;
$completed_count = 0;

foreach ($orders as $order) {
    $completed_count++;
    if ($order['payment_status'] === 'completed') {
        $total_earnings += $order['booster_amount'];
    } else {
        $pending_payments += $order['booster_amount'];
    }
}

// Grafik verilerini hazırla
$months = [];
$earnings = [];

// Son 6 ayı doldur (veri yoksa 0 olarak)
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    $months[] = $month_name;
    $earnings[$month] = 0;
}

// Veritabanından gelen verileri ekle
foreach ($earnings_data as $data) {
    $earnings[$data['month']] = (float)$data['earnings'];
}

// Grafik için final diziyi oluştur
$earnings_values = array_values($earnings);

// Oyun dağılımı için verileri hazırla
$game_names = [];
$game_counts = [];

foreach ($games_data as $data) {
    $game_names[] = $data['game_name'];
    $game_counts[] = (int)$data['order_count'];
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Başlık ve Filtreler -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tamamlanan Siparişler</h1>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="refreshOrders">
                <i class="fas fa-sync-alt me-1"></i> Yenile
            </button>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Tamamlanan Siparişler</h6>
                            <h2 class="mb-0 text-primary"><?php echo $completed_count; ?></h2>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-check-circle text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Toplam tamamladığınız sipariş sayısı
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Toplam Kazanç</h6>
                            <h2 class="mb-0 text-success"><?php echo number_format($total_earnings, 2, ',', '.'); ?> ₺</h2>
                        </div>
                        <div class="icon-circle bg-success bg-opacity-10">
                            <i class="fas fa-coins text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Ödemesi tamamlanan siparişlerden kazancınız
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Bekleyen Ödemeler</h6>
                            <h2 class="mb-0 text-warning"><?php echo number_format($pending_payments, 2, ',', '.'); ?> ₺</h2>
                        </div>
                        <div class="icon-circle bg-warning bg-opacity-10">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-muted">
                            <i class="fas fa-info-circle me-1"></i> Henüz ödenmemiş siparişlerden alacağınız
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-1">Ortalama Sipariş Değeri</h6>
                            <h2 class="mb-0 text-info">
                                <?php 
                                    $avg = $completed_count > 0 ? ($total_earnings + $pending_payments) / $completed_count : 0;
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
                            <i class="fas fa-info-circle me-1"></i> Tamamlanan siparişlerinizin ortalama değeri
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Grafikler -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0">Aylık Kazanç Grafiği</h5>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0">Oyun Dağılımı</h5>
                </div>
                <div class="card-body">
                    <canvas id="gamesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparişler Tablosu -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i> Tamamlanan Siparişler
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item filter-btn" data-filter="all" href="#">Tümü</a></li>
                        <li><a class="dropdown-item filter-btn" data-filter="paid" href="#">Ödenmiş</a></li>
                        <li><a class="dropdown-item filter-btn" data-filter="pending" href="#">Beklemede</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <img src="../assets/img/no-data.svg" alt="Sipariş Yok" class="mb-3" style="width: 200px; opacity: 0.7;">
                    <h4 class="text-muted">Tamamlanan Sipariş Bulunmuyor</h4>
                    <p class="text-muted">Henüz tamamladığınız bir sipariş bulunmamaktadır.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="completedOrdersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Sipariş No</th>
                                <th>Oyun</th>
                                <th>Müşteri</th>
                                <th>Rank Bilgisi</th>
                                <th>Kazanç</th>
                                <th>Ödeme Durumu</th>
                                <th>Tamamlanma Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr class="order-row <?php echo $order['payment_status'] === 'completed' ? 'paid-order' : 'pending-order'; ?>">
                                    <td>
                                        <span class="fw-bold">#<?php echo $order['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($order['game_image'])): ?>
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
                                                <?php if (!empty($order['user_email'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($order['current_rank_image'])): ?>
                                                <div class="rank-badge me-2">
                                                    <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                         alt="" class="img-fluid" style="width: 32px; height: 32px;">
                                                </div>
                                            <?php else: ?>
                                                <span class="me-2"><?php echo htmlspecialchars($order['current_rank']); ?></span>
                                            <?php endif; ?>
                                            <i class="fas fa-arrow-right text-muted mx-2"></i>
                                            <?php if (!empty($order['target_rank_image'])): ?>
                                                <div class="rank-badge">
                                                    <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                         alt="" class="img-fluid" style="width: 32px; height: 32px;">
                                                </div>
                                            <?php else: ?>
                                                <span><?php echo htmlspecialchars($order['target_rank']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success">
                                            <?php 
                                                $amount = isset($order['booster_amount']) && $order['booster_amount'] !== null ? $order['booster_amount'] : 0;
                                                echo number_format($amount, 2, ',', '.'); 
                                            ?> ₺
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($order['payment_status'] === 'completed'): ?>
                                            <span class="badge bg-success">Ödendi</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Beklemede</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="d-block"><?php echo date('d.m.Y', strtotime($order['completed_at'])); ?></span>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($order['completed_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> Detay
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

.order-row {
    transition: all 0.2s;
}

.order-row:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.order-row.paid-order {
    border-left: 3px solid #1cc88a;
}

.order-row.pending-order {
    border-left: 3px solid #f6c23e;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // DataTables'ı başlat
    const ordersTable = $('#completedOrdersTable').DataTable({
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
        } else if (filter === 'paid') {
            ordersTable.search('paid-order').draw();
        } else if (filter === 'pending') {
            ordersTable.search('pending-order').draw();
        }
    });
    
    // Aylık Kazanç Grafiği
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    const earningsChart = new Chart(earningsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Aylık Kazanç (₺)',
                data: <?php echo json_encode($earnings_values); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: '#4e73df',
                borderWidth: 2,
                pointBackgroundColor: '#4e73df',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#5a5c69',
                    bodyColor: '#5a5c69',
                    borderColor: '#e3e6f0',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(2) + ' ₺';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' ₺';
                        }
                    }
                }
            }
        }
    });
    
    // Oyun Dağılımı Grafiği
    const gamesCtx = document.getElementById('gamesChart').getContext('2d');
    const gamesChart = new Chart(gamesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($game_names); ?>,
            datasets: [{
                data: <?php echo json_encode($game_counts); ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                ],
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#5a5c69',
                    bodyColor: '#5a5c69',
                    borderColor: '#e3e6f0',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' sipariş';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 