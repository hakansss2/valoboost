<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Banka hesabı durumunu değiştirme
if (isset($_POST['toggle_status'])) {
    $account_id = (int)$_POST['account_id'];
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    
    try {
        $stmt = $conn->prepare("UPDATE bank_accounts SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $account_id]);
        
        $_SESSION['message'] = 'Banka hesabı durumu güncellendi.';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: bank_accounts.php');
    exit;
}

// Banka hesabı silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $account_id = (int)$_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        
        $_SESSION['message'] = 'Banka hesabı başarıyla silindi.';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: bank_accounts.php');
    exit;
}

// Banka hesaplarını getir
try {
    $stmt = $conn->query("SELECT * FROM bank_accounts ORDER BY bank_name, account_name");
    $bank_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = 'Hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $bank_accounts = [];
}

// Sayfa başlığı
$page_title = 'Banka Hesapları';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Banka Hesapları</h1>
    
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
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Banka Hesapları</h6>
            <a href="add_bank_account.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Yeni Banka Hesabı Ekle
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Banka</th>
                            <th>Hesap Adı</th>
                            <th>IBAN</th>
                            <th>Şube</th>
                            <th>Durum</th>
                            <th width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bank_accounts)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Henüz banka hesabı eklenmemiş.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bank_accounts as $account): ?>
                                <tr>
                                    <td><?php echo $account['id']; ?></td>
                                    <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                    <td><?php echo htmlspecialchars($account['iban']); ?></td>
                                    <td><?php echo htmlspecialchars($account['branch_name'] . ' (' . $account['branch_code'] . ')'); ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $account['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $account['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $account['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="edit_bank_account.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="bank_accounts.php?delete=<?php echo $account['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu banka hesabını silmek istediğinize emin misiniz?')">
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
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bilgilendirme</h6>
        </div>
        <div class="card-body">
            <p>Banka hesapları, kullanıcıların banka havalesi/EFT ile ödeme yaparken görecekleri hesap bilgileridir.</p>
            <p>Aktif durumda olan hesaplar kullanıcılara gösterilir. Pasif durumda olan hesaplar gösterilmez.</p>
            <p>Hesap bilgilerini eklerken, kullanıcıların ödeme yaparken dikkat etmeleri gereken hususları açıklama kısmına yazabilirsiniz.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 