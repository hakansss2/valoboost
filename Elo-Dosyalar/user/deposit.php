<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

// Kullanıcı bilgilerini getir
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Kullanıcı bilgileri getirilirken bir hata oluştu.";
    redirect('index.php');
}

// Banka hesaplarını getir
try {
    $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY bank_name");
    $stmt->execute();
    $bank_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $bank_accounts = [];
}

// Ödeme bildirimi oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    
    // Ödeme yöntemini iki kaynaktan kontrol et
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $payment_method_confirmed = isset($_POST['payment_method_confirmed']) ? $_POST['payment_method_confirmed'] : '';
    
    // Eğer onaylanan değer varsa ve geçerliyse, onu kullan
    if (!empty($payment_method_confirmed) && in_array($payment_method_confirmed, ['credit_card', 'bank_transfer'])) {
        $payment_method = $payment_method_confirmed;
    }
    
    $bank_account_id = isset($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : 0;
    
    // Debug: POST verilerini kontrol et
    error_log("DEBUG: POST payment_method = " . (isset($_POST['payment_method']) ? $_POST['payment_method'] : 'not set'));
    error_log("DEBUG: POST payment_method_confirmed = " . (isset($_POST['payment_method_confirmed']) ? $_POST['payment_method_confirmed'] : 'not set'));
    error_log("DEBUG: Final payment_method = " . $payment_method);
    
    $errors = [];

    // Validasyon
    if ($amount <= 0) {
        $errors[] = "Geçerli bir tutar girin.";
    }

    if (!in_array($payment_method, ['credit_card', 'bank_transfer'])) {
        $errors[] = "Geçerli bir ödeme yöntemi seçin: " . $payment_method;
        error_log("DEBUG: Geçersiz ödeme yöntemi: " . $payment_method);
    }

    if ($payment_method === 'bank_transfer' && $bank_account_id <= 0) {
        $errors[] = "Lütfen bir banka hesabı seçin.";
    }

    if (empty($errors)) {
        try {
            // Banka hesap bilgilerini getir (banka havalesi seçildiyse)
            $bankInfo = '';
            if ($payment_method === 'bank_transfer' && $bank_account_id > 0) {
                $bankStmt = $conn->prepare("SELECT bank_name, account_name, iban FROM bank_accounts WHERE id = ?");
                $bankStmt->execute([$bank_account_id]);
                $bankAccount = $bankStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($bankAccount) {
                    $bankInfo = "Banka: " . $bankAccount['bank_name'] . ", Hesap: " . $bankAccount['account_name'] . ", IBAN: " . $bankAccount['iban'];
                }
            }
            
            // Ödeme yöntemini belirle
            $paymentMethodValue = ($payment_method === 'bank_transfer') ? 'bank_transfer' : 'credit_card';
            
            // Ödeme kaydı oluştur
            $stmt = $conn->prepare("
                INSERT INTO payments (user_id, amount, payment_method, status, created_at, description)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            
            // Ödeme durumunu belirle
            $status = ($payment_method === 'bank_transfer') ? 'waiting_confirmation' : 'pending';
            
            $stmt->execute([$_SESSION['user_id'], $amount, $paymentMethodValue, $status, $bankInfo]);
            $payment_id = $conn->lastInsertId();
            
            if ($payment_method === 'credit_card') {
                // Kredi kartı ödemesi için yönlendirme
                redirect("payment_cc.php?payment_id=$payment_id");
            } else {
                // Bildirim oluştur
                createNotification($_SESSION['user_id'], 'Ödeme onayı bekleniyor', 'Banka havalesi ile yaptığınız ödeme onay bekliyor. En kısa sürede işleme alınacaktır.');
                
                // Admin için bildirim oluştur
                $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $admin_id) {
                    createNotification($admin_id, 'Yeni ödeme onayı', 'Yeni bir banka havalesi onayı bekliyor. Ödeme ID: #' . $payment_id);
                }
                
                $_SESSION['success'] = "Ödeme bildiriminiz başarıyla oluşturuldu. Onay sürecine alındı.";
                redirect('payments.php');
            }
        } catch(PDOException $e) {
            // Hata mesajını analiz et
            $errorMessage = $e->getMessage();
            
            // Eğer 'description' sütunu da yoksa, daha basit bir sorgu dene
            if (strpos($errorMessage, "Unknown column 'description'") !== false) {
                try {
                    // Ödeme yöntemini belirle
                    $paymentMethodValue = ($payment_method === 'bank_transfer') ? 'bank_transfer' : 'credit_card';
                    
                    // En basit sorgu
                    $stmt = $conn->prepare("
                        INSERT INTO payments (user_id, amount, payment_method, status, created_at)
                        VALUES (?, ?, ?, 'waiting_confirmation', NOW())
                    ");
                    
                    $stmt->execute([$_SESSION['user_id'], $amount, $paymentMethodValue]);
                    $payment_id = $conn->lastInsertId();
                    
                    if ($payment_method === 'credit_card') {
                        // Kredi kartı ödemesi için yönlendirme
                        redirect("payment_cc.php?payment_id=$payment_id");
                    } else {
                        // Bildirim oluştur
                        createNotification($_SESSION['user_id'], 'Ödeme onayı bekleniyor', 'Banka havalesi ile yaptığınız ödeme onay bekliyor. En kısa sürede işleme alınacaktır.');
                        
                        // Admin için bildirim oluştur
                        $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($admins as $admin_id) {
                            createNotification($admin_id, 'Yeni ödeme onayı', 'Yeni bir banka havalesi onayı bekliyor. Ödeme ID: #' . $payment_id);
                        }
                        
                        $_SESSION['success'] = "Ödeme bildiriminiz başarıyla oluşturuldu. Onay sürecine alındı.";
                        redirect('payments.php');
                    }
                } catch(PDOException $e2) {
                    $errors[] = "Ödeme kaydı oluşturulurken bir hata oluştu: " . $e2->getMessage();
                    $errors[] = "Lütfen site yöneticisiyle iletişime geçin ve bu hatayı bildirin.";
                }
            } else {
                $errors[] = "Ödeme kaydı oluşturulurken bir hata oluştu: " . $errorMessage;
                $errors[] = "Lütfen site yöneticisiyle iletişime geçin ve bu hatayı bildirin.";
                
                // Debug: Tablo yapısını göster
                try {
                    $tableInfo = $conn->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC);
                    $errors[] = "<strong>Debug - Tablo Yapısı:</strong>";
                    foreach ($tableInfo as $column) {
                        $errors[] = "Sütun: " . $column['Field'] . " - Tip: " . $column['Type'];
                    }
                } catch(PDOException $debugError) {
                    $errors[] = "Tablo yapısı alınamadı: " . $debugError->getMessage();
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 dark-theme">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card glass-effect">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1 text-white">Bakiye Yükle</h2>
                            <p class="text-muted mb-0">Hesabınıza bakiye yükleyerek hizmetlerimizden faydalanabilirsiniz.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="payments.php" class="btn btn-glow btn-primary">
                                <i class="fas fa-history me-2"></i>Ödeme Geçmişi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-glass fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-glass fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-glass fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card glass-effect">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-wallet me-2"></i>
                        Ödeme Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <form id="paymentForm" method="POST" class="payment-form">
                        <input type="hidden" name="create_payment" value="1">
                        
                        <div class="mb-4">
                            <label for="amount" class="form-label text-white">Yüklenecek Tutar (₺)</label>
                            <div class="input-group">
                                <span class="input-group-text glass-effect"><i class="fas fa-lira-sign"></i></span>
                                <input type="number" class="form-control form-control-lg glass-effect text-white" id="amount" name="amount" min="10" step="10" value="100" required>
                            </div>
                            <div class="form-text text-muted">Minimum yükleme tutarı 10₺'dir.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white">Ödeme Yöntemi</label>
                            <div class="row g-3 mb-4">
                                <!-- Kredi Kartı -->
                                <div class="col-md-6">
                                    <div class="card payment-method-card glass-effect h-100" data-method="credit_card">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="fas fa-credit-card fa-4x text-glow"></i>
                                            </div>
                                            <h5 class="text-white">Kredi Kartı</h5>
                                            <p class="text-muted small">Tüm kredi kartları ile güvenli ödeme yapın.</p>
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                                <label class="form-check-label text-white" for="credit_card">
                                                    Seç
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Banka Havalesi -->
                                <div class="col-md-6">
                                    <div class="card payment-method-card glass-effect h-100" data-method="bank_transfer">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="fas fa-university fa-4x text-glow"></i>
                                            </div>
                                            <h5 class="text-white">Banka Havalesi</h5>
                                            <p class="text-muted small">Banka hesaplarımıza havale/EFT yapın.</p>
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                                <label class="form-check-label text-white" for="bank_transfer">
                                                    Seç
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banka Hesapları -->
                            <div id="bankAccountsSection" class="mt-4" style="display: none;">
                                <h5 class="text-white mb-3">Banka Hesabı Seçin</h5>
                                
                                <?php if (empty($bank_accounts)): ?>
                                    <div class="alert alert-warning alert-glass">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Şu anda aktif banka hesabı bulunmamaktadır. Lütfen site yöneticisiyle iletişime geçin veya farklı bir ödeme yöntemi seçin.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($bank_accounts as $account): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card bank-account-card glass-effect h-100">
                                                    <div class="card-body p-4">
                                                        <h5 class="text-white mb-3"><?php echo htmlspecialchars($account['bank_name']); ?></h5>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($account['account_name']); ?></p>
                                                        <p class="mb-2 text-glow"><?php echo htmlspecialchars($account['iban']); ?></p>
                                                        <?php if ($account['account_number']): ?>
                                                            <small class="text-muted d-block">Hesap No: <?php echo htmlspecialchars($account['account_number']); ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($account['description']): ?>
                                                            <div class="mt-2 small text-muted"><?php echo htmlspecialchars($account['description']); ?></div>
                                                        <?php endif; ?>
                                                        <div class="form-check mt-3">
                                                            <input class="form-check-input bank-select" type="radio" name="bank_account_id" id="bank_<?php echo $account['id']; ?>" value="<?php echo $account['id']; ?>" required>
                                                            <label class="form-check-label text-white" for="bank_<?php echo $account['id']; ?>">
                                                                Bu hesaba ödeme yapacağım
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-glow btn-primary btn-lg" id="submitButton">
                                <span id="buttonText">Ödemeye Devam Et</span>
                                <span id="paymentAmount" class="ms-2">100,00 ₺</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --dark-bg: #0a0b1e;
    --card-bg: rgba(255, 255, 255, 0.05);
    --text-color: #ffffff;
    --border-color: rgba(255, 255, 255, 0.1);
    --hover-color: rgba(255, 255, 255, 0.1);
    --glow-color: #4a90e2;
}

.dark-theme {
    background-color: var(--dark-bg);
    color: var(--text-color);
}

.glass-effect {
    background: var(--card-bg) !important;
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.bg-dark-gradient {
    background: linear-gradient(45deg, #2c3e50, #3498db);
}

.card {
    border: none;
    transition: transform 0.3s ease;
}

.payment-method-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(74, 144, 226, 0.2) !important;
}

.payment-method-card.selected {
    border: 2px solid var(--glow-color);
    box-shadow: 0 0 20px rgba(74, 144, 226, 0.3);
}

.bank-account-card {
    transition: all 0.3s ease;
}

.bank-account-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(74, 144, 226, 0.2) !important;
}

.text-glow {
    color: var(--glow-color);
    text-shadow: 0 0 10px rgba(74, 144, 226, 0.5);
}

.form-control {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: var(--glow-color);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(74, 144, 226, 0.4);
}

.alert-glass {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
}

.input-group-text {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.form-check-input:checked {
    background-color: var(--glow-color);
    border-color: var(--glow-color);
}

.form-check-input:focus {
    border-color: var(--glow-color);
    box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentCards = document.querySelectorAll('.payment-method-card');
    const bankAccountsSection = document.getElementById('bankAccountsSection');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const paymentAmount = document.getElementById('paymentAmount');
    const amountInput = document.getElementById('amount');
    const paymentForm = document.getElementById('paymentForm');
    
    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        paymentAmount.textContent = amount.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
    });
    
    const creditCardRadio = document.getElementById('credit_card');
    if (creditCardRadio) {
        creditCardRadio.checked = true;
        const creditCardCard = document.querySelector('.payment-method-card[data-method="credit_card"]');
        if (creditCardCard) {
            creditCardCard.classList.add('selected');
        }
        bankAccountsSection.style.display = 'none';
        buttonText.textContent = 'Ödemeye Devam Et';
    }
    
    paymentCards.forEach(card => {
        card.addEventListener('click', function() {
            const method = this.dataset.method;
            const radio = this.querySelector('input[type="radio"]');
            
            paymentCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            radio.checked = true;
            
            if (method === 'bank_transfer') {
                bankAccountsSection.style.display = 'block';
                buttonText.textContent = 'Ödeme Bildirimi Oluştur';
            } else {
                bankAccountsSection.style.display = 'none';
                buttonText.textContent = 'Ödemeye Devam Et';
            }
        });
    });
    
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            alert('Lütfen bir ödeme yöntemi seçin.');
            return false;
        }
        
        const amount = parseFloat(amountInput.value);
        if (isNaN(amount) || amount < 10) {
            alert('Lütfen geçerli bir tutar girin (minimum 10₺).');
            return false;
        }
        
        const formData = new FormData(this);
        formData.append('payment_method_confirmed', selectedMethod.value);
        
        if (selectedMethod.value === 'bank_transfer') {
            const selectedBank = document.querySelector('input[name="bank_account_id"]:checked');
            if (!selectedBank) {
                alert('Lütfen bir banka hesabı seçin.');
                return false;
            }
            formData.append('bank_account_id', selectedBank.value);
        }
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> İşleniyor...';
        
        fetch('deposit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text().then(html => {
                    document.documentElement.innerHTML = html;
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitButton.disabled = false;
            submitButton.innerHTML = `${buttonText.textContent} <span class="ms-2">${paymentAmount.textContent}</span>`;
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 