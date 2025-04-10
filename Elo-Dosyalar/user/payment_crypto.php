<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    header("Location: ../login.php");
    exit;
}

// Ödeme ID'sini al
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ödeme detaylarını getir
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.user_id = ? AND p.payment_method = 'crypto'
    ");
    $stmt->execute([$payment_id, $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $_SESSION['error'] = "Geçersiz ödeme!";
        header("Location: payments.php");
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Ödeme detayları alınamadı!";
    header("Location: payments.php");
    exit;
}

// Kripto cüzdanları
$crypto_wallets = [
    'btc' => [
        'name' => 'Bitcoin',
        'symbol' => 'BTC',
        'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
        'network' => 'Bitcoin Network',
        'icon' => 'fab fa-bitcoin'
    ],
    'eth' => [
        'name' => 'Ethereum',
        'symbol' => 'ETH',
        'address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
        'network' => 'ERC-20',
        'icon' => 'fab fa-ethereum'
    ],
    'usdt' => [
        'name' => 'USDT',
        'symbol' => 'USDT',
        'address' => 'TKVyVXrZXpEgbWxqWKiHKZP3CTtEqcMMro',
        'network' => 'TRC-20',
        'icon' => 'fas fa-dollar-sign'
    ]
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kripto Para ile Ödeme - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <style>
    .crypto-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .crypto-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .crypto-card.selected {
        border: 2px solid var(--primary-color);
    }
    .wallet-address {
        font-family: monospace;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        word-break: break-all;
    }
    .copy-button {
        cursor: pointer;
    }
    .copy-button:hover {
        color: var(--primary-color);
    }
    .qr-code {
        max-width: 200px;
        margin: 0 auto;
    }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="../assets/img/logo.png" alt="Logo" height="60">
                </div>

                <!-- Ödeme Detayları -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Ödeme Detayları</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Ödeme ID:</strong> #<?php echo $payment['id']; ?></p>
                                <p><strong>Tutar:</strong> <?php echo number_format($payment['amount'], 2); ?> TL</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></p>
                                <p><strong>Durum:</strong> <span class="badge bg-warning">Beklemede</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kripto Cüzdanları -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Kripto Para ile Ödeme</h5>
                    </div>
                    <div class="card-body">
                        <!-- Kripto Para Seçimi -->
                        <div class="row mb-4">
                            <?php foreach ($crypto_wallets as $key => $wallet): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card crypto-card" data-crypto="<?php echo $key; ?>">
                                    <div class="card-body text-center">
                                        <i class="<?php echo $wallet['icon']; ?> fa-2x mb-2"></i>
                                        <h6 class="mb-0"><?php echo $wallet['name']; ?></h6>
                                        <small class="text-muted"><?php echo $wallet['network']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Cüzdan Detayları -->
                        <?php foreach ($crypto_wallets as $key => $wallet): ?>
                        <div class="wallet-details" id="<?php echo $key; ?>-details" style="display: none;">
                            <h6 class="mb-3"><?php echo $wallet['name']; ?> Cüzdan Adresi</h6>
                            
                            <!-- QR Kod -->
                            <div class="text-center mb-3">
                                <div class="qr-code">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $wallet['address']; ?>" 
                                         alt="<?php echo $wallet['name']; ?> QR Code" class="img-fluid">
                                </div>
                            </div>
                            
                            <!-- Cüzdan Adresi -->
                            <div class="wallet-address mb-3">
                                <?php echo $wallet['address']; ?>
                                <i class="fas fa-copy copy-button float-end" 
                                   data-clipboard-text="<?php echo $wallet['address']; ?>" 
                                   data-bs-toggle="tooltip" 
                                   title="Kopyala"></i>
                            </div>
                            
                            <!-- Uyarılar -->
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">Önemli Notlar:</h6>
                                <ul class="mb-0">
                                    <li>Sadece <?php echo $wallet['network']; ?> ağını kullanın.</li>
                                    <li>Minimum transfer tutarı: <?php echo number_format($payment['amount'], 2); ?> TL değerine eşit <?php echo $wallet['symbol']; ?></li>
                                    <li>Transfer açıklamasına ödeme ID'nizi (#<?php echo $payment['id']; ?>) yazmayı unutmayın.</li>
                                    <li>Transfer tamamlandıktan sonra 10-30 dakika içinde bakiyeniz hesabınıza yansıyacaktır.</li>
                                </ul>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Yardım -->
                        <div class="text-center mt-4">
                            <p class="mb-2">Sorularınız mı var?</p>
                            <a href="support.php" class="btn btn-outline-primary">
                                <i class="fas fa-headset"></i> Destek Al
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Geri Dön -->
                <div class="text-center mt-4">
                    <a href="payments.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Ödemelerime Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Clipboard.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clipboard.js başlat
        new ClipboardJS('.copy-button');
        
        // Tooltips başlat
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Kopyalama butonu tıklama
        document.querySelectorAll('.copy-button').forEach(button => {
            button.addEventListener('click', function() {
                const tooltip = bootstrap.Tooltip.getInstance(this);
                tooltip.setContent({ '.tooltip-inner': 'Kopyalandı!' });
                
                setTimeout(() => {
                    tooltip.setContent({ '.tooltip-inner': 'Kopyala' });
                }, 1000);
            });
        });
        
        // Kripto para seçimi
        document.querySelectorAll('.crypto-card').forEach(card => {
            card.addEventListener('click', function() {
                // Seçili kartı güncelle
                document.querySelectorAll('.crypto-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                // Cüzdan detaylarını göster/gizle
                const selectedCrypto = this.dataset.crypto;
                document.querySelectorAll('.wallet-details').forEach(details => {
                    details.style.display = 'none';
                });
                document.getElementById(selectedCrypto + '-details').style.display = 'block';
            });
        });
        
        // İlk kripto parayı seç
        document.querySelector('.crypto-card').click();
    });
    </script>
</body>
</html> 