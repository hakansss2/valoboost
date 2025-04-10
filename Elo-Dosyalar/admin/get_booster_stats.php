<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Booster ID gerekli']);
    exit;
}

$booster_id = (int)$_GET['id'];

try {
    // Booster bilgilerini al
    $stmt = $conn->prepare("
        SELECT u.username, b.*
        FROM users u
        JOIN boosters b ON u.id = b.user_id
        WHERE b.id = ?
    ");
    $stmt->execute([$booster_id]);
    $booster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booster) {
        echo json_encode(['success' => false, 'message' => 'Booster bulunamadı']);
        exit;
    }

    // Aylık istatistikleri al
    $stmt = $conn->prepare("
        SELECT *
        FROM booster_stats
        WHERE booster_id = ?
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    $stmt->execute([$booster_id]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Son ödemeleri al
    $stmt = $conn->prepare("
        SELECT *
        FROM booster_payments
        WHERE booster_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$booster_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // HTML içeriğini oluştur
    $html = '
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary">
                    <h6 class="card-title mb-0 text-white">
                        <i class="mdi mdi-chart-line me-2"></i>Aylık İstatistikler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Dönem</th>
                                    <th>Sipariş</th>
                                    <th>Kazanç</th>
                                    <th>Puan</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];

    foreach ($stats as $stat) {
        $html .= '
        <tr>
            <td>' . $months[$stat['month']] . ' ' . $stat['year'] . '</td>
            <td>' . $stat['completed_orders'] . '/' . $stat['total_orders'] . '</td>
            <td>' . number_format($stat['total_earnings'], 2, ',', '.') . ' ₺</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="me-2">' . number_format($stat['average_rating'], 1) . '</div>
                    <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: ' . ($stat['average_rating'] * 20) . '%"></div>
                    </div>
                </div>
            </td>
        </tr>';
    }

    $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary">
                    <h6 class="card-title mb-0 text-white">
                        <i class="mdi mdi-cash-multiple me-2"></i>Son Ödemeler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>Not</th>
                                </tr>
                            </thead>
                            <tbody>';

    foreach ($payments as $payment) {
        $status_badge = match($payment['status']) {
            'completed' => '<span class="badge bg-success">Tamamlandı</span>',
            'pending' => '<span class="badge bg-warning">Beklemede</span>',
            'cancelled' => '<span class="badge bg-danger">İptal</span>',
            default => '<span class="badge bg-secondary">Bilinmiyor</span>'
        };

        $html .= '
        <tr>
            <td>' . date('d.m.Y H:i', strtotime($payment['created_at'])) . '</td>
            <td>' . number_format($payment['amount'], 2, ',', '.') . ' ₺</td>
            <td>' . $status_badge . '</td>
            <td>' . ($payment['notes'] ? htmlspecialchars($payment['notes']) : '-') . '</td>
        </tr>';
    }

    $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu'
    ]);
} 