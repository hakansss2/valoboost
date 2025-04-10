<?php
require_once 'includes/header.php';

// Oyun silme işlemi
if (isset($_GET['delete'])) {
    $game_id = (int)$_GET['delete'];
    try {
        // Önce bu oyuna ait rankları sil
        $stmt = $conn->prepare("DELETE FROM ranks WHERE game_id = ?");
        $stmt->execute([$game_id]);
        
        // Sonra oyunu sil
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Oyun başarıyla silindi.";
        } else {
            $_SESSION['error'] = "Oyun silinemedi.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Bir hata oluştu: " . $e->getMessage();
    }
    header("Location: games.php");
    exit;
}

// Oyun durumunu değiştirme
if (isset($_GET['toggle_status'])) {
    $game_id = (int)$_GET['toggle_status'];
    try {
        $stmt = $conn->prepare("UPDATE games SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
        $stmt->execute([$game_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Oyun durumu güncellendi.";
        } else {
            $_SESSION['error'] = "Oyun durumu güncellenemedi.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Bir hata oluştu: " . $e->getMessage();
    }
    header("Location: games.php");
    exit;
}

// İstatistikleri getir
try {
    // Toplam oyun sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM games");
    $total_games = $stmt->fetchColumn();
    
    // Aktif oyun sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM games WHERE status = 'active'");
    $active_games = $stmt->fetchColumn();
    
    // Toplam rank sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM ranks");
    $total_ranks = $stmt->fetchColumn();
    
    // Toplam sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();
    
    // Oyunlara göre sipariş dağılımı
    $stmt = $conn->query("
        SELECT g.name, COUNT(o.id) as order_count
        FROM games g
        LEFT JOIN orders o ON g.id = o.game_id
        GROUP BY g.id
        ORDER BY order_count DESC
    ");
    $game_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Oyun adları ve sipariş sayıları için diziler
    $game_names = [];
    $order_counts = [];
    
    foreach ($game_orders as $data) {
        $game_names[] = $data['name'];
        $order_counts[] = $data['order_count'];
    }
    
    // Son 6 aydaki aylık sipariş sayıları
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aylık sipariş sayıları için diziler
    $order_months = [];
    $monthly_counts = [];
    
    foreach ($monthly_orders as $data) {
        $date = new DateTime($data['month'] . '-01');
        $order_months[] = $date->format('M Y');
        $monthly_counts[] = $data['count'];
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "İstatistikler yüklenirken bir hata oluştu.";
}

// Oyunları getir
try {
    $stmt = $conn->prepare("
        SELECT g.*, 
        (SELECT COUNT(*) FROM ranks WHERE game_id = g.id) as rank_count,
        (SELECT COUNT(*) FROM orders WHERE game_id = g.id) as order_count
        FROM games g
        ORDER BY g.created_at DESC
    ");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Oyunlar yüklenirken bir hata oluştu.";
    $games = [];
}
?>

<!-- Başlık ve Butonlar -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-gamepad fa-sm text-primary-300"></i>
        Oyun Yönetimi
    </h1>
    <div class="d-flex">
        <button class="btn btn-sm btn-primary shadow-sm me-2" id="refreshData">
            <i class="fas fa-sync-alt fa-sm text-white-50 me-1"></i> Yenile
        </button>
        <a href="add_game.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Yeni Oyun
        </a>
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
    <!-- Toplam Oyun Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Toplam Oyun
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_games); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-gamepad fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktif Oyun Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Aktif Oyun
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($active_games); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplam Rank Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Toplam Rank
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_ranks); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-trophy fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplam Sipariş Kartı -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Toplam Sipariş
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_orders); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafikler Satırı -->
<div class="row mb-4">
    <!-- Aylık Sipariş Grafiği -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Aylık Sipariş Sayıları</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlyOrdersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Oyun Dağılımı Pasta Grafiği -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Oyunlara Göre Siparişler</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="gameDistributionChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach (array_slice($game_names, 0, 5) as $index => $name): ?>
                        <span class="me-2">
                            <i class="fas fa-circle text-<?php echo ['primary', 'success', 'info', 'warning', 'danger'][$index % 5]; ?>"></i> <?php echo $name; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Oyunlar Tablosu -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Oyun Listesi</h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter fa-sm"></i> Filtrele
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                <li><a class="dropdown-item filter-status" data-status="all" href="#">Tümü</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item filter-status" data-status="active" href="#">Aktif</a></li>
                <li><a class="dropdown-item filter-status" data-status="inactive" href="#">Pasif</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="gamesTable" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Oyun</th>
                        <th>Rank Sayısı</th>
                        <th>Sipariş Sayısı</th>
                        <th>Durum</th>
                        <th>Oluşturulma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                        <tr data-status="<?php echo $game['status']; ?>">
                            <td><?php echo $game['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($game['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image me-2">
                                    <?php else: ?>
                                        <div class="game-avatar bg-primary me-2">
                                            <span><?php echo strtoupper(substr($game['name'], 0, 1)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($game['name']); ?></div>
                                        <?php if (isset($game['slug']) && !empty($game['slug'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($game['slug']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info rounded-pill"><?php echo $game['rank_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning rounded-pill"><?php echo $game['order_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $game['status'] == 'active' ? 'success' : 'danger'; ?> rounded-pill">
                                    <?php echo $game['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($game['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit_game.php?id=<?php echo $game['id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" onclick="confirmToggleStatus(<?php echo $game['id']; ?>)" 
                                       class="btn btn-sm btn-outline-warning" title="Durum Değiştir">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" onclick="confirmDelete(<?php echo $game['id']; ?>)" 
                                       class="btn btn-sm btn-outline-danger" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
    var gamesTable = $('#gamesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        },
        "order": [[0, "desc"]],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]]
    });
    
    // Durum filtreleme
    $('.filter-status').on('click', function(e) {
        e.preventDefault();
        var status = $(this).data('status');
        
        if (status === 'all') {
            gamesTable.search('').columns(4).search('').draw();
        } else {
            gamesTable.columns(4).search(status === 'active' ? 'Aktif' : 'Pasif').draw();
        }
        
        $('#filterDropdown').text($(this).text());
    });
    
    // Aylık Sipariş Grafiği
    var ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
    var monthlyOrdersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($order_months); ?>,
            datasets: [{
                label: 'Sipariş Sayısı',
                data: <?php echo json_encode($monthly_counts); ?>,
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
                        precision: 0
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
                    displayColors: false
                }
            }
        }
    });
    
    // Oyun Dağılımı Pasta Grafiği
    var ctx2 = document.getElementById('gameDistributionChart').getContext('2d');
    var gameDistributionChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_slice($game_names, 0, 5)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_slice($order_counts, 0, 5)); ?>,
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
                    displayColors: false
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

function confirmDelete(gameId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu oyunu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `games.php?delete=${gameId}`;
        }
    });
}

function confirmToggleStatus(gameId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu oyunun durumunu değiştirmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, değiştir!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `games.php?toggle_status=${gameId}`;
        }
    });
}
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
.game-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}
.game-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?>