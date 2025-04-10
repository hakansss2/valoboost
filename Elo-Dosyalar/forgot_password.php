<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Zaten giriş yapmış kullanıcıyı yönlendir
if (isLoggedIn()) {
    header("Location: user/index.php");
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? clean($_POST['email']) : '';
    $errors = [];

    // Validasyon
    if (empty($email)) {
        $errors[] = "E-posta adresi girin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    }

    if (empty($errors)) {
        try {
            // Kullanıcıyı kontrol et
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Reset token oluştur
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Eski reset token'ları temizle
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->execute([$user['id']]);

                // Yeni reset token oluştur
                $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);

                // Reset linkini oluştur
                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;

                // E-posta gönder
                $to = $email;
                $subject = "Şifre Sıfırlama - " . getSetting('site_title');
                $message = "Merhaba " . htmlspecialchars($user['username']) . ",\n\n";
                $message .= "Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "Bu bağlantı 1 saat süreyle geçerlidir.\n\n";
                $message .= "Eğer şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı dikkate almayın.\n\n";
                $message .= "Saygılarımızla,\n";
                $message .= getSetting('site_title');

                $headers = "From: " . getSetting('contact_email') . "\r\n";
                $headers .= "Reply-To: " . getSetting('contact_email') . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                if (mail($to, $subject, $message, $headers)) {
                    $_SESSION['success_message'] = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "E-posta gönderilemedi. Lütfen daha sonra tekrar deneyiniz.";
                }
            } else {
                // Güvenlik için kullanıcı bulunamasa bile başarılı mesajı göster
                $_SESSION['success_message'] = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
                header("Location: login.php");
                exit;
            }
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
    <title>Şifremi Unuttum - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
    .forgot-password-page {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/forgot-password-bg.jpg');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .forgot-password-card {
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }
    </style>
</head>
<body class="forgot-password-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Logo" height="60">
                </div>

                <!-- Forgot Password Card -->
                <div class="card forgot-password-card shadow">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Şifremi Unuttum</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <p class="text-muted text-center mb-4">
                            E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.
                        </p>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">Şifremi Sıfırla</button>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">
                                    <a href="login.php">Giriş sayfasına dön</a>
                                </p>
                            </div>
                        </form>
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