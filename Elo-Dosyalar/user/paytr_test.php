<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/paytr_config.php';

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
    die("Kullanıcı bilgileri getirilirken bir hata oluştu: " . $e->getMessage());
}

// PayTR için gerekli parametreleri hazırla
$merchant_id = PAYTR_MERCHANT_ID;
$merchant_key = PAYTR_MERCHANT_KEY;
$merchant_salt = PAYTR_MERCHANT_SALT;

// Test için sabit değerler
$merchant_oid = 'TEST_' . time(); // Benzersiz sipariş numarası
$email = $user['email'];
$payment_amount = 1000; // 10 TL (kuruş cinsinden)
$user_name = $user['name'] ?: $user['username'];
$user_address = "Test Adresi";
$user_phone = $user['phone'] ?: "5551234567";
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_basket = base64_encode(json_encode([["Test Ürün", "10", 1]]));
$no_installment = 1;
$max_installment = 0;
$currency = "TL";
$test_mode = 1; // Test modu aktif
$non_3d = 0;
$merchant_ok_url = 'https://' . $_SERVER['HTTP_HOST'] . '/user/paytr_test_success.php';
$merchant_fail_url = 'https://' . $_SERVER['HTTP_HOST'] . '/user/paytr_test_fail.php';
$timeout_limit = 30;
$debug_on = 1;
$lang = "tr";
$payment_theme = "light";

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
    echo "<h1>CURL Hatası</h1>";
    echo "<p>Hata: " . $curl_error . "</p>";
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    echo "<h2>CURL Verbose Log</h2>";
    echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
    exit;
}

curl_close($ch);

// Sonucu JSON olarak çözümle
$result_json = json_decode($result, true);

// JSON çözümleme hatası kontrolü
if ($result_json === null) {
    echo "<h1>JSON Çözümleme Hatası</h1>";
    echo "<p>Hata: " . json_last_error_msg() . "</p>";
    echo "<h2>Ham Yanıt</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

// Token alınamadıysa hata göster
if (!isset($result_json['status']) || $result_json['status'] !== 'success') {
    $error_reason = isset($result_json['reason']) ? $result_json['reason'] : 'Bilinmeyen hata';
    echo "<h1>PayTR Hatası</h1>";
    echo "<p>Hata: " . $error_reason . "</p>";
    echo "<h2>PayTR Yanıtı</h2>";
    echo "<pre>" . print_r($result_json, true) . "</pre>";
    exit;
}

// Token alındıysa iframe URL'ini oluştur
$iframe_url = "https://www.paytr.com/odeme/guvenli/" . $result_json['token'];

// Sayfa başlığı
$page_title = 'PayTR Test';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">PayTR Test Sayfası</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Test Bilgileri</h5>
                        <p>Bu sayfa PayTR entegrasyonunu test etmek için oluşturulmuştur.</p>
                        <ul>
                            <li><strong>Sipariş No:</strong> <?php echo $merchant_oid; ?></li>
                            <li><strong>Tutar:</strong> 10,00 TL</li>
                            <li><strong>E-posta:</strong> <?php echo $email; ?></li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h5>PayTR API Yanıtı</h5>
                        <pre class="bg-light p-3"><?php echo print_r($result_json, true); ?></pre>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Ödeme Sayfası</h5>
                        <div class="ratio ratio-16x9">
                            <iframe src="<?php echo $iframe_url; ?>" frameborder="0" scrolling="no" style="width: 100%; height: 100%;"></iframe>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="deposit.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 