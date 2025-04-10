<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Destek taleplerini getir
try {
    // Destek talepleri istatistikleri
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
            COUNT(DISTINCT user_id) as unique_users
        FROM support_tickets
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Aylık destek talebi sayıları
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM support_tickets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $monthly_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Grafik verileri
    $months = [];
    $ticket_data = [];
    
    // Son 6 ayı doldur
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i month"));
        $months[] = date('M Y', strtotime("-$i month"));
        $ticket_data[$month] = 0;
    }
    
    // Veritabanından gelen verileri ekle
    foreach ($monthly_tickets as $item) {
        if (isset($ticket_data[$item['month']])) {
            $ticket_data[$item['month']] = (int)$item['count'];
        }
    }
    
    // Durum dağılımı
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM support_tickets
        GROUP BY status
    ");
    $stmt->execute();
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Destek taleplerini getir
    $stmt = $conn->prepare("
        SELECT t.*, u.username,
               (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) as message_count,
               (SELECT MAX(created_at) FROM support_messages WHERE ticket_id = t.id) as last_message
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.status = 'open' DESC, t.updated_at DESC
    ");
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['message'] = 'Destek talepleri yüklenirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
    $tickets = [];
    $stats = [
        'total_tickets' => 0,
        'open_tickets' => 0,
        'closed_tickets' => 0,
        'unique_users' => 0
    ];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Destek Talepleri Yönetimi</h2>
                            <p class="mb-0 opacity-75">Kullanıcılardan gelen destek taleplerini yönetin ve yanıtlayın.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light" id="refreshPage">
                                <i class="fas fa-sync-alt me-2"></i>Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
        <!-- Toplam Talepler -->
        <div class="col-md-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Talepler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_tickets']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Açık Talepler -->
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Açık Talepler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['open_tickets']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kapalı Talepler -->
        <div class="col-md-3">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Kapalı Talepler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['closed_tickets']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benzersiz Kullanıcılar -->
        <div class="col-md-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Benzersiz Kullanıcılar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['unique_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Aylık Destek Talepleri Grafiği -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aylık Destek Talepleri</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlyTicketsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Durum Dağılımı -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Durum Dağılımı</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusDistributionChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Açık
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-secondary"></i> Kapalı
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Tüm Destek Talepleri</h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" id="filterAll">Tümü</button>
                <button type="button" class="btn btn-sm btn-outline-success" id="filterOpen">Açık</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="filterClosed">Kapalı</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ticketsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kullanıcı</th>
                            <th>Konu</th>
                            <th>Durum</th>
                            <th>Mesaj Sayısı</th>
                            <th>Son Mesaj</th>
                            <th>Oluşturulma</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr data-status="<?php echo $ticket['status']; ?>">
                                <td><?php echo $ticket['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle-sm me-2">
                                            <span class="avatar-text-sm"><?php echo strtoupper(substr($ticket['username'], 0, 1)); ?></span>
                                        </div>
                                        <?php echo htmlspecialchars($ticket['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $ticket['status'] == 'open' ? 'success' : 'secondary'; ?>">
                                        <?php echo $ticket['status'] == 'open' ? 'Açık' : 'Kapalı'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info rounded-pill">
                                        <?php echo $ticket['message_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ticket['last_message']): ?>
                                        <?php echo date('d.m.Y H:i', strtotime($ticket['last_message'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($ticket['status'] == 'open'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning close-ticket"
                                                    data-id="<?php echo $ticket['id']; ?>">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success open-ticket"
                                                    data-id="<?php echo $ticket['id']; ?>">
                                                <i class="fas fa-lock-open"></i>
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

<!-- Sonuç Modalı -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İşlem Sonucu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="resultMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
.chart-area {
    position: relative;
    height: 300px;
}
.chart-pie {
    position: relative;
    height: 250px;
}
.avatar-circle-sm {
    width: 30px;
    height: 30px;
    background-color: #4e73df;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.avatar-text-sm {
    font-size: 14px;
    color: white;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTables
    const ticketsTable = $('#ticketsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        },
        order: [[6, 'desc']]
    });
    
    // Filtreler
    $('#filterAll').on('click', function() {
        ticketsTable.search('').columns().search('').draw();
        updateFilterButtons(this);
    });
    
    $('#filterOpen').on('click', function() {
        ticketsTable.column(3).search('Açık').draw();
        updateFilterButtons(this);
    });
    
    $('#filterClosed').on('click', function() {
        ticketsTable.column(3).search('Kapalı').draw();
        updateFilterButtons(this);
    });
    
    function updateFilterButtons(activeButton) {
        $('.btn-outline-primary, .btn-outline-success, .btn-outline-secondary').removeClass('active');
        $(activeButton).addClass('active');
    }
    
    // Sayfa yenileme
    $('#refreshPage').on('click', function() {
        location.reload();
    });
    
    // Talep kapatma
    $('.close-ticket').on('click', function() {
        const ticketId = $(this).data('id');
        
        Swal.fire({
            title: 'Destek Talebini Kapat',
            text: 'Bu destek talebini kapatmak istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, Kapat',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/update_ticket_status.php',
                    type: 'POST',
                    data: {
                        ticket_id: ticketId,
                        status: 'closed'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Kapatıldı!',
                                'Destek talebi başarıyla kapatıldı.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Hata!',
                                response.message || 'Bir hata oluştu.',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Hata!',
                            'İşlem sırasında bir hata oluştu.',
                            'error'
                        );
                    }
                });
            }
        });
    });
    
    // Talep açma
    $('.open-ticket').on('click', function() {
        const ticketId = $(this).data('id');
        
        Swal.fire({
            title: 'Destek Talebini Aç',
            text: 'Bu destek talebini açmak istediğinize emin misiniz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, Aç',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/update_ticket_status.php',
                    type: 'POST',
                    data: {
                        ticket_id: ticketId,
                        status: 'open'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Açıldı!',
                                'Destek talebi başarıyla açıldı.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Hata!',
                                response.message || 'Bir hata oluştu.',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Hata!',
                            'İşlem sırasında bir hata oluştu.',
                            'error'
                        );
                    }
                });
            }
        });
    });
    
    // Aylık Destek Talepleri Grafiği
    const monthlyCtx = document.getElementById('monthlyTicketsChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Aylık Destek Talepleri',
                data: <?php echo json_encode(array_values($ticket_data)); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Durum Dağılımı Grafiği
    const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Açık', 'Kapalı'],
            datasets: [{
                data: [<?php echo $stats['open_tickets']; ?>, <?php echo $stats['closed_tickets']; ?>],
                backgroundColor: ['#1cc88a', '#858796'],
                hoverBackgroundColor: ['#17a673', '#717384'],
                hoverBorderColor: 'rgba(234, 236, 244, 1)',
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 