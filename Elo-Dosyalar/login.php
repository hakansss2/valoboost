<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Zaten giriş yapmış kullanıcıyı yönlendir
if (isLoggedIn()) {
    switch($_SESSION['user_role']) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'booster':
            header("Location: booster/index.php");
            break;
        default:
            header("Location: user/index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? clean($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Debug için
    error_log("Giriş denemesi - Kullanıcı: " . $username);
    error_log("Şifre uzunluğu: " . strlen($password));
    
    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre gereklidir.";
    } else {
        try {
            // Kullanıcıyı doğrudan veritabanından kontrol et
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                error_log("Kullanıcı bulundu: " . $user['username']);
                error_log("Rol: " . $user['role']);
                error_log("Durum: " . $user['status']);
                
                if (password_verify($password, $user['password'])) {
                    if ($user['status'] == 'active') {
                        // Oturum bilgilerini kaydet
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        // Debug için
error_log("Kullanıcı rolü: " . $user['role']);
                        // Rol bazlı yönlendirme
switch($user['role']) {
    case 'admin':
        header("Location: admin/index.php");
        break;
    case 'booster':
        header("Location: booster/index.php");
        break;
    default:
        header("Location: user/index.php");
}
                        exit;
                    } else {
                        $error = "Hesabınız aktif değil.";
                    }
                } else {
                    $error = "Kullanıcı adı veya şifre hatalı.";
                }
            } else {
                $error = "Kullanıcı adı veya şifre hatalı.";
            }
        } catch(PDOException $e) {
            error_log("Veritabanı hatası: " . $e->getMessage());
            $error = "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Logo" height="60">
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4">Giriş Yap</h4>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Beni Hatırla</label>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Giriş Yap</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-decoration-none">Şifremi Unuttum</a>
                            <span class="mx-2">|</span>
                            <a href="register.php" class="text-decoration-none">Kayıt Ol</a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>