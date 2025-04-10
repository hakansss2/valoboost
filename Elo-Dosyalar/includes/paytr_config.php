<?php
// PayTR API Ayarları
define('PAYTR_MERCHANT_ID', '370262'); // PayTR Mağaza No - Buraya kendi mağaza numaranızı girin
define('PAYTR_MERCHANT_KEY', 'Tc4EHTRJTTjfhbZi'); // PayTR Mağaza Anahtarı - Buraya kendi mağaza anahtarınızı girin
define('PAYTR_MERCHANT_SALT', 'Pn45DX9pTngM2soM'); // PayTR Mağaza Gizli Anahtar - Buraya kendi mağaza gizli anahtarınızı girin

// Test modu (1: Aktif, 0: Pasif)
// Canlı ortama geçtiğinizde bu değeri 0 yapın
define('PAYTR_TEST_MODE', 1);

// Ödeme başarılı/başarısız yönlendirme adresleri
define('PAYTR_SUCCESS_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/user/payment_success.php');
define('PAYTR_FAIL_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/user/payment_fail.php');

// Bildirim URL'i (Callback URL)
define('PAYTR_NOTIFICATION_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/user/payment_callback.php');

// Ödeme sayfası dil seçeneği (tr/en)
define('PAYTR_LANG', 'tr');

// Ödeme sayfası teması (light/dark)
define('PAYTR_PAYMENT_THEME', 'light');

/**
 * PayTR için hash oluşturma fonksiyonu
 * 
 * @param array $params Hash için kullanılacak parametreler
 * @return string Oluşturulan hash
 */
function generatePaytrHash($params) {
    $hash_str = PAYTR_MERCHANT_ID . $params['user_ip'] . $params['merchant_oid'] . 
                $params['email'] . $params['payment_amount'] . $params['user_basket'] . 
                $params['no_installment'] . $params['max_installment'] . $params['currency'] . 
                $params['test_mode'] . $params['non_3d'];
                
    $hash = base64_encode(hash_hmac('sha256', $hash_str . PAYTR_MERCHANT_SALT, PAYTR_MERCHANT_KEY, true));
    
    return $hash;
} 