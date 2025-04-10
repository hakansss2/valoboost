<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Ödeme yöntemini aktif/pasif yapma
if (isset($_POST['toggle_status'])) {
    $method_id = (int)$_POST['method_id'];
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    
    try {
        $stmt = $conn->prepare("UPDATE payment_methods SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $method_id]);
        
        $_SESSION['message'] = 'Ödeme yöntemi durumu güncellendi.';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: payment_methods.php');
    exit;
}

// Ödeme yöntemlerini getir
try {
    $stmt = $conn->query("SELECT * FROM payment_methods ORDER BY sort_order, name");
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = 'Hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $payment_methods = [];
}

// Banka hesaplarını getir
try {
    $stmt = $conn->query("SELECT * FROM bank_accounts ORDER BY bank_name");
    $bank_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = 'Hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $bank_accounts = [];
}

// Sayfa başlığı
$page_title = 'Ödeme Yöntemleri';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Ödeme Yöntemleri</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Yöntemleri</h6>
                    <a href="add_payment_method.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Yeni Ödeme Yöntemi Ekle
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>İkon</th>
                                    <th>Adı</th>
                                    <th>Açıklama</th>
                                    <th>Min. Tutar</th>
                                    <th>Max. Tutar</th>
                                    <th>Durum</th>
                                    <th>Sıralama</th>
                                    <th width="150">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payment_methods)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Henüz ödeme yöntemi eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <tr>
                                            <td><?php echo $method['id']; ?></td>
                                            <td>
                                                <?php if ($method['icon']): ?>
                                                    <i class="<?php echo htmlspecialchars($method['icon']); ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-money-bill-wave"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($method['name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($method['description'], 0, 50) . (strlen($method['description']) > 50 ? '...' : '')); ?></td>
                                            <td><?php echo number_format($method['min_amount'], 2, ',', '.'); ?> ₺</td>
                                            <td><?php echo number_format($method['max_amount'], 2, ',', '.'); ?> ₺</td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $method['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $method['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo $method['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?php echo $method['sort_order']; ?></td>
                                            <td>
                                                <a href="edit_payment_method.php?id=<?php echo $method['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($method['name'] === 'Banka Havalesi'): ?>
                                                    <a href="bank_accounts.php" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-university"></i> Banka Hesapları
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete_payment_method.php?id=<?php echo $method['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu ödeme yöntemini silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Banka Hesapları Yönetimi</h6>
                </div>
                <div class="card-body">
                    <p>Banka havalesi ile ödeme için kullanılacak banka hesaplarını yönetmek için aşağıdaki butona tıklayın.</p>
                    <a href="bank_accounts.php" class="btn btn-primary">
                        <i class="fas fa-university"></i> Banka Hesaplarını Yönet
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 