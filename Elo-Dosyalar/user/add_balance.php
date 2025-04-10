<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Bakiye Yükleme</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active text-muted">Bakiye Yükleme</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Kart -->
    <div class="row">
        <div class="col-xxl-8 mx-auto">
            <div class="card border-0 glass-effect" style="border-radius: 20px;">
                <div class="card-body bg-dark-gradient p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex flex-column h-100">
                                <div class="flex-grow-1">
                                    <h3 class="fw-medium text-capitalize mt-0 mb-2 text-glow">Bakiye Yükle</h3>
                                    <p class="font-18 text-muted">Güvenli ödeme yöntemleriyle hemen bakiye yükleyin.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <img src="../assets/img/characters/character3.png" alt="Add Balance" class="img-fluid floating-image" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bakiye Yükleme Formu -->
    <div class="row mt-4">
        <div class="col-xxl-8 mx-auto">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">Ödeme Detayları</h5>
                </div>
                <div class="card-body p-4">
                    <form id="paymentForm" action="process_payment.php" method="POST">
                        <!-- Tutar Seçimi -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label text-white mb-3">Yüklenecek Tutar</label>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="amount-card glass-effect text-center p-4" data-amount="50">
                                            <h4 class="text-white mb-2">50 ₺</h4>
                                            <small class="text-muted">Başlangıç Paketi</small>
                                            <input type="radio" name="amount" value="50" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="amount-card glass-effect text-center p-4" data-amount="100">
                                            <h4 class="text-white mb-2">100 ₺</h4>
                                            <small class="text-muted">Standart Paket</small>
                                            <input type="radio" name="amount" value="100" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="amount-card glass-effect text-center p-4" data-amount="250">
                                            <h4 class="text-white mb-2">250 ₺</h4>
                                            <small class="text-muted">Premium Paket</small>
                                            <input type="radio" name="amount" value="250" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="amount-card glass-effect text-center p-4" data-amount="custom">
                                            <h4 class="text-white mb-2">Özel</h4>
                                            <small class="text-muted">Tutar Belirle</small>
                                            <input type="radio" name="amount" value="custom" class="d-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Özel Tutar -->
                        <div class="row mb-4 custom-amount d-none">
                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label class="form-label text-white mb-2">Özel Tutar</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control glass-input" name="custom_amount" placeholder="Tutar giriniz" min="10" step="1">
                                        <span class="input-group-text glass-effect">₺</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ödeme Yöntemi -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label text-white mb-3">Ödeme Yöntemi</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="payment-method glass-effect p-4" data-method="credit_card">
                                            <div class="d-flex align-items-center">
                                                <div class="payment-icon me-3">
                                                    <i class="fas fa-credit-card fa-2x text-glow"></i>
                                                </div>
                                                <div>
                                                    <h6 class="text-white mb-1">Kredi Kartı</h6>
                                                    <small class="text-muted">Tüm kartlar desteklenir</small>
                                                </div>
                                                <input type="radio" name="payment_method" value="credit_card" class="d-none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-method glass-effect p-4" data-method="bank_transfer">
                                            <div class="d-flex align-items-center">
                                                <div class="payment-icon me-3">
                                                    <i class="fas fa-university fa-2x text-glow"></i>
                                                </div>
                                                <div>
                                                    <h6 class="text-white mb-1">Havale/EFT</h6>
                                                    <small class="text-muted">Tüm bankalar</small>
                                                </div>
                                                <input type="radio" name="payment_method" value="bank_transfer" class="d-none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-method glass-effect p-4" data-method="papara">
                                            <div class="d-flex align-items-center">
                                                <div class="payment-icon me-3">
                                                    <i class="fas fa-wallet fa-2x text-glow"></i>
                                                </div>
                                                <div>
                                                    <h6 class="text-white mb-1">Papara</h6>
                                                    <small class="text-muted">Hızlı Ödeme</small>
                                                </div>
                                                <input type="radio" name="payment_method" value="papara" class="d-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onay -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-glow btn-primary btn-lg px-5">
                                    <i class="fas fa-check me-2"></i>Ödemeyi Tamamla
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Theme */
.dark-theme {
    background-color: #0a0b1e;
    color: #fff;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Amount Cards */
.amount-card {
    cursor: pointer;
    border-radius: 15px;
    transition: all 0.3s ease;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.amount-card:hover {
    transform: translateY(-5px) rotateX(5deg);
    box-shadow: 0 15px 30px rgba(106, 17, 203, 0.2) !important;
}

.amount-card.selected {
    border: 2px solid var(--neon-blue);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
}

/* Payment Methods */
.payment-method {
    cursor: pointer;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.payment-method:hover {
    transform: translateX(5px);
    background: rgba(255, 255, 255, 0.1) !important;
}

.payment-method.selected {
    border: 2px solid var(--neon-blue);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
}

.payment-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
}

/* Form Elements */
.glass-input {
    background: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

.glass-input:focus {
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: var(--neon-blue);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
    color: white;
}

/* Animations */
@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.floating-image {
    animation: floating 3s ease-in-out infinite;
}

/* Glow Effects */
.text-glow {
    color: var(--neon-blue);
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

/* Background Gradients */
.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}
</style>

<script>
$(document).ready(function() {
    // Tutar seçimi
    $('.amount-card').click(function() {
        $('.amount-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
        
        // Özel tutar alanını göster/gizle
        if ($(this).data('amount') === 'custom') {
            $('.custom-amount').removeClass('d-none');
        } else {
            $('.custom-amount').addClass('d-none');
        }
    });
    
    // Ödeme yöntemi seçimi
    $('.payment-method').click(function() {
        $('.payment-method').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    // Form gönderimi
    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        
        // Form validasyonu
        if (!$('input[name="amount"]:checked').length) {
            Swal.fire({
                title: 'Hata!',
                text: 'Lütfen bir tutar seçin.',
                icon: 'error',
                background: '#1a1b3a',
                color: '#fff'
            });
            return;
        }
        
        if (!$('input[name="payment_method"]:checked').length) {
            Swal.fire({
                title: 'Hata!',
                text: 'Lütfen bir ödeme yöntemi seçin.',
                icon: 'error',
                background: '#1a1b3a',
                color: '#fff'
            });
            return;
        }
        
        // Özel tutar kontrolü
        if ($('input[name="amount"]:checked').val() === 'custom') {
            let customAmount = $('input[name="custom_amount"]').val();
            if (!customAmount || customAmount < 10) {
                Swal.fire({
                    title: 'Hata!',
                    text: 'Lütfen geçerli bir tutar girin (minimum 10₺).',
                    icon: 'error',
                    background: '#1a1b3a',
                    color: '#fff'
                });
                return;
            }
        }
        
        // Form gönder
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: 'Ödeme işlemi başlatıldı.',
                        icon: 'success',
                        background: '#1a1b3a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = response.redirect_url;
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: response.message,
                        icon: 'error',
                        background: '#1a1b3a',
                        color: '#fff'
                    });
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 