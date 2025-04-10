<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Zaten giriş yapmış kullanıcıyı yönlendir
if (isLoggedIn()) {
    header("Location: user/index.php");
    exit;
}

$token = isset($_GET['token']) ? clean($_GET['token']) : '';
$errors = [];
$token_valid = false;
$user_id = null;

// Token kontrolü
if (!empty($token)) {
    try {
        // Geçerli token'ı kontrol et
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0 LIMIT 1");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $token_valid = true;
            $user_id = $result['user_id'];
        } else {
            $errors[] = "Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.";
        }
    } catch (PDOException $e) {
        $errors[] = "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
    }
} else {
    $errors[] = "Geçersiz şifre sıfırlama bağlantısı.";
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    // Validasyon
    if (empty($password)) {
        $errors[] = "Yeni şifre girin.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    if (empty($errors)) {
        try {
            // Şifreyi güncelle
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);

            // Token'ı kullanıldı olarak işaretle
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);

            $_SESSION['success_message'] = "Şifreniz başarıyla güncellendi. Yeni şifrenizle giriş yapabilirsiniz.";
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
    .reset-password-page {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/reset-password-bg.jpg');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .reset-password-card {
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }
    </style>
</head>
<body class="reset-password-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Logo" height="60">
                </div>

                <!-- Reset Password Card -->
                <div class="card reset-password-card shadow">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Şifre Sıfırlama</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($token_valid): ?>
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label for="password" class="form-label">Yeni Şifre</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    </div>
                                    <small class="text-muted">En az 6 karakter olmalıdır.</small>
                                </div>

                                <div class="mb-4">
                                    <label for="password_confirm" class="form-label">Yeni Şifre (Tekrar)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                                    </div>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">Şifremi Güncelle</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="mb-0">
                                <a href="login.php">Giriş sayfasına dön</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Back to Home -->
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 