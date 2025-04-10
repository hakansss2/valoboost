<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// ID kontrolü
$account_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$account_id) {
    header('Location: bank_accounts.php');
    exit;
}

// Banka hesabını getir
try {
    $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE id = ?");
    $stmt->execute([$account_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        $_SESSION['message'] = 'Banka hesabı bulunamadı.';
        $_SESSION['message_type'] = 'danger';
        header('Location: bank_accounts.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['message'] = 'Hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: bank_accounts.php');
    exit;
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = trim($_POST['bank_name']);
    $account_name = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $iban = trim($_POST['iban']);
    $branch_code = trim($_POST['branch_code']);
    $branch_name = trim($_POST['branch_name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    
    // Validasyon
    $errors = [];
    
    if (empty($bank_name)) {
        $errors[] = 'Banka adı gereklidir.';
    }
    
    if (empty($account_name)) {
        $errors[] = 'Hesap adı gereklidir.';
    }
    
    if (empty($iban)) {
        $errors[] = 'IBAN gereklidir.';
    }
    
    // Hata yoksa güncelle
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE bank_accounts SET 
                    bank_name = ?, 
                    account_name = ?, 
                    account_number = ?, 
                    iban = ?, 
                    branch_code = ?, 
                    branch_name = ?, 
                    description = ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $bank_name, 
                $account_name, 
                $account_number, 
                $iban,
                $branch_code, 
                $branch_name, 
                $description, 
                $status,
                $account_id
            ]);
            
            $_SESSION['message'] = 'Banka hesabı başarıyla güncellendi.';
            $_SESSION['message_type'] = 'success';
            
            header('Location: bank_accounts.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// Sayfa başlığı
$page_title = 'Banka Hesabı Düzenle';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Banka Hesabı Düzenle</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Banka Hesabı Bilgileri</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bank_name" class="form-label">Banka Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                       value="<?php echo isset($_POST['bank_name']) ? htmlspecialchars($_POST['bank_name']) : htmlspecialchars($account['bank_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="account_name" class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="<?php echo isset($_POST['account_name']) ? htmlspecialchars($_POST['account_name']) : htmlspecialchars($account['account_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_number" class="form-label">Hesap Numarası</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       value="<?php echo isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : htmlspecialchars($account['account_number']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="iban" class="form-label">IBAN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="iban" name="iban" 
                                       value="<?php echo isset($_POST['iban']) ? htmlspecialchars($_POST['iban']) : htmlspecialchars($account['iban']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="branch_code" class="form-label">Şube Kodu</label>
                                <input type="text" class="form-control" id="branch_code" name="branch_code" 
                                       value="<?php echo isset($_POST['branch_code']) ? htmlspecialchars($_POST['branch_code']) : htmlspecialchars($account['branch_code']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="branch_name" class="form-label">Şube Adı</label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" 
                                       value="<?php echo isset($_POST['branch_name']) ? htmlspecialchars($_POST['branch_name']) : htmlspecialchars($account['branch_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($account['description']); ?></textarea>
                            <div class="form-text">Kullanıcılara gösterilecek açıklama (örn: Havale yaparken açıklama kısmına kullanıcı adınızı yazmayı unutmayın).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Durum</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') || (!isset($_POST['status']) && $account['status'] === 'active') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') || (!isset($_POST['status']) && $account['status'] === 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="bank_accounts.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 