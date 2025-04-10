<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// İstatistikleri getir
try {
    // Toplam sipariş sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();
    
    // Bekleyen siparişler
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetchColumn();
    
    // Tamamlanan siparişler
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
    $completed_orders = $stmt->fetchColumn();
    
    // Toplam kazanç
    $stmt = $conn->query("SELECT SUM(price) FROM orders WHERE status != 'cancelled'");
    $total_earnings = $stmt->fetchColumn() ?: 0;
    
    // Destek talepleri istatistikleri
    $stmt = $conn->query("SELECT COUNT(*) FROM support_tickets");
    $total_tickets = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
    $open_tickets = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'closed'");
    $closed_tickets = $stmt->fetchColumn();
    
    // Son siparişler
    $stmt = $conn->prepare("
        SELECT o.*, u.username, g.name as game_name,
               r1.name as current_rank_name, r1.image as current_rank_image,
               r2.name as target_rank_name, r2.image as target_rank_image
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN games g ON o.game_id = g.id
        JOIN ranks r1 ON o.current_rank_id = r1.id
        JOIN ranks r2 ON o.target_rank_id = r2.id
        WHERE o.status = 'pending'
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $pending_order_list = $stmt->fetchAll();
    
    // Oyunlara göre sipariş dağılımı
    $stmt = $conn->query("
        SELECT g.name, COUNT(*) as count
        FROM orders o
        JOIN games g ON o.game_id = g.id
        GROUP BY g.id
        ORDER BY count DESC
        LIMIT 5
    ");
    $game_stats = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Hoş Geldin Kartı -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h2 class="mb-3">Hoş Geldin, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                            <p class="mb-4 text-secondary">Panel istatistiklerine göz atabilir, bekleyen siparişleri yönetebilir ve sistem ayarlarını düzenleyebilirsin.</p>
                            <div class="d-flex gap-3">
                                <a href="orders.php?status=pending" class="btn btn-primary">
                                    <i class="mdi mdi-clock-outline me-2"></i>Bekleyen Siparişler
                                </a>
                                <a href="settings.php" class="btn btn-outline-primary" style="backdrop-filter: blur(10px); background: rgba(99, 102, 241, 0.1);">
                                    <i class="mdi mdi-cog me-2"></i>Ayarlar
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center">
                            <img src="../assets/img/admin-welcome.svg" alt="Welcome" class="img-fluid" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
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
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon">
                    <i class="mdi mdi-clock"></i>
                </div>
                <h4 class="mb-2">Bekleyen Sipariş</h4>
                <h2 class="mb-0"><?php echo number_format($pending_orders); ?></h2>
                <div class="mt-3">
                    <a href="orders.php?status=pending" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="mdi mdi-arrow-right me-1"></i>Siparişleri Görüntüle
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon">
                    <i class="mdi mdi-cash-multiple"></i>
                </div>
                <h4 class="mb-2">Toplam Kazanç</h4>
                <h2 class="mb-0"><?php echo number_format($total_earnings, 2, ',', '.'); ?> ₺</h2>
                <div class="mt-3">
                    <a href="payments.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="mdi mdi-arrow-right me-1"></i>Ödemeleri Görüntüle
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card fade-in">
                <div class="stats-icon">
                    <i class="mdi mdi-headset"></i>
                </div>
                <h4 class="mb-2">Destek Talepleri</h4>
                <h2 class="mb-0"><?php echo number_format($open_tickets); ?></h2>
                <div class="mt-3">
                    <a href="support.php" class="btn btn-sm btn-outline-warning rounded-pill">
                        <i class="mdi mdi-alert me-1"></i>Talepleri Görüntüle
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bekleyen Siparişler Tablosu -->
    <div class="row">
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="mdi mdi-clock-outline me-2"></i>Bekleyen Siparişler
                    </h5>
                    <a href="orders.php?status=pending" class="btn btn-primary btn-sm">
                        Tümünü Görüntüle
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_order_list)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Kullanıcı</th>
                                        <th>Oyun</th>
                                        <th>Rank Bilgisi</th>
                                        <th>Tutar</th>
                                        <th>Tarih</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_order_list as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td><?php echo htmlspecialchars($order['game_name']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($order['current_rank_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                             alt="" class="me-2" style="width: 24px;">
                                                    <?php endif; ?>
                                                    <i class="mdi mdi-arrow-right mx-2"></i>
                                                    <?php if ($order['target_rank_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                             alt="" style="width: 24px;">
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($order['price'], 2, ',', '.'); ?> ₺</td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="acceptOrder(<?php echo $order['id']; ?>)">
                                                        <i class="mdi mdi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-sm"
                                                            onclick="assignBooster(<?php echo $order['id']; ?>)">
                                                        <i class="mdi mdi-account-plus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-info btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                        <i class="mdi mdi-eye"></i>
                                                    </button>
                                                </div>

                                                <!-- Sipariş Detay Modal -->
                                                <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="mdi mdi-information me-2"></i>
                                                                    Sipariş #<?php echo $order['id']; ?> Detayları
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row g-4">
                                                                    <div class="col-md-6">
                                                                        <div class="card h-100">
                                                                            <div class="card-body">
                                                                                <h6 class="card-title mb-3">
                                                                                    <i class="mdi mdi-account me-2"></i>Müşteri Bilgileri
                                                                                </h6>
                                                                                <p class="mb-2">
                                                                                    <strong>Kullanıcı Adı:</strong> 
                                                                                    <?php echo htmlspecialchars($order['username']); ?>
                                                                                </p>
                                                                                <p class="mb-0">
                                                                                    <strong>Sipariş Tarihi:</strong> 
                                                                                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="card h-100">
                                                                            <div class="card-body">
                                                                                <h6 class="card-title mb-3">
                                                                                    <i class="mdi mdi-gamepad-variant me-2"></i>Oyun Bilgileri
                                                                                </h6>
                                                                                <p class="mb-2">
                                                                                    <strong>Oyun:</strong> 
                                                                                    <?php echo htmlspecialchars($order['game_name']); ?>
                                                                                </p>
                                                                                <div class="d-flex align-items-center">
                                                                                    <strong class="me-2">Rank:</strong>
                                                                                    <div class="d-flex align-items-center">
                                                                                        <?php if ($order['current_rank_image']): ?>
                                                                                            <img src="../<?php echo htmlspecialchars($order['current_rank_image']); ?>" 
                                                                                                 alt="" class="me-2" style="width: 24px;">
                                                                                        <?php endif; ?>
                                                                                        <i class="mdi mdi-arrow-right mx-2"></i>
                                                                                        <?php if ($order['target_rank_image']): ?>
                                                                                            <img src="../<?php echo htmlspecialchars($order['target_rank_image']); ?>" 
                                                                                                 alt="" style="width: 24px;">
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="mdi mdi-information-outline" style="font-size: 48px;"></i>
                            <p class="mt-2">Bekleyen sipariş bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booster Atama Modal -->
    <div class="modal fade" id="assignBoosterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="mdi mdi-account-plus me-2"></i>Booster Ata
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignBoosterForm">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="orderIdInput">
                        <div class="mb-3">
                            <label class="form-label">Booster Seç</label>
                            <select name="booster_id" class="form-select" required>
                                <option value="">Booster seçin...</option>
                                <?php
                                // Aktif boosterları getir
                                $stmt = $conn->query("
                                    SELECT id, username 
                                    FROM users 
                                    WHERE role = 'booster' AND status = 'active'
                                    ORDER BY username
                                ");
                                while ($booster = $stmt->fetch()) {
                                    echo '<option value="' . $booster['id'] . '">' . htmlspecialchars($booster['username']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check me-2"></i>Ata
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 