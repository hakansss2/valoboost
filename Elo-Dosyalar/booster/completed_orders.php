<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Booster kontrolü
if (!isBooster()) {
    redirect('../login.php');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/header.php';

// Booster bilgilerini getir
$stmt = $conn->prepare("
    SELECT b.*, u.username,
           (SELECT COUNT(*) FROM orders WHERE booster_id = u.id) as total_orders,
           (SELECT COUNT(*) FROM orders WHERE booster_id = u.id AND status = 'completed') as completed_orders,
           (SELECT COUNT(*) FROM orders WHERE booster_id = u.id AND status = 'in_progress') as active_orders,
           (SELECT COALESCE(SUM(booster_earnings), 0) FROM orders WHERE booster_id = u.id AND status = 'completed') as total_earnings
    FROM boosters b
    JOIN users u ON b.user_id = u.id
    WHERE b.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$booster = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug için booster bilgilerini yazdır
error_log("Booster bilgileri: " . print_r($booster, true));

// Tamamlanan siparişleri getir
$stmt = $conn->prepare("
    SELECT o.*, g.name as game_name, u.username as customer_name,
           (SELECT AVG(rating) FROM booster_ratings WHERE order_id = o.id) as rating
    FROM orders o
    JOIN games g ON o.game_id = g.id
    JOIN users u ON o.user_id = u.id
    WHERE o.booster_id = ? AND o.status = 'completed'
    ORDER BY o.completed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);  // Booster'ın user_id'sini kullan
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug için siparişleri yazdır
error_log("Tamamlanan siparişler: " . print_r($completed_orders, true));
?>

<div class="container-fluid py-4">
    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
        <!-- Toplam Kazanç -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="mdi mdi-cash-multiple"></i>
                </div>
                <h6 class="text-white mb-2">Toplam Kazanç</h6>
                <h3 class="text-white mb-0"><?php echo number_format($booster['total_earnings'], 2, ',', '.'); ?> ₺</h3>
            </div>
        </div>

        <!-- Tamamlanan Siparişler -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="mdi mdi-check-circle"></i>
                </div>
                <h6 class="text-white mb-2">Tamamlanan Siparişler</h6>
                <h3 class="text-white mb-0"><?php echo $booster['completed_orders']; ?></h3>
            </div>
        </div>

        <!-- Aktif Siparişler -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="mdi mdi-clock-outline"></i>
                </div>
                <h6 class="text-white mb-2">Aktif Siparişler</h6>
                <h3 class="text-white mb-0"><?php echo $booster['active_orders']; ?></h3>
            </div>
        </div>

        <!-- Ortalama Puan -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="mdi mdi-star"></i>
                </div>
                <h6 class="text-white mb-2">Ortalama Puan</h6>
                <?php
                $stmt = $conn->prepare("
                    SELECT AVG(rating) as avg_rating 
                    FROM booster_ratings br
                    JOIN orders o ON br.order_id = o.id
                    WHERE o.booster_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $avg_rating = $stmt->fetch(PDO::FETCH_COLUMN);
                ?>
                <h3 class="text-white mb-0">
                    <?php echo $avg_rating ? number_format($avg_rating, 1) : '0.0'; ?>
                    <small class="text-warning"><i class="mdi mdi-star"></i></small>
                </h3>
            </div>
        </div>
    </div>

    <!-- Tamamlanan Siparişler Tablosu -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 glass-effect">
                <div class="card-header bg-transparent py-3">
                    <h5 class="text-white mb-0">
                        <i class="mdi mdi-check-circle me-2"></i>Tamamlanan Siparişler
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($completed_orders)): ?>
                        <div class="text-center py-5">
                            <img src="../assets/img/illustrations/no-data.svg" alt="Veri Yok" class="mb-3" style="height: 150px;">
                            <h5 class="text-white">Tamamlanan Sipariş Bulunmuyor</h5>
                            <p class="text-white">Henüz tamamladığınız bir sipariş bulunmamaktadır.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-white">Sipariş No</th>
                                        <th class="text-white">Oyun</th>
                                        <th class="text-white">Müşteri</th>
                                        <th class="text-white">Kazanç</th>
                                        <th class="text-white">Tamamlanma Tarihi</th>
                                        <th class="text-white">Puan</th>
                                        <th class="text-white">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_orders as $order): ?>
                                        <tr>
                                            <td class="text-white">#<?php echo $order['id']; ?></td>
                                            <td class="text-white"><?php echo htmlspecialchars($order['game_name']); ?></td>
                                            <td class="text-white"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td class="text-white"><?php echo number_format($order['booster_earnings'], 2, ',', '.'); ?> ₺</td>
                                            <td class="text-white"><?php echo date('d.m.Y H:i', strtotime($order['completed_at'])); ?></td>
                                            <td>
                                                <?php if ($order['rating']): ?>
                                                    <div class="text-warning">
                                                        <?php
                                                        $rating = round($order['rating']);
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $rating ? '<i class="mdi mdi-star"></i>' : '<i class="mdi mdi-star-outline"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Değerlendirilmemiş</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="order.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-glow btn-primary btn-sm">
                                                    <i class="mdi mdi-eye"></i>
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
    </div>
</div>

<style>
.stats-card {
    background: rgba(26, 27, 58, 0.95);
    border-radius: 20px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.stats-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
}

.stats-icon i {
    font-size: 24px;
    color: #fff;
}

.glass-effect {
    background: rgba(26, 27, 58, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Tablo stilleri */
.table {
    color: #fff !important;
}

.table thead th {
    background-color: rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.1);
}

.table tbody td {
    background-color: rgba(0, 0, 0, 0.1);
    border-color: rgba(255, 255, 255, 0.1);
}

.table-hover tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.05);
}

/* DataTables özelleştirmeleri */
.dataTables_wrapper {
    color: #fff;
}

.dataTables_length select,
.dataTables_filter input {
    background-color: rgba(0, 0, 0, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}

.dataTables_info,
.dataTables_paginate {
    color: #fff !important;
}

.paginate_button {
    color: #fff !important;
}

.paginate_button.current {
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}

.paginate_button:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}
</style>

<?php require_once 'includes/footer.php'; ?> 