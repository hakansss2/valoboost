<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Ödeme ID kontrolü
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
if (!$payment_id) {
    redirect('deposit.php');
}

// Ödeme bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.user_id = ? AND p.payment_method = 'bank_transfer'
        AND p.status = 'pending'
    ");
    $stmt->execute([$payment_id, $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        redirect('deposit.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Ödeme bilgileri getirilirken bir hata oluştu.";
    redirect('deposit.php');
}

// Banka hesaplarını veritabanından getir
try {
    $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY bank_name");
    $stmt->execute();
    $bank_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bank_accounts)) {
        $_SESSION['error'] = "Şu anda aktif banka hesabı bulunmamaktadır. Lütfen site yöneticisiyle iletişime geçin veya farklı bir ödeme yöntemi seçin. (Admin panelinden 'Banka Hesapları' bölümünden en az bir aktif banka hesabı eklemeniz gerekmektedir.)";
        redirect('deposit.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Banka hesapları getirilirken bir hata oluştu.";
    redirect('deposit.php');
}

// Ödeme onaylandı mı?
if (isset($_POST['confirm_payment'])) {
    try {
        // Ödeme durumunu güncelle
        $stmt = $conn->prepare("UPDATE payments SET status = 'waiting_confirmation' WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $_SESSION['user_id']]);
        
        // Bildirim oluştur
        createNotification($_SESSION['user_id'], 'Ödeme onayı bekleniyor', 'Banka havalesi ile yaptığınız ödeme onay bekliyor. En kısa sürede işleme alınacaktır.');
        
        // Admin için bildirim oluştur
        $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $admin_id) {
            createNotification($admin_id, 'Yeni ödeme onayı', 'Yeni bir banka havalesi onayı bekliyor. Ödeme ID: #' . $payment_id);
        }
        
        $_SESSION['success'] = "Ödemeniz başarıyla kaydedildi. Onay sürecine alındı.";
        redirect('payments.php');
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ödeme onaylanırken bir hata oluştu.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Havale/EFT ile Ödeme - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <style>
    .bank-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .bank-card:hover {
        transform: translateY(-5px);
    }
    .bank-card.selected {
        border: 2px solid var(--primary-color);
    }
    .bank-logo {
        width: 120px;
        height: 40px;
        object-fit: contain;
    }
    .iban-copy {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .iban-copy:hover {
        color: var(--primary-color);
    }
    .steps-container {
        position: relative;
    }
    .steps-container::before {
        content: '';
        position: absolute;
        top: 40px;
        left: 50%;
        width: 2px;
        height: calc(100% - 80px);
        background-color: #e9ecef;
    }
    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Ana İçerik -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Ödeme Detayları -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ödeme Detayları</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Ödeme ID:</strong> #<?php echo $payment['id']; ?></p>
                                <p class="mb-1"><strong>Tutar:</strong> <?php echo formatMoney($payment['amount']); ?></p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <p class="mb-1"><strong>Tarih:</strong> <?php echo formatDate($payment['created_at']); ?></p>
                                <p class="mb-1"><strong>Durum:</strong> <span class="badge bg-warning">Beklemede</span></p>
                            </div>
                        </div>

                        <!-- Banka Hesapları -->
                        <form method="POST" action="" id="paymentForm">
                            <div class="row g-4">
                                <?php foreach ($bank_accounts as $account): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="bank-card p-4" data-bank-id="<?php echo $account['id']; ?>">
                                            <div class="d-flex align-items-center mb-3">
                                                <?php if ($account['logo']): ?>
                                                    <img src="<?php echo $account['logo']; ?>" alt="<?php echo htmlspecialchars($account['bank_name']); ?>" class="bank-logo">
                                                <?php else: ?>
                                                    <h5><?php echo htmlspecialchars($account['bank_name']); ?></h5>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-2"><strong><?php echo htmlspecialchars($account['branch_name'] ? $account['branch_name'] . ' Şubesi' : ''); ?> <?php echo $account['branch_code'] ? '(' . $account['branch_code'] . ')' : ''; ?></strong></p>
                                            <p class="mb-2"><?php echo htmlspecialchars($account['account_name']); ?></p>
                                            <div class="d-flex align-items-center mb-2">
                                                <small class="text-muted me-2">IBAN:</small>
                                                <span class="me-2"><?php echo htmlspecialchars($account['iban']); ?></span>
                                                <i class="fas fa-copy iban-copy" data-clipboard-text="<?php echo htmlspecialchars($account['iban']); ?>" 
                                                   data-bs-toggle="tooltip" title="IBAN'ı Kopyala"></i>
                                            </div>
                                            <?php if ($account['account_number']): ?>
                                                <small class="text-muted d-block">Hesap No: <?php echo htmlspecialchars($account['account_number']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($account['description']): ?>
                                                <div class="mt-2 small text-muted"><?php echo htmlspecialchars($account['description']); ?></div>
                                            <?php endif; ?>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input bank-select" type="radio" name="bank_account_id" id="bank_<?php echo $account['id']; ?>" value="<?php echo $account['id']; ?>" required>
                                                <label class="form-check-label" for="bank_<?php echo $account['id']; ?>">
                                                    Bu hesaba ödeme yapacağım
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Ödeme Adımları -->
                            <div class="steps-container mt-5">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="step-icon">
                                                <i class="fas fa-money-bill-transfer"></i>
                                            </div>
                                            <h6>1. Havale/EFT Yap</h6>
                                            <p class="text-muted small">
                                                Yukarıdaki banka hesaplarından birine <?php echo formatMoney($payment['amount']); ?> tutarında 
                                                havale/EFT yapın.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="step-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <h6>2. Onay Bekleyin</h6>
                                            <p class="text-muted small">
                                                Ödemeniz kontrol edildikten sonra (1-15 dakika) bakiyeniz otomatik olarak yüklenecektir.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="step-icon">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <h6>3. Bakiyeniz Hazır</h6>
                                            <p class="text-muted small">
                                                Bakiyeniz yüklendiğinde SMS ve e-posta ile bilgilendirileceksiniz.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Önemli Notlar -->
                            <div class="alert alert-warning mt-4">
                                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Önemli Notlar</h6>
                                <ul class="mb-0">
                                    <li>Havale/EFT yaparken açıklama kısmına mutlaka <strong>"#<?php echo $payment['id']; ?>"</strong> yazın.</li>
                                    <li>Ödeme tam tutarda yapılmalıdır, eksik ödemeler işleme alınmaz.</li>
                                    <li>Mesai saatleri içinde ödemeler 15 dakika, mesai saatleri dışında 24 saat içinde işlenir.</li>
                                    <li>Sorun yaşarsanız 7/24 canlı destek hattımızla iletişime geçebilirsiniz.</li>
                                </ul>
                            </div>

                            <div class="mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="deposit.php" class="btn btn-light">
                                        <i class="fas fa-arrow-left"></i> Geri Dön
                                    </a>
                                    <button type="submit" name="confirm_payment" class="btn btn-primary" id="confirmButton" disabled>
                                        <i class="fas fa-check-circle"></i> Ödemeyi Onaylıyorum
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Clipboard.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // IBAN kopyalama
        var clipboard = new ClipboardJS('.iban-copy');
        
        clipboard.on('success', function(e) {
            var tooltip = bootstrap.Tooltip.getInstance(e.trigger);
            if (!tooltip) {
                tooltip = new bootstrap.Tooltip(e.trigger, {
                    title: 'Kopyalandı!',
                    placement: 'top',
                    trigger: 'manual'
                });
            } else {
                tooltip.setContent({ '.tooltip-inner': 'Kopyalandı!' });
            }
            tooltip.show();
            
            setTimeout(function() {
                tooltip.hide();
            }, 1000);
            
            e.clearSelection();
        });
        
        // Banka kartı seçimi
        const bankCards = document.querySelectorAll('.bank-card');
        const confirmButton = document.getElementById('confirmButton');
        
        bankCards.forEach(card => {
            card.addEventListener('click', function() {
                const bankId = this.dataset.bankId;
                const radio = document.getElementById('bank_' + bankId);
                
                // Tüm kartlardan selected sınıfını kaldır
                bankCards.forEach(c => c.classList.remove('selected'));
                
                // Seçilen kartı işaretle
                this.classList.add('selected');
                radio.checked = true;
                
                // Onay butonunu aktifleştir
                confirmButton.disabled = false;
            });
        });
        
        // Radyo butonları için olay dinleyicisi
        const radioButtons = document.querySelectorAll('.bank-select');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    // Tüm kartlardan selected sınıfını kaldır
                    bankCards.forEach(c => c.classList.remove('selected'));
                    
                    // Seçilen kartı işaretle
                    const card = document.querySelector(`.bank-card[data-bank-id="${this.value}"]`);
                    card.classList.add('selected');
                    
                    // Onay butonunu aktifleştir
                    confirmButton.disabled = false;
                }
            });
        });
    });
    </script>
</body>
</html> 