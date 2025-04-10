<?php
require_once 'includes/header.php';

// Bekleyen ödemeleri getir
try {
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.username,
            b.user_id as booster_user_id
        FROM booster_payments bp
        JOIN boosters b ON bp.booster_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE bp.status = 'pending'
        ORDER BY bp.created_at DESC
    ");
    $stmt->execute();
    $pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Ödeme bilgileri alınırken bir hata oluştu.";
    $pending_payments = [];
}

// Tamamlanan ödemeleri getir
try {
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.username
        FROM booster_payments bp
        JOIN boosters b ON bp.booster_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE bp.status = 'completed'
        ORDER BY bp.payment_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $completed_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $completed_payments = [];
}
?>

<div class="container-fluid py-4">
    <!-- Bekleyen Ödemeler -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0">
                        <i class="mdi mdi-clock-outline me-2"></i>Bekleyen Ödemeler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Booster</th>
                                    <th>Tutar</th>
                                    <th>Talep Tarihi</th>
                                    <th>Not</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_payments)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Bekleyen ödeme bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td>
                                                <span class="text-warning">
                                                    <?php echo number_format($payment['amount'], 2, ',', '.'); ?> ₺
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                                            </td>
                                            <td><?php echo $payment['notes'] ? htmlspecialchars($payment['notes']) : '-'; ?></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-success btn-sm"
                                                        onclick="approvePayment(<?php 
                                                            echo htmlspecialchars(json_encode([
                                                                'payment_id' => $payment['id'],
                                                                'booster_id' => $payment['booster_id'],
                                                                'username' => $payment['username'],
                                                                'amount' => $payment['amount']
                                                            ])); 
                                                        ?>)">
                                                    <i class="mdi mdi-check me-1"></i>Onayla
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Tamamlanan Ödemeler -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0">
                        <i class="mdi mdi-check-circle me-2"></i>Son Tamamlanan Ödemeler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Booster</th>
                                    <th>Tutar</th>
                                    <th>Talep Tarihi</th>
                                    <th>Ödeme Tarihi</th>
                                    <th>Not</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($completed_payments)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tamamlanan ödeme bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($completed_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td>
                                                <span class="text-success">
                                                    <?php echo number_format($payment['amount'], 2, ',', '.'); ?> ₺
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                                            </td>
                                            <td>
                                                <?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?>
                                            </td>
                                            <td><?php echo $payment['notes'] ? htmlspecialchars($payment['notes']) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approvePayment(data) {
    Swal.fire({
        title: 'Ödemeyi Onayla',
        html: `<b>${data.username}</b> adlı booster için <b>${data.amount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</b> tutarındaki ödemeyi onaylamak istiyor musunuz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Onayla',
        cancelButtonText: 'İptal',
        background: '#1e293b',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('approve_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: data.payment_id,
                    booster_id: data.booster_id
                })
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

// DataTables başlat
$(document).ready(function() {
    $('.table').DataTable({
        order: [[2, 'desc']]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 