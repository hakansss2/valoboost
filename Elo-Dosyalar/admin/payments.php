<?php
require_once 'includes/header.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    redirect('../login.php');
}

// Ödeme durumunu güncelle
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $new_status = isset($_POST['status']) ? clean($_POST['status']) : '';
    
    if ($payment_id > 0 && in_array($new_status, ['pending', 'completed', 'failed'])) {
        try {
            $conn->beginTransaction();

            // Ödeme bilgilerini al
            $stmt = $conn->prepare("SELECT user_id, amount FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                // Ödeme durumunu güncelle
                $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $payment_id]);

                // Bildirim gönder
                createPaymentStatusNotification($payment['user_id'], $payment_id, $new_status, $payment['amount']);

                // Eğer ödeme tamamlandıysa kullanıcının bakiyesini güncelle
                if ($new_status === 'completed') {
                    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$payment['amount'], $payment['user_id']]);
                    
                    // Bakiye bildirimi gönder
                    createBalanceNotification($payment['user_id'], $payment['amount'], 'add', 'Bakiye yükleme');
                }

                $conn->commit();
                $_SESSION['success'] = "Ödeme durumu başarıyla güncellendi.";
            }
        } catch(PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Ödeme durumu güncellenirken bir hata oluştu.";
        }
    }
    
    // JavaScript ile yönlendirme yapalım, header() kullanmak yerine
    echo '<script>window.location.href = "payments.php";</script>';
    exit;
}

// İstatistikleri getir
try {
    // Toplam ödeme sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM payments");
    $total_payments = $stmt->fetchColumn();
    
    // Toplam ödeme tutarı
    $stmt = $conn->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
    $total_amount = $stmt->fetchColumn() ?: 0;
    
    // Bekleyen ödeme sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
    $pending_payments = $stmt->fetchColumn();
    
    // Bekleyen ödeme tutarı
    $stmt = $conn->query("SELECT SUM(amount) FROM payments WHERE status = 'pending'");
    $pending_amount = $stmt->fetchColumn() ?: 0;
    
    // Ödeme yöntemlerine göre dağılım
    $stmt = $conn->query("
        SELECT payment_method, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE status = 'completed'
        GROUP BY payment_method
    ");
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ödeme yöntemleri için diziler
    $method_names = [];
    $method_counts = [];
    $method_amounts = [];
    
    foreach ($payment_methods as $method) {
        $method_names[] = ucfirst($method['payment_method']);
        $method_counts[] = $method['count'];
        $method_amounts[] = $method['total'];
    }
    
    // Son 6 aydaki aylık ödeme tutarları
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as total
        FROM payments
        WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aylık ödeme tutarları için diziler
    $payment_months = [];
    $monthly_amounts = [];
    
    foreach ($monthly_payments as $data) {
        $date = new DateTime($data['month'] . '-01');
        $payment_months[] = $date->format('M Y');
        $monthly_amounts[] = $data['total'];
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "İstatistikler yüklenirken bir hata oluştu.";
}

// Ödemeleri getir
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Ödemeler getirilirken bir hata oluştu.";
    $payments = [];
}

// Durum etiketleri ve renkleri
$status_labels = [
    'pending' => 'Beklemede',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi',
    'failed' => 'Başarısız'
];

$status_colors = [
    'pending' => 'warning',
    'completed' => 'success',
    'cancelled' => 'danger',
    'failed' => 'danger'
];

$payment_method_labels = [
    'credit_card' => 'Kredi Kartı',
    'bank_transfer' => 'Havale/EFT',
    'papara' => 'Papara',
    'paytr' => 'PayTR',
    'shopier' => 'Shopier'
];
?>

<!-- Başlık ve Butonlar -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-money-bill-wave fa-sm text-primary-300"></i>
        Ödeme Yönetimi
    </h1>
    <div class="d-flex">
        <button class="btn btn-sm btn-primary shadow-sm" id="refreshData">
            <i class="fas fa-sync-alt fa-sm text-white-50 me-1"></i> Yenile
        </button>
    </div>
</div>

<!-- Mesajlar -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row mb-4">
    <!-- Toplam Ödeme Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Toplam Ödeme
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_payments); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-check-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplam Tutar Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Toplam Tutar
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_amount, 2); ?> ₺</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lira-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekleyen Ödeme Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Bekleyen Ödeme
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($pending_payments); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekleyen Tutar Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Bekleyen Tutar
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($pending_amount, 2); ?> ₺
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafikler Satırı -->
<div class="row mb-4">
    <!-- Aylık Ödeme Grafiği -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Aylık Ödeme Tutarları</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlyPaymentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ödeme Yöntemleri Pasta Grafiği -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Ödeme Yöntemleri</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach (array_slice($method_names, 0, 5) as $index => $name): ?>
                        <span class="me-2">
                            <i class="fas fa-circle text-<?php echo ['primary', 'success', 'info', 'warning', 'danger'][$index % 5]; ?>"></i> <?php echo $name; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ödemeler Tablosu -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Ödeme Listesi</h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter fa-sm"></i> Filtrele
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                <li><a class="dropdown-item filter-status" data-status="all" href="#">Tümü</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item filter-status" data-status="Beklemede" href="#">Beklemede</a></li>
                <li><a class="dropdown-item filter-status" data-status="Tamamlandı" href="#">Tamamlandı</a></li>
                <li><a class="dropdown-item filter-status" data-status="Reddedildi" href="#">Reddedildi</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="paymentsTable" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı</th>
                        <th>Tutar</th>
                        <th>Yöntem</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr data-status="<?php echo $payment['status']; ?>">
                            <td><?php echo $payment['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar bg-primary me-2">
                                        <span><?php echo $payment['username'] ? strtoupper(substr($payment['username'], 0, 1)) : '?'; ?></span>
                                    </div>
                                    <div>
                                        <?php if ($payment['username']): ?>
                                            <div class="fw-bold"><?php echo htmlspecialchars($payment['username']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($payment['email']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">Kullanıcı Silinmiş</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold"><?php echo number_format($payment['amount'], 2); ?> ₺</span>
                            </td>
                            <td>
                                <span class="badge bg-info rounded-pill">
                                    <?php echo isset($payment_method_labels[$payment['payment_method']]) ? 
                                                        $payment_method_labels[$payment['payment_method']] : 
                                                        ucfirst($payment['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo isset($status_colors[$payment['status']]) ? 
                                                            $status_colors[$payment['status']] : 
                                                            'secondary'; ?> rounded-pill">
                                    <?php echo isset($status_labels[$payment['status']]) ? 
                                                        $status_labels[$payment['status']] : 
                                                        ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-edit"></i> Durum
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php foreach ($status_labels as $status_key => $status_label): ?>
                                            <?php if ($status_key !== $payment['status']): ?>
                                                <li>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo $status_key; ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-circle text-<?php echo $status_colors[$status_key]; ?> me-2"></i>
                                                            <?php echo $status_label; ?>
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // DataTables'ı başlat
    var paymentsTable = $('#paymentsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        },
        "order": [[5, "desc"]], // Tarihe göre sırala
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        "columnDefs": [
            { "orderable": false, "targets": 6 } // İşlemler sütununu sıralamadan hariç tut
        ]
    });
    
    // Durum filtreleme
    $('.filter-status').on('click', function(e) {
        e.preventDefault();
        var status = $(this).data('status');
        
        if (status === 'all') {
            paymentsTable.search('').columns(4).search('').draw();
        } else {
            paymentsTable.columns(4).search(status).draw();
        }
        
        $('#filterDropdown').text($(this).text());
    });
    
    // Aylık Ödeme Grafiği
    var ctx = document.getElementById('monthlyPaymentsChart').getContext('2d');
    var monthlyPaymentsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($payment_months); ?>,
            datasets: [{
                label: 'Ödeme Tutarı (₺)',
                data: <?php echo json_encode($monthly_amounts); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('tr-TR') + ' ₺';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            return 'Toplam: ' + value.toLocaleString('tr-TR') + ' ₺';
                        }
                    }
                }
            }
        }
    });
    
    // Ödeme Yöntemleri Pasta Grafiği
    var ctx2 = document.getElementById('paymentMethodsChart').getContext('2d');
    var paymentMethodsChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($method_names); ?>,
            datasets: [{
                data: <?php echo json_encode($method_counts); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 15,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            var index = context.dataIndex;
                            var count = context.dataset.data[index];
                            var amount = <?php echo json_encode($method_amounts); ?>[index];
                            return [
                                'Adet: ' + count,
                                'Tutar: ' + amount.toLocaleString('tr-TR') + ' ₺'
                            ];
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
    
    // Yenile butonu
    $('#refreshData').click(function() {
        location.reload();
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
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.chart-area {
    position: relative;
    height: 300px;
    width: 100%;
}
.chart-pie {
    position: relative;
    height: 250px;
    width: 100%;
}
.user-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?> 