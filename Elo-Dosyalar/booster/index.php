<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Varsayılan değerleri tanımla
$active_orders = 0;
$completed_orders = 0;
$total_earnings = 0;
$monthly_earnings = 0;
$pending_balance = 0;
$withdrawn_balance = 0;
$active_orders_list = [];
$earnings_data = [];
$games_data = [];
$months = [];
$earnings = [];
$game_names = [];
$game_counts = [];

$booster_id = $_SESSION['user_id'];

// İstatistikleri getir
try {
    // Veritabanı bağlantısını kontrol et
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Veritabanı bağlantısı bulunamadı!");
    }

    // Booster bakiye bilgilerini getir
    $stmt = $conn->prepare("
        SELECT pending_balance, withdrawn_balance
        FROM boosters
        WHERE user_id = ?
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Booster bakiye bilgileri sorgusu başarısız oldu!");
    }
    $balance_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($balance_data) {
        $pending_balance = $balance_data['pending_balance'];
        $withdrawn_balance = $balance_data['withdrawn_balance'];
    }

    // Toplam kazanç
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(booster_earnings), 0) as total_earnings
        FROM orders
        WHERE booster_id = ? AND status = 'completed'
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Toplam kazanç sorgusu başarısız oldu!");
    }
    $total_earnings = $stmt->fetchColumn();

    // Aylık kazanç
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(booster_earnings), 0) as monthly_earnings
        FROM orders
        WHERE booster_id = ? 
        AND status = 'completed'
        AND MONTH(completed_at) = MONTH(CURRENT_DATE())
        AND YEAR(completed_at) = YEAR(CURRENT_DATE())
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Aylık kazanç sorgusu başarısız oldu!");
    }
    $monthly_earnings = $stmt->fetchColumn();

    // Aktif sipariş sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) as active_orders
        FROM orders
        WHERE booster_id = ? AND status = 'in_progress'
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Aktif sipariş sayısı sorgusu başarısız oldu!");
    }
    $active_orders = $stmt->fetchColumn();

    // Tamamlanan sipariş sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed_orders
        FROM orders
        WHERE booster_id = ? AND status = 'completed'
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Tamamlanan sipariş sayısı sorgusu başarısız oldu!");
    }
    $completed_orders = $stmt->fetchColumn();

    // Son 6 ayın kazanç verileri
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(completed_at, '%Y-%m') as month,
            COALESCE(SUM(booster_earnings), 0) as earnings
        FROM orders
        WHERE booster_id = ? 
        AND status = 'completed'
        AND completed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(completed_at, '%Y-%m')
        ORDER BY month DESC
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Aylık kazanç verileri sorgusu başarısız oldu!");
    }
    $earnings_data = $stmt->fetchAll();
    
    // Komisyon oranını al (varsayılan %20)
    $commission_rate = (float)getSetting('commission_rate') ?: 20;
    // Booster'ın alacağı oran (100 - komisyon)
    $booster_rate = 100 - $commission_rate;
    
    // Kazançları hesapla
    $total_earnings = $total_earnings * $booster_rate / 100;
    $monthly_earnings = $monthly_earnings * $booster_rate / 100;

    // Kazanç verilerini booster oranına göre düzelt
    foreach ($earnings_data as &$data) {
        $data['earnings'] = (float)$data['earnings'] * $booster_rate / 100;
    }

    // Oyun dağılımı grafiği için veri
    $stmt = $conn->prepare("
        SELECT 
            g.name as game_name,
            COUNT(*) as order_count
        FROM orders o
        JOIN games g ON o.game_id = g.id
        WHERE o.booster_id = ? AND (o.status = 'completed' OR o.status = 'in_progress')
        GROUP BY g.name
        ORDER BY order_count DESC
        LIMIT 5
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Oyun dağılımı sorgusu başarısız oldu!");
    }
    $games_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aktif siparişleri getir
    $stmt = $conn->prepare("
        SELECT o.*, g.name as game_name, g.image as game_image,
               u.username as customer_name, u.email as customer_email,
               r1.name as current_rank_name, r1.image as current_rank_image,
               r2.name as target_rank_name, r2.image as target_rank_image,
               0 as unread_messages
        FROM orders o
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN ranks r1 ON o.current_rank_id = r1.id
        LEFT JOIN ranks r2 ON o.target_rank_id = r2.id
        WHERE o.booster_id = ? AND o.status = 'in_progress'
        ORDER BY o.created_at DESC
    ");
    if (!$stmt->execute([$booster_id])) {
        throw new Exception("Aktif siparişler sorgusu başarısız oldu!");
    }
    $active_orders_list = $stmt->fetchAll();

} catch(Exception $e) {
    error_log("Booster Panel Hatası: " . $e->getMessage());
    $_SESSION['error'] = "Veriler yüklenirken bir hata oluştu: " . $e->getMessage();
}

// Grafik verilerini hazırla
// Son 6 ayı doldur (veri yoksa 0 olarak)
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    $months[] = $month_name;
    $earnings[$month] = 0;
}

// Veritabanından gelen verileri ekle
if (!empty($earnings_data)) {
    foreach ($earnings_data as $data) {
        $earnings[$data['month']] = (float)$data['earnings'];
    }
}

// Grafik için final diziyi oluştur
$earnings_values = array_values($earnings);

// Oyun dağılımı için verileri hazırla
if (!empty($games_data)) {
    foreach ($games_data as $data) {
        $game_names[] = $data['game_name'];
        $game_counts[] = (int)$data['order_count'];
    }
}

require_once 'includes/header.php';

// Booster bilgilerini getir
$stmt = $conn->prepare("
    SELECT b.*, u.username,
           (SELECT COUNT(*) FROM orders WHERE booster_id = b.id) as total_orders,
           (SELECT COUNT(*) FROM orders WHERE booster_id = b.id AND status = 'completed') as completed_orders,
           (SELECT COUNT(*) FROM orders WHERE booster_id = b.id AND status = 'in_progress') as active_orders
    FROM boosters b
    JOIN users u ON b.user_id = u.id
    WHERE b.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$booster = $stmt->fetch(PDO::FETCH_ASSOC);

// Son siparişleri getir
$stmt = $conn->prepare("
    SELECT o.*, g.name as game_name, u.username as customer_name
    FROM orders o
    JOIN games g ON o.game_id = g.id
    JOIN users u ON o.user_id = u.id
    WHERE o.booster_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$booster['id']]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <!-- Ana Kart -->
    <div class="row">
        <div class="col-xxl-6">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex flex-column h-100">
                                <div class="flex-grow-1">
                                    <h3 class="fw-medium text-capitalize mt-0 mb-2 text-glow">Hoş Geldin, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                                    <p class="font-18 text-white">Bugün <?php echo $active_orders; ?> aktif siparişin var. Bu ay şu ana kadar <?php echo number_format($monthly_earnings, 2, ',', '.'); ?> ₺ kazandın.</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="row h-100">
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect glow-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Aktif Siparişler</h4>
                                                        <a class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center" href="orders.php">
                                                            <i class="mdi mdi-arrow-top-right"></i>
                                                        </a>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo $active_orders; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="card border-0 glass-effect glow-effect mb-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="mt-0 mb-0 text-white">Aylık Kazanç</h4>
                                                        <a class="avatar-xs bg-glow rounded-circle font-18 d-flex text-white align-items-center justify-content-center" href="earnings.php">
                                                            <i class="mdi mdi-arrow-top-right"></i>
                                                        </a>
                                                    </div>
                                                    <h2 class="mb-0 text-glow"><?php echo number_format($monthly_earnings, 2, ',', '.'); ?> ₺</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-center h-100 w-100 mt-4 mt-md-0">
                                <img alt="Booster" class="img-fluid floating-image" src="../theme/assets/hero-dashboard.png" style="max-height: 280px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="row">
                <!-- İstatistik Kartları -->
                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Tamamlanan Siparişler</h4>
                                    <p class="mb-2 text-white">Toplam <?php echo $completed_orders; ?> sipariş tamamladın</p>
                                    <a href="completed.php" class="btn btn-glow btn-primary btn-sm">Detayları Gör</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg bg-glow rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="mdi mdi-check-circle font-24 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 glass-effect hover-effect" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-8">
                                    <h4 class="my-0 text-white">Toplam Kazanç</h4>
                                    <p class="mb-2 text-white"><?php echo number_format($total_earnings, 2, ',', '.'); ?> ₺ kazandın</p>
                                    <a href="earnings.php" class="btn btn-glow btn-success btn-sm">Kazanç Detayları</a>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="avatar-lg bg-glow rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="mdi mdi-currency-try font-24 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="row mt-4">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3">Aylık Kazanç Grafiği</h5>
                    <div style="height: 300px;">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3">Oyun Dağılımı</h5>
                    <div style="height: 300px;">
                        <canvas id="gamesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktif Siparişler -->
    <div class="card border-0 glass-effect" style="border-radius: 20px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-white mb-0">Aktif Siparişler</h5>
                <button class="btn btn-glow btn-primary btn-sm" onclick="window.location.reload()">
                    <i class="mdi mdi-refresh me-1"></i> Yenile
                </button>
            </div>

            <?php if (!empty($active_orders_list)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead>
                            <tr>
                                <th class="text-white">Sipariş No</th>
                                <th class="text-white">Oyun</th>
                                <th class="text-white">Müşteri</th>
                                <th class="text-white">Rank Bilgisi</th>
                                <th class="text-white">İlerleme</th>
                                <th class="text-white">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_orders_list as $order): ?>
                                <tr>
                                    <td>
                                        <span class="text-white">#<?php echo $order['id']; ?></span>
                                        <?php if ($order['unread_messages'] > 0): ?>
                                            <span class="badge bg-danger ms-1"><?php echo $order['unread_messages']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($order['game_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($order['game_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($order['game_name']); ?>"
                                                     class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                            <?php endif; ?>
                                            <span class="text-white"><?php echo htmlspecialchars($order['game_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($order['customer_name']); ?>&background=random" 
                                                 class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                            <div>
                                                <span class="text-white d-block"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($order['current_rank_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($order['current_rank_name']); ?>"
                                                     class="me-2" style="height: 24px;">
                                            <?php endif; ?>
                                            <i class="mdi mdi-arrow-right text-muted mx-2"></i>
                                            <?php if ($order['target_rank_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($order['target_rank_name']); ?>"
                                                     class="ms-2" style="height: 24px;">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="width: 200px;">
                                        <div class="progress bg-dark" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $order['progress'] ?? 0; ?>%;" 
                                                 aria-valuenow="<?php echo $order['progress'] ?? 0; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-white">%<?php echo $order['progress'] ?? 0; ?> Tamamlandı</small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="order.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-glow btn-primary btn-sm">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-glow btn-success btn-sm"
                                                    onclick="completeOrder(<?php echo $order['id']; ?>)">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <img src="../assets/img/illustrations/no-data.svg" alt="Veri Yok" class="mb-3" style="height: 150px;">
                    <h5 class="text-white">Aktif Siparişiniz Bulunmuyor</h5>
                    <p class="text-white">Şu anda size atanmış aktif bir sipariş bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Aylık Kazanç Grafiği
const earningsCtx = document.getElementById('earningsChart').getContext('2d');
new Chart(earningsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Aylık Kazanç (₺)',
            data: <?php echo json_encode($earnings_values); ?>,
            backgroundColor: 'rgba(106, 17, 203, 0.1)',
            borderColor: '#6a11cb',
            borderWidth: 2,
            pointBackgroundColor: '#6a11cb',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
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
                backgroundColor: 'rgba(26, 27, 58, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
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
                    display: false,
                    color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                    color: 'rgba(255, 255, 255, 0.7)'
                }
            },
            y: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                    color: 'rgba(255, 255, 255, 0.7)',
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
new Chart(gamesCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($game_names); ?>,
        datasets: [{
            data: <?php echo json_encode($game_counts); ?>,
            backgroundColor: [
                '#6a11cb',
                '#2575fc',
                '#00f3ff',
                '#9d4edd',
                '#ff6b6b'
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
                    color: 'rgba(255, 255, 255, 0.7)',
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(26, 27, 58, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
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

function completeOrder(orderId) {
    Swal.fire({
        title: 'Siparişi Tamamla',
        text: "Bu siparişi tamamlamak istediğinize emin misiniz?",
        icon: 'question',
        background: 'rgba(26, 27, 58, 0.95)',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: '#2575fc',
        cancelButtonColor: '#ff6b6b',
        confirmButtonText: 'Evet, Tamamla',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'complete_order.php?id=' + orderId;
        }
    });
}
</script>

<style>
/* Genel Tema */
.techui-content {
    background-color: var(--dark-bg);
    min-height: 100vh;
    padding: 1.5rem;
}

/* Cam Efekti */
.glass-effect {
    background: rgba(26, 27, 58, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Hover Efekti */
.hover-effect {
    transition: all 0.3s ease;
}

.hover-effect:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 30px rgba(106, 17, 203, 0.4);
}

/* Parlama Efekti */
.text-glow {
    color: #fff;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.bg-glow {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

/* Avatar */
.avatar-lg {
    width: 48px;
    height: 48px;
}

/* Butonlar */
.btn-glow {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: white;
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
    transition: all 0.3s ease;
    border-radius: 12px;
    font-weight: 500;
}

.btn-glow:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 30px rgba(106, 17, 203, 0.6);
    color: white;
}

/* Progress Bar */
.progress {
    overflow: hidden;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.progress-bar {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 10px;
}

/* Tablo */
.table {
    color: rgba(255, 255, 255, 0.8) !important;
}

.table thead th {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 600;
    padding: 1rem;
    background: transparent !important;
}

.table td {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem;
    vertical-align: middle;
    background: transparent !important;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

.table-hover tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff;
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

/* Floating Animation */
.floating-image {
    animation: floating 3s ease-in-out infinite;
}

@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

/* Özel Efektler */
.bg-dark-gradient {
    background: linear-gradient(135deg, var(--dark-card), var(--dark-bg));
}

.card {
    border-radius: 20px !important;
    overflow: hidden;
}

.card-body {
    position: relative;
    z-index: 1;
}

.card-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(106, 17, 203, 0.1), rgba(37, 117, 252, 0.1));
    z-index: -1;
}

/* Breadcrumb */
.breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255, 255, 255, 0.3);
}

.breadcrumb-item a {
    color: var(--neon-blue);
    text-decoration: none;
    transition: all 0.3s ease;
}

.breadcrumb-item a:hover {
    color: var(--neon-purple);
    text-shadow: 0 0 10px rgba(157, 78, 221, 0.6);
}

/* Grafik Stilleri */
#earningsChart, #gamesChart {
    filter: drop-shadow(0 0 10px rgba(106, 17, 203, 0.2));
}

/* Responsive */
@media (max-width: 768px) {
    .techui-content {
        padding: 1rem;
    }
}

/* Kart İçi Stiller */
.card {
    background: rgba(26, 27, 58, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

.card-body {
    position: relative;
    z-index: 1;
}

.card-title {
    color: #fff;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.card-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.stats-card {
    overflow: hidden;
    position: relative;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(106, 17, 203, 0.2), rgba(37, 117, 252, 0.2));
    z-index: 0;
}

/* İstatistik Değerleri */
.stat-value {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(to right, var(--neon-blue), var(--neon-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Tablo Stiller */
.table {
    color: rgba(255, 255, 255, 0.8) !important;
}

.table thead th {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 600;
    padding: 1rem;
    background: transparent !important;
}

.table td {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem;
    vertical-align: middle;
    background: transparent !important;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

.table-hover tbody tr:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff;
}

/* Progress Bar */
.progress {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    height: 8px;
}

.progress-bar {
    background: linear-gradient(to right, var(--neon-blue), var(--neon-purple));
    border-radius: 10px;
}

/* Butonlar */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
}

/* Breadcrumb */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 1rem 0;
}

.breadcrumb-item {
    color: rgba(255, 255, 255, 0.6);
}

.breadcrumb-item.active {
    color: #fff;
}

.breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255, 255, 255, 0.4);
}
</style>

<?php require_once 'includes/footer.php'; ?> 