<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    redirect('../login.php');
}

// Boosterları getir
try {
    // Önce boosters tablosunu kontrol et
    $stmt = $conn->prepare("SHOW TABLES LIKE 'boosters'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        throw new Exception('Booster tablosu bulunamadı. Lütfen db_update.php dosyasını çalıştırın.');
    }

    // Ana sorgu
    $stmt = $conn->prepare("
        SELECT 
            b.id,
            b.user_id,
            b.pending_balance,
            b.total_balance,
            b.withdrawn_balance,
            b.iban,
            b.bank_name,
            b.account_holder,
            u.username,
            u.email,
            u.status,
            u.created_at as join_date,
            (SELECT COUNT(*) FROM orders WHERE booster_id = b.id AND status = 'in_progress') as active_orders,
            (SELECT COUNT(*) FROM orders WHERE booster_id = b.id AND status = 'completed') as completed_orders,
            (SELECT COUNT(*) FROM orders WHERE booster_id = b.id) as total_orders,
            COALESCE((SELECT AVG(rating) FROM booster_ratings WHERE booster_id = b.id), 0) as average_rating,
            (SELECT COUNT(*) FROM booster_ratings WHERE booster_id = b.id) as total_ratings,
            (SELECT created_at FROM booster_payments WHERE booster_id = b.id AND status = 'completed' ORDER BY created_at DESC LIMIT 1) as last_payment
        FROM boosters b
        JOIN users u ON b.user_id = u.id
        WHERE u.role = 'booster'
        ORDER BY b.id DESC
    ");
    $stmt->execute();
    $boosters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($boosters)) {
        $_SESSION['info'] = "Henüz hiç booster eklenmemiş.";
    }

    // Aktif oyunları getir
    $stmt = $conn->prepare("SELECT id, name FROM games WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log('Booster listesi hatası: ' . $e->getMessage());
    $_SESSION['error'] = "Veritabanı hatası: " . $e->getMessage();
    $boosters = [];
    $games = [];
} catch(Exception $e) {
    error_log('Booster listesi hatası: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    $boosters = [];
    $games = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white">
            <i class="mdi mdi-account-star me-2"></i>Boosterlar
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBoosterModal">
            <i class="mdi mdi-plus me-2"></i>Yeni Booster Ekle
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white" id="boostersTable">
                    <thead>
                        <tr>
                            <th class="text-white">ID</th>
                            <th class="text-white">Kullanıcı Adı</th>
                            <th class="text-white">E-posta</th>
                            <th class="text-white">Durum</th>
                            <th class="text-white">Siparişler</th>
                            <th class="text-white">Başarı Oranı</th>
                            <th class="text-white">Değerlendirme</th>
                            <th class="text-white">Bekleyen Bakiye</th>
                            <th class="text-white">Son Ödeme</th>
                            <th class="text-white">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <?php foreach ($boosters as $booster): ?>
                            <tr>
                                <td><?php echo $booster['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 bg-primary bg-opacity-10 rounded-circle">
                                            <span class="avatar-title text-primary">
                                                <?php echo strtoupper(substr($booster['username'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <?php echo htmlspecialchars($booster['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($booster['email']); ?></td>
                                <td>
                                    <?php if ($booster['status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booster['active_orders'] > 0): ?>
                                        <span class="badge bg-warning me-1" title="Aktif Siparişler">
                                            <?php echo $booster['active_orders']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-white">
                                        <?php echo $booster['completed_orders']; ?>/<?php echo $booster['total_orders']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $success_rate = $booster['total_orders'] > 0 
                                        ? ($booster['completed_orders'] / $booster['total_orders']) * 100 
                                        : 0;
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $success_rate; ?>%"></div>
                                        </div>
                                        <span class="ms-2 text-white">%<?php echo number_format($success_rate, 0); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($booster['total_ratings'] > 0): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-star text-warning me-1"></i>
                                            <span class="text-white"><?php echo number_format($booster['average_rating'], 1); ?></span>
                                            <small class="text-white-50 ms-1">(<?php echo $booster['total_ratings']; ?>)</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-white-50">Değerlendirme yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booster['pending_balance'] > 0): ?>
                                        <span class="text-warning fw-bold">
                                            <?php echo number_format($booster['pending_balance'], 2, ',', '.'); ?> ₺
                                        </span>
                                    <?php else: ?>
                                        <span class="text-white-50">0,00 ₺</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booster['last_payment']): ?>
                                        <span class="text-white"><?php echo date('d.m.Y', strtotime($booster['last_payment'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-white-50">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewStats(<?php echo $booster['id']; ?>)" title="İstatistikler">
                                            <i class="mdi mdi-chart-bar"></i>
                                        </button>
                                        <?php if ($booster['pending_balance'] > 0): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="makePayment(<?php echo $booster['id']; ?>, <?php echo $booster['pending_balance']; ?>)" title="Ödeme Yap">
                                                <i class="mdi mdi-cash"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="edit_booster.php?id=<?php echo $booster['id']; ?>" class="btn btn-sm btn-primary" title="Düzenle">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
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

<!-- Yeni Booster Ekleme Modal -->
<div class="modal fade" id="addBoosterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-account-plus me-2"></i>Yeni Booster Ekle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addBoosterForm">
                    <div class="row g-4">
                        <!-- Hesap Bilgileri -->
                        <div class="col-md-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-header border-secondary">
                                    <h6 class="card-title mb-0 text-white">
                                        <i class="mdi mdi-account me-2"></i>Hesap Bilgileri
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-white">Kullanıcı Adı</label>
                                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white">E-posta Adresi</label>
                                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white">Şifre</label>
                                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white">Şifre (Tekrar)</label>
                                        <input type="password" name="password_confirm" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Banka Bilgileri -->
                        <div class="col-md-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-header border-secondary">
                                    <h6 class="card-title mb-0 text-white">
                                        <i class="mdi mdi-bank me-2"></i>Banka Bilgileri
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-white">IBAN</label>
                                        <input type="text" name="iban" class="form-control bg-dark text-white border-secondary">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white">Banka Adı</label>
                                        <input type="text" name="bank_name" class="form-control bg-dark text-white border-secondary">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white">Hesap Sahibi</label>
                                        <input type="text" name="account_holder" class="form-control bg-dark text-white border-secondary">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Oyunlar -->
                        <div class="col-12">
                            <div class="card bg-dark border-secondary">
                                <div class="card-header border-secondary">
                                    <h6 class="card-title mb-0 text-white">
                                        <i class="mdi mdi-gamepad-variant me-2"></i>Oyunlar
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <?php foreach ($games as $game): ?>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="games[]" 
                                                           value="<?php echo $game['id']; ?>" id="game_<?php echo $game['id']; ?>">
                                                    <label class="form-check-label text-white" for="game_<?php echo $game['id']; ?>">
                                                        <?php echo htmlspecialchars($game['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitAddBoosterForm()">
                    <i class="mdi mdi-check me-2"></i>Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- İstatistik Modal -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-chart-bar me-2"></i>Booster İstatistikleri
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statsContent">
                <!-- İstatistikler buraya yüklenecek -->
            </div>
        </div>
    </div>
</div>

<script>
// DataTables başlat
$(document).ready(function() {
    $('#boostersTable').DataTable({
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        initComplete: function(settings, json) {
            // DataTables dark theme stilleri
            $('.dataTables_wrapper').addClass('text-white');
            $('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate').addClass('text-white');
            $('.dataTables_length select').addClass('form-select form-select-sm bg-dark text-white border-secondary');
            $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-secondary');
            $('.paginate_button').addClass('text-white');
        }
    });

    // Tablo hücrelerinin metin rengini beyaz yap
    $('.table td, .table th').addClass('text-white');
    $('.text-muted').removeClass('text-muted').addClass('text-white-50');
});

// Yeni booster formu gönderme
function submitAddBoosterForm() {
    const form = document.getElementById('addBoosterForm');
    const formData = new FormData(form);
    
    // Oyun kontrolü
    const games = formData.getAll('games[]');
    if (games.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'En az bir oyun seçmelisiniz.',
            background: '#1e293b',
            color: '#fff'
        });
        return;
    }
    
    // Şifre kontrolü
    const password = formData.get('password');
    const passwordConfirm = formData.get('password_confirm');
    
    if (password !== passwordConfirm) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Şifreler eşleşmiyor.',
            background: '#1e293b',
            color: '#fff'
        });
        return;
    }
    
    fetch('add_booster.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: data.message,
                background: '#1e293b',
                color: '#fff'
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message,
            background: '#1e293b',
            color: '#fff'
        });
    });
}

// İstatistikleri görüntüle
function viewStats(boosterId) {
    const modal = new bootstrap.Modal(document.getElementById('statsModal'));
    const content = document.getElementById('statsContent');
    
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch('get_booster_stats.php?id=' + boosterId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Bir hata oluştu'}
                </div>
            `;
        });
}

// Ödeme yap
function makePayment(boosterId, maxAmount) {
    Swal.fire({
        title: 'Ödeme Yap',
        html: `
            <div class="mb-3">
                <label class="form-label">Ödeme Tutarı</label>
                <input type="number" id="paymentAmount" class="form-control" 
                       min="0" max="${maxAmount}" step="0.01" value="${maxAmount}">
            </div>
            <div class="mb-3">
                <label class="form-label">Not</label>
                <textarea id="paymentNotes" class="form-control" rows="3"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ödeme Yap',
        cancelButtonText: 'İptal',
        background: '#1e293b',
        color: '#fff',
        preConfirm: () => {
            const amount = document.getElementById('paymentAmount').value;
            const notes = document.getElementById('paymentNotes').value;
            
            if (!amount || amount <= 0 || amount > maxAmount) {
                Swal.showValidationMessage('Geçerli bir ödeme tutarı girin');
                return false;
            }
            
            return { amount, notes };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('booster_id', boosterId);
            formData.append('amount', result.value.amount);
            formData.append('notes', result.value.notes);
            
            fetch('make_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: data.message,
                        background: '#1e293b',
                        color: '#fff'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            });
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 