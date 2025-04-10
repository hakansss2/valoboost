<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/paytr_config.php';

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
        SELECT p.*, u.username, u.email, u.name, u.phone
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.user_id = ? AND p.payment_method = 'credit_card'
        AND p.status = 'pending'
    ");
    $stmt->execute([$payment_id, $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $_SESSION['error'] = "Geçerli bir ödeme bulunamadı.";
        redirect('deposit.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Ödeme bilgileri getirilirken bir hata oluştu.";
    redirect('deposit.php');
}

// PayTR için gerekli parametreleri hazırla
$merchant_id = PAYTR_MERCHANT_ID;
$merchant_key = PAYTR_MERCHANT_KEY;
$merchant_salt = PAYTR_MERCHANT_SALT;

// Sipariş numarası: ödeme ID'si
$merchant_oid = $payment_id;

// Kullanıcı bilgileri
$email = $payment['email'];
$payment_amount = $payment['amount'] * 100; // Kuruş cinsinden (örn: 100 TL için 10000)
$user_name = $payment['name'] ?: $payment['username'];
$user_address = "Adres bilgisi";
$user_phone = $payment['phone'] ?: "5551234567";

// Kullanıcı IP adresi
$user_ip = $_SERVER['REMOTE_ADDR'];

// Sepet içeriği
$user_basket = base64_encode(json_encode([
    ["Bakiye Yükleme", $payment['amount'], 1] // [Ürün Adı, Fiyat, Adet]
]));

// Taksit seçenekleri
$no_installment = 1; // Taksit yapılmasını istemiyorsanız 1 yapın
$max_installment = 0; // Maksimum taksit sayısı (0: taksit yok)

// Diğer ayarlar
$currency = "TL";
$test_mode = PAYTR_TEST_MODE;
$non_3d = 0; // 3D secure kullanımı (0: 3D, 1: 3D olmadan)
$merchant_ok_url = PAYTR_SUCCESS_URL;
$merchant_fail_url = PAYTR_FAIL_URL;
$timeout_limit = 30; // Dakika cinsinden ödeme süresi sınırı
$debug_on = 1; // Hata mesajlarının gösterilmesi (1: göster, 0: gizle)
$lang = PAYTR_LANG;
$payment_theme = PAYTR_PAYMENT_THEME;

// Hash oluşturma için parametreler
$hash_params = [
    'user_ip' => $user_ip,
    'merchant_oid' => $merchant_oid,
    'email' => $email,
    'payment_amount' => $payment_amount,
    'user_basket' => $user_basket,
    'no_installment' => $no_installment,
    'max_installment' => $max_installment,
    'currency' => $currency,
    'test_mode' => $test_mode,
    'non_3d' => $non_3d
];

// Hash oluştur
$paytr_token = generatePaytrHash($hash_params);

// PayTR'ye gönderilecek form parametreleri
$post_params = [
    'merchant_id' => $merchant_id,
    'user_ip' => $user_ip,
    'merchant_oid' => $merchant_oid,
    'email' => $email,
    'payment_amount' => $payment_amount,
    'paytr_token' => $paytr_token,
    'user_basket' => $user_basket,
    'debug_on' => $debug_on,
    'no_installment' => $no_installment,
    'max_installment' => $max_installment,
    'user_name' => $user_name,
    'user_address' => $user_address,
    'user_phone' => $user_phone,
    'merchant_ok_url' => $merchant_ok_url,
    'merchant_fail_url' => $merchant_fail_url,
    'timeout_limit' => $timeout_limit,
    'currency' => $currency,
    'test_mode' => $test_mode,
    'lang' => $lang,
    'payment_theme' => $payment_theme
];

// PayTR API'sine istek gönder
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);

// CURL hata ayıklama
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$result = curl_exec($ch);

// CURL hatası kontrolü
if ($result === false) {
    $curl_error = curl_error($ch);
    error_log("CURL Error: " . $curl_error);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("CURL Verbose Log: " . $verboseLog);
    
    $_SESSION['error'] = "Ödeme sayfası oluşturulurken bir hata oluştu: " . $curl_error;
    redirect('deposit.php');
}

curl_close($ch);

// Sonucu JSON olarak çözümle
$result_json = json_decode($result, true);

// JSON çözümleme hatası kontrolü
if ($result_json === null) {
    error_log("JSON Decode Error: " . json_last_error_msg());
    error_log("Raw Response: " . $result);
    
    $_SESSION['error'] = "Ödeme sayfası oluşturulurken bir hata oluştu: Geçersiz yanıt alındı.";
    redirect('deposit.php');
}

// Token alınamadıysa hata göster
if (!isset($result_json['status']) || $result_json['status'] !== 'success') {
    $error_reason = isset($result_json['reason']) ? $result_json['reason'] : 'Bilinmeyen hata';
    error_log("PayTR Error: " . $error_reason);
    error_log("PayTR Response: " . print_r($result_json, true));
    
    $_SESSION['error'] = "Ödeme sayfası oluşturulurken bir hata oluştu: " . $error_reason;
    redirect('deposit.php');
}

// Token alındıysa iframe URL'ini oluştur
$iframe_url = "https://www.paytr.com/odeme/guvenli/" . $result_json['token'];

// Ödeme durumunu güncelle
try {
    $stmt = $conn->prepare("UPDATE payments SET token = ? WHERE id = ?");
    $stmt->execute([$result_json['token'], $payment_id]);
} catch(PDOException $e) {
    // Hata olsa bile devam et
}

// Sayfa başlığı
$page_title = 'Ödeme Yap';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-credit-card me-2"></i>
                            Kredi Kartı ile Ödeme
                        </h5>
                        <span class="badge bg-primary fs-6"><?php echo number_format($payment['amount'], 2, ',', '.'); ?> ₺</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Güvenli Ödeme</h5>
                                <p class="mb-0">Ödeme işleminiz PayTR güvenli ödeme altyapısı üzerinden gerçekleştirilecektir. Kart bilgileriniz kesinlikle sitemizde saklanmamaktadır.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Müşteri</h6>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($user_name); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <i class="fas fa-receipt fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Sipariş No</h6>
                                    <p class="mb-0 text-muted">#<?php echo $payment_id; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="loadingIndicator" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                        <p class="mt-2">Ödeme sayfası yükleniyor, lütfen bekleyin...</p>
                    </div>
                    
                    <div class="payment-iframe-container">
                        <iframe src="<?php echo $iframe_url; ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%; height: 600px;"></iframe>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="deposit.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // iframe yüklendikten sonra yükleniyor göstergesini gizle
    var paytriframe = document.getElementById('paytriframe');
    var loadingIndicator = document.getElementById('loadingIndicator');
    
    if (paytriframe && loadingIndicator) {
        paytriframe.onload = function() {
            loadingIndicator.style.display = 'none';
        };
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 