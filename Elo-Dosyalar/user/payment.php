<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Sadece kullanıcıların erişimine izin ver
if (!isUser()) {
    redirect('../login.php');
}

// Sipariş ID'sini kontrol et
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $_SESSION['error'] = "Geçersiz sipariş ID'si.";
    redirect('orders.php');
}

// Siparişi getir
try {
    $stmt = $conn->prepare("
        SELECT o.*, g.name as game_name, 
               cr.name as current_rank, tr.name as target_rank
        FROM orders o
        LEFT JOIN games g ON o.game_id = g.id
        LEFT JOIN ranks cr ON o.current_rank_id = cr.id
        LEFT JOIN ranks tr ON o.target_rank_id = tr.id
        WHERE o.id = ? AND o.user_id = ? AND o.status = 'pending'
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error'] = "Sipariş bulunamadı veya ödeme yapılamaz.";
        redirect('orders.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Sipariş bilgileri getirilirken bir hata oluştu.";
    redirect('orders.php');
}

require_once 'includes/header.php';
?>

    <div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ödeme - Sipariş #<?php echo $order_id; ?></h5>
        </div>
                <div class="card-body p-4">
                    <!-- Sipariş Özeti -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">Sipariş Özeti</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Oyun:</strong> <?php echo htmlspecialchars($order['game_name']); ?></p>
                                <p><strong>Mevcut Rank:</strong> <?php echo htmlspecialchars($order['current_rank']); ?></p>
                                <p><strong>Hedef Rank:</strong> <?php echo htmlspecialchars($order['target_rank']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tutar:</strong> <?php echo number_format($order['total_price'], 2, ',', '.'); ?> ₺</p>
                                <p><strong>Sipariş Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Ödeme Yöntemleri -->
                    <div>
                        <h5 class="border-bottom pb-2 mb-3">Ödeme Yöntemi Seçin</h5>
                        
                        <div class="payment-methods">
                        <div class="row g-3">
                            <!-- Kredi Kartı -->
                            <div class="col-md-6">
                                    <div class="card payment-option h-100" data-payment="credit_card">
                                        <div class="card-body p-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                                <label class="form-check-label w-100" for="credit_card">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-credit-card fa-2x text-primary me-3"></i>
                                                        <div>
                                                            <h6 class="mb-0">Kredi Kartı</h6>
                                                            <small class="text-muted">Visa, Mastercard, Troy</small>
                                                        </div>
                                                    </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Havale/EFT -->
                            <div class="col-md-6">
                                    <div class="card payment-option h-100" data-payment="bank_transfer">
                                        <div class="card-body p-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                                <label class="form-check-label w-100" for="bank_transfer">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-university fa-2x text-primary me-3"></i>
                                                        <div>
                                                            <h6 class="mb-0">Havale / EFT</h6>
                                                            <small class="text-muted">Banka havalesi ile ödeme</small>
                                                        </div>
                                                    </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Bakiye -->
                            <div class="col-md-6">
                                    <div class="card payment-option h-100" data-payment="balance">
                                        <div class="card-body p-3">
                                        <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="balance" value="balance">
                                                <label class="form-check-label w-100" for="balance">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-wallet fa-2x text-primary me-3"></i>
                                                        <div>
                                                            <h6 class="mb-0">Bakiye</h6>
                                                            <small class="text-muted">Mevcut bakiyeniz: <?php echo number_format(getUserBalance($_SESSION['user_id']), 2, ',', '.'); ?> ₺</small>
                                                        </div>
                                                    </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                </div>
                                
                                <!-- Papara -->
                                <div class="col-md-6">
                                    <div class="card payment-option h-100" data-payment="papara">
                                        <div class="card-body p-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="papara" value="papara">
                                                <label class="form-check-label w-100" for="papara">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-money-bill-wave fa-2x text-primary me-3"></i>
                                                        <div>
                                                            <h6 class="mb-0">Papara</h6>
                                                            <small class="text-muted">Papara ile hızlı ödeme</small>
                                                        </div>
                                                    </div>
                                                </label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

                        <!-- Ödeme Formu -->
                        <div class="mt-4 payment-forms">
                            <!-- Kredi Kartı Formu -->
                            <div class="payment-form" id="credit_card_form">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="process_payment.php" method="post">
                                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                            <input type="hidden" name="payment_method" value="credit_card">
                                            
                                            <div class="mb-3">
                                                <label for="card_number" class="form-label">Kart Numarası</label>
                                                <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="expiry_date" class="form-label">Son Kullanma Tarihi</label>
                                                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cvv" class="form-label">CVV</label>
                                                    <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="card_holder" class="form-label">Kart Sahibi</label>
                                                <input type="text" class="form-control" id="card_holder" name="card_holder" placeholder="Ad Soyad" required>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Ödemeyi Tamamla</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Havale/EFT Formu -->
                            <div class="payment-form d-none" id="bank_transfer_form">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">Banka Hesap Bilgileri</h6>
                                            <p class="mb-0">Aşağıdaki banka hesaplarından birine ödeme yapabilirsiniz. Açıklama kısmına sipariş numaranızı (<strong><?php echo $order_id; ?></strong>) yazmayı unutmayınız.</p>
                                        </div>
                                        
                                        <div class="bank-accounts">
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <h6>Ziraat Bankası</h6>
                                                    <p class="mb-1"><strong>Hesap Sahibi:</strong> <?php echo getSetting('company_name'); ?></p>
                                                    <p class="mb-1"><strong>IBAN:</strong> TR12 3456 7890 1234 5678 9012 34</p>
                                                </div>
                                            </div>
                                            
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <h6>Garanti Bankası</h6>
                                                    <p class="mb-1"><strong>Hesap Sahibi:</strong> <?php echo getSetting('company_name'); ?></p>
                                                    <p class="mb-1"><strong>IBAN:</strong> TR98 7654 3210 9876 5432 1098 76</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <form action="process_payment.php" method="post" class="mt-3">
                                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                            <input type="hidden" name="payment_method" value="bank_transfer">
                                            
                                            <div class="mb-3">
                                                <label for="transfer_name" class="form-label">Gönderen Adı</label>
                                                <input type="text" class="form-control" id="transfer_name" name="transfer_name" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="transfer_date" class="form-label">Havale Tarihi</label>
                                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="transfer_note" class="form-label">Not (İsteğe Bağlı)</label>
                                                <textarea class="form-control" id="transfer_note" name="transfer_note" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Bildirimi Gönder</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                    </div>
                            
                            <!-- Bakiye Formu -->
                            <div class="payment-form d-none" id="balance_form">
                                <div class="card">
                    <div class="card-body">
                                        <?php
                                        $user_balance = getUserBalance($_SESSION['user_id']);
                                        if ($user_balance >= $order['total_price']) {
                                        ?>
                                            <div class="alert alert-success">
                                                <h6 class="alert-heading">Bakiye Bilgisi</h6>
                                                <p class="mb-0">Mevcut bakiyeniz: <strong><?php echo number_format($user_balance, 2, ',', '.'); ?> ₺</strong></p>
                                                <p class="mb-0">Sipariş tutarı: <strong><?php echo number_format($order['total_price'], 2, ',', '.'); ?> ₺</strong></p>
                                                <p class="mb-0">İşlem sonrası bakiyeniz: <strong><?php echo number_format($user_balance - $order['total_price'], 2, ',', '.'); ?> ₺</strong></p>
                                            </div>
                                            
                                            <form action="process_payment.php" method="post">
                                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                <input type="hidden" name="payment_method" value="balance">
                                                
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">Bakiye ile Öde</button>
                                                </div>
                                            </form>
                                        <?php
                                        } else {
                                        ?>
                                            <div class="alert alert-danger">
                                                <h6 class="alert-heading">Yetersiz Bakiye</h6>
                                                <p class="mb-0">Mevcut bakiyeniz: <strong><?php echo number_format($user_balance, 2, ',', '.'); ?> ₺</strong></p>
                                                <p class="mb-0">Sipariş tutarı: <strong><?php echo number_format($order['total_price'], 2, ',', '.'); ?> ₺</strong></p>
                                                <p class="mb-0">Gereken ek bakiye: <strong><?php echo number_format($order['total_price'] - $user_balance, 2, ',', '.'); ?> ₺</strong></p>
                        </div>
                                            
                                            <div class="d-grid">
                                                <a href="deposit.php" class="btn btn-primary">Bakiye Yükle</a>
                        </div>
                                        <?php
                                        }
                                        ?>
                        </div>
                    </div>
                </div>

                            <!-- Papara Formu -->
                            <div class="payment-form d-none" id="papara_form">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">Papara Bilgileri</h6>
                                            <p class="mb-0">Aşağıdaki Papara numarasına ödeme yapabilirsiniz. Açıklama kısmına sipariş numaranızı (<strong><?php echo $order_id; ?></strong>) yazmayı unutmayınız.</p>
                                        </div>
                                        
                                        <div class="card mb-3">
                    <div class="card-body">
                                                <h6>Papara Hesabı</h6>
                                                <p class="mb-1"><strong>Hesap Sahibi:</strong> <?php echo getSetting('company_name'); ?></p>
                                                <p class="mb-1"><strong>Papara No:</strong> 1234567890</p>
                                            </div>
                                        </div>
                                        
                                        <form action="process_payment.php" method="post">
                                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                            <input type="hidden" name="payment_method" value="papara">
                                            
                                            <div class="mb-3">
                                                <label for="papara_name" class="form-label">Gönderen Adı</label>
                                                <input type="text" class="form-control" id="papara_name" name="papara_name" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="papara_number" class="form-label">Papara Numarası</label>
                                                <input type="text" class="form-control" id="papara_number" name="papara_number" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="papara_note" class="form-label">Not (İsteğe Bağlı)</label>
                                                <textarea class="form-control" id="papara_note" name="papara_note" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Bildirimi Gönder</button>
                                            </div>
                                        </form>
                                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Siparişlere Dön
                        </a>
                        <div class="text-end">
                            <p class="mb-0 fw-bold">Toplam: <?php echo number_format($order['total_price'], 2, ',', '.'); ?> ₺</p>
                        </div>
                    </div>
                </div>
                </div>
                </div>
            </div>
        </div>

<style>
.payment-option {
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #dee2e6;
}

.payment-option:hover {
    border-color: #6c757d;
}

.payment-option .form-check-input:checked ~ .form-check-label {
    font-weight: bold;
}

.payment-option .form-check-input:checked ~ .form-check-label i {
    color: #0d6efd;
}

.payment-option .form-check {
    padding-left: 0;
}

.payment-option .form-check-input {
    position: absolute;
    margin-top: 0.3rem;
    margin-left: 0;
}

.payment-option .form-check-label {
    padding-left: 1.5rem;
    cursor: pointer;
}
</style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Ödeme yöntemi seçimi
    const paymentOptions = document.querySelectorAll('.payment-option');
    const paymentForms = document.querySelectorAll('.payment-form');
    
    function showPaymentForm(paymentMethod) {
        paymentForms.forEach(form => {
            if (form.id === paymentMethod + '_form') {
                form.classList.remove('d-none');
            } else {
                form.classList.add('d-none');
            }
        });
    }
    
    // İlk form gösterimi
    showPaymentForm('credit_card');
    
    // Ödeme yöntemi değiştiğinde
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', function() {
            showPaymentForm(this.value);
        });
    });
    
    // Ödeme seçeneği kartına tıklandığında
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            const paymentMethod = this.dataset.payment;
            document.getElementById(paymentMethod).checked = true;
            showPaymentForm(paymentMethod);
        });
        });
    });
    </script>

<?php require_once 'includes/footer.php'; ?> 