<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat ve çıktı tamponlamasını başlat
ob_start();

// Gerekli dosyaları dahil et
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Admin kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Sayfa başlığı
$page_title = "Site Ayarları";

// Mevcut ayarları getir
try {
    $stmt = $conn->query("SELECT * FROM settings");
    $current_settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    $error = 'Ayarlar yüklenirken bir hata oluştu: ' . $e->getMessage();
    $current_settings = [];
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Genel ayarları güncelle
        $settings = [
            // Genel Ayarlar
            'site_title' => isset($_POST['site_title']) ? clean($_POST['site_title']) : '',
            'site_description' => isset($_POST['site_description']) ? clean($_POST['site_description']) : '',
            'site_email' => isset($_POST['site_email']) ? clean($_POST['site_email']) : '',
            'site_phone' => isset($_POST['site_phone']) ? clean($_POST['site_phone']) : '',
            'site_address' => isset($_POST['site_address']) ? clean($_POST['site_address']) : '',
            'site_discord' => isset($_POST['site_discord']) ? clean($_POST['site_discord']) : '',
            
            // Sosyal Medya
            'facebook_url' => isset($_POST['facebook_url']) ? clean($_POST['facebook_url']) : '',
            'twitter_url' => isset($_POST['twitter_url']) ? clean($_POST['twitter_url']) : '',
            'instagram_url' => isset($_POST['instagram_url']) ? clean($_POST['instagram_url']) : '',
            'discord_invite' => isset($_POST['discord_invite']) ? clean($_POST['discord_invite']) : '',
            
            // Ödeme Ayarları
            'commission_rate' => isset($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 20,
            'min_withdrawal' => isset($_POST['min_withdrawal']) ? (float)$_POST['min_withdrawal'] : 50,
            'currency_symbol' => isset($_POST['currency_symbol']) ? clean($_POST['currency_symbol']) : '₺',
            
            // Sistem Ayarları
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'registration_enabled' => isset($_POST['registration_enabled']) ? 1 : 0,
            'auto_approve_boosters' => isset($_POST['auto_approve_boosters']) ? 1 : 0,
            'require_id_verification' => isset($_POST['require_id_verification']) ? 1 : 0,
            
            // Bildirim Ayarları
            'smtp_host' => isset($_POST['smtp_host']) ? clean($_POST['smtp_host']) : '',
            'smtp_port' => isset($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587,
            'smtp_user' => isset($_POST['smtp_user']) ? clean($_POST['smtp_user']) : '',
            'smtp_pass' => isset($_POST['smtp_pass']) ? clean($_POST['smtp_pass']) : '',
            'discord_webhook' => isset($_POST['discord_webhook']) ? clean($_POST['discord_webhook']) : '',
            'discord_new_order' => isset($_POST['discord_new_order']) ? 1 : 0,
            'discord_completed_order' => isset($_POST['discord_completed_order']) ? 1 : 0
        ];
        
        // Dosya yüklemeleri
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $logo_path = uploadFile($_FILES['site_logo'], '../uploads/');
            if ($logo_path) {
                $settings['site_logo'] = $logo_path;
            }
        }
        
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $favicon_path = uploadFile($_FILES['site_favicon'], '../uploads/');
            if ($favicon_path) {
                $settings['site_favicon'] = $favicon_path;
            }
        }
        
        // Ayarları veritabanına kaydet
        $stmt = $conn->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }
        
        $_SESSION['success'] = 'Ayarlar başarıyla güncellendi.';
        
        // Aktif sekmeyi al ve yönlendir
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        header("Location: settings.php?tab=" . $active_tab);
        exit;
        
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Aktif sekmeyi belirle
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Header'ı dahil et
require_once 'includes/header.php';

// Veritabanı bağlantısını kontrol et
if (!isset($conn) || !$conn) {
    die("Veritabanı bağlantısı başarısız!");
}

// Gerekli tabloların varlığını kontrol et
try {
    // settings tablosunu kontrol et
    $stmt = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        // settings tablosunu oluştur
        $conn->exec("CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(255) PRIMARY KEY,
            `value` TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
    
    // payment_methods tablosunu kontrol et
    $stmt = $conn->query("SHOW TABLES LIKE 'payment_methods'");
    if ($stmt->rowCount() == 0) {
        // payment_methods tablosunu oluştur
        $conn->exec("CREATE TABLE IF NOT EXISTS payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            icon VARCHAR(50),
            min_amount DECIMAL(10,2) DEFAULT 0,
            max_amount DECIMAL(10,2) DEFAULT 0,
            fee_percentage DECIMAL(5,2) DEFAULT 0,
            fee_fixed DECIMAL(10,2) DEFAULT 0,
            settings TEXT,
            status ENUM('active', 'inactive') DEFAULT 'inactive',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Varsayılan ödeme yöntemlerini ekle
        $conn->exec("INSERT INTO payment_methods (name, icon, min_amount, max_amount, status) VALUES 
            ('Banka Havalesi', 'fas fa-university', 50, 10000, 'active'),
            ('Kredi Kartı', 'fas fa-credit-card', 50, 10000, 'active'),
            ('PayTR', 'fas fa-credit-card', 50, 10000, 'inactive'),
            ('Papara', 'fas fa-wallet', 50, 10000, 'inactive')
        ");
    }
    
    // bank_accounts tablosunu kontrol et
    $stmt = $conn->query("SHOW TABLES LIKE 'bank_accounts'");
    if ($stmt->rowCount() == 0) {
        // bank_accounts tablosunu oluştur
        $conn->exec("CREATE TABLE IF NOT EXISTS bank_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bank_name VARCHAR(255) NOT NULL,
            account_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(50),
            iban VARCHAR(50) NOT NULL,
            branch_code VARCHAR(20),
            branch_name VARCHAR(255),
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
    
    // games tablosunu kontrol et
    $stmt = $conn->query("SHOW TABLES LIKE 'games'");
    if ($stmt->rowCount() == 0) {
        // games tablosunu oluştur
        $conn->exec("CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Varsayılan oyunları ekle
        $conn->exec("INSERT INTO games (name, slug, status) VALUES 
            ('Valorant', 'valorant', 'active'),
            ('League of Legends', 'lol', 'active')
        ");
    }
    
} catch(PDOException $e) {
    die("Veritabanı tabloları oluşturulurken hata: " . $e->getMessage());
}

// Banka hesabı durumunu değiştirme
if (isset($_POST['toggle_bank_status'])) {
    $account_id = (int)$_POST['account_id'];
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    
    try {
        $stmt = $conn->prepare("UPDATE bank_accounts SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $account_id]);
        
        $_SESSION['success'] = 'Banka hesabı durumu güncellendi.';
        header('Location: settings.php?tab=bank_accounts');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Hata: ' . $e->getMessage();
    }
}

// Banka hesabı silme
if (isset($_GET['delete_bank']) && is_numeric($_GET['delete_bank'])) {
    $account_id = (int)$_GET['delete_bank'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        
        $_SESSION['success'] = 'Banka hesabı başarıyla silindi.';
        header('Location: settings.php?tab=bank_accounts');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Hata: ' . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cogs fa-sm text-primary-300"></i> Site Ayarları
        </h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Ayarlar Kartı -->
    <div class="card shadow mb-4">
        <!-- Sekmeler -->
        <div class="card-header py-3">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'general' ? 'active' : ''; ?>" href="?tab=general">
                        <i class="fas fa-globe"></i> Genel Ayarlar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'boost' ? 'active' : ''; ?>" href="?tab=boost">
                        <i class="fas fa-rocket"></i> Boost Ayarları
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'payment' ? 'active' : ''; ?>" href="?tab=payment">
                        <i class="fas fa-credit-card"></i> Ödeme Ayarları
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'notification' ? 'active' : ''; ?>" href="?tab=notification">
                        <i class="fas fa-bell"></i> Bildirim Ayarları
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'social' ? 'active' : ''; ?>" href="?tab=social">
                        <i class="fas fa-share-alt"></i> Sosyal Medya
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sekme İçerikleri -->
        <div class="card-body">
            <form method="post" action="" enctype="multipart/form-data">
                <?php if ($active_tab === 'general'): ?>
                    <!-- Genel Ayarlar -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Site Başlığı</label>
                                <input type="text" class="form-control" name="site_title" 
                                       value="<?php echo htmlspecialchars($current_settings['site_title'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Site Açıklaması</label>
                                <textarea class="form-control" name="site_description" rows="3"><?php 
                                    echo htmlspecialchars($current_settings['site_description'] ?? ''); 
                                ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" name="site_email" 
                                       value="<?php echo htmlspecialchars($current_settings['site_email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" class="form-control" name="site_phone" 
                                       value="<?php echo htmlspecialchars($current_settings['site_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Site Logo</label>
                                <input type="file" class="form-control" name="site_logo">
                                <?php if (!empty($current_settings['site_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($current_settings['site_logo']); ?>" 
                                         class="mt-2" style="max-height: 50px;">
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Favicon</label>
                                <input type="file" class="form-control" name="site_favicon">
                                <?php if (!empty($current_settings['site_favicon'])): ?>
                                    <img src="<?php echo htmlspecialchars($current_settings['site_favicon']); ?>" 
                                         class="mt-2" style="max-height: 32px;">
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Adres</label>
                                <textarea class="form-control" name="site_address" rows="2"><?php 
                                    echo htmlspecialchars($current_settings['site_address'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                <?php elseif ($active_tab === 'boost'): ?>
                    <!-- Boost Ayarları -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Varsayılan Komisyon Oranı (%)</label>
                                <input type="number" class="form-control" name="default_commission" 
                                       value="<?php echo htmlspecialchars($current_settings['default_commission'] ?? '20'); ?>" 
                                       min="0" max="100" step="0.1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum Çekim Tutarı (₺)</label>
                                <input type="number" class="form-control" name="min_withdrawal" 
                                       value="<?php echo htmlspecialchars($current_settings['min_withdrawal'] ?? '50'); ?>" 
                                       min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Booster Onay Sistemi</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_approve_boosters" 
                                           <?php echo ($current_settings['auto_approve_boosters'] ?? '') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Otomatik Booster Onayı</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kimlik Doğrulama</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="require_id_verification" 
                                           <?php echo ($current_settings['require_id_verification'] ?? '') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Boosterlar için kimlik doğrulama zorunlu</label>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($active_tab === 'payment'): ?>
                    <!-- Ödeme Ayarları -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">PayTR Ayarları</h5>
                            <div class="mb-3">
                                <label class="form-label">Merchant ID</label>
                                <input type="text" class="form-control" name="paytr_merchant_id" 
                                       value="<?php echo htmlspecialchars($current_settings['paytr_merchant_id'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Merchant Key</label>
                                <input type="text" class="form-control" name="paytr_merchant_key" 
                                       value="<?php echo htmlspecialchars($current_settings['paytr_merchant_key'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Merchant Salt</label>
                                <input type="text" class="form-control" name="paytr_merchant_salt" 
                                       value="<?php echo htmlspecialchars($current_settings['paytr_merchant_salt'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Papara Ayarları</h5>
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <input type="text" class="form-control" name="papara_api_key" 
                                       value="<?php echo htmlspecialchars($current_settings['papara_api_key'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hesap Numarası</label>
                                <input type="text" class="form-control" name="papara_account" 
                                       value="<?php echo htmlspecialchars($current_settings['papara_account'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                <?php elseif ($active_tab === 'notification'): ?>
                    <!-- Bildirim Ayarları -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">E-posta Bildirimleri</h5>
                            <div class="mb-3">
                                <label class="form-label">SMTP Sunucu</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" 
                                       value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? '587'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Kullanıcı</label>
                                <input type="text" class="form-control" name="smtp_user" 
                                       value="<?php echo htmlspecialchars($current_settings['smtp_user'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Şifre</label>
                                <input type="password" class="form-control" name="smtp_pass" 
                                       value="<?php echo htmlspecialchars($current_settings['smtp_pass'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Discord Bildirimleri</h5>
                            <div class="mb-3">
                                <label class="form-label">Webhook URL</label>
                                <input type="text" class="form-control" name="discord_webhook" 
                                       value="<?php echo htmlspecialchars($current_settings['discord_webhook'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="discord_new_order" 
                                           <?php echo ($current_settings['discord_new_order'] ?? '') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Yeni Sipariş Bildirimi</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="discord_completed_order" 
                                           <?php echo ($current_settings['discord_completed_order'] ?? '') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Tamamlanan Sipariş Bildirimi</label>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($active_tab === 'social'): ?>
                    <!-- Sosyal Medya Ayarları -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Discord Sunucu Linki</label>
                                <input type="url" class="form-control" name="discord_invite" 
                                       value="<?php echo htmlspecialchars($current_settings['discord_invite'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instagram</label>
                                <input type="url" class="form-control" name="instagram_url" 
                                       value="<?php echo htmlspecialchars($current_settings['instagram_url'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Twitter</label>
                                <input type="url" class="form-control" name="twitter_url" 
                                       value="<?php echo htmlspecialchars($current_settings['twitter_url'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">YouTube</label>
                                <input type="url" class="form-control" name="youtube_url" 
                                       value="<?php echo htmlspecialchars($current_settings['youtube_url'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Kaydet Butonu -->
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 