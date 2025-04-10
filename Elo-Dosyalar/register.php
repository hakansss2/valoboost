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
    $username = isset($_POST['username']) ? clean($_POST['username']) : '';
    $email = isset($_POST['email']) ? clean($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $errors = [];

    // Validasyon
    if (empty($username)) {
        $errors[] = "Kullanıcı adı girin.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Kullanıcı adı 3-20 karakter arasında olmalıdır.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
    }

    if (empty($email)) {
        $errors[] = "E-posta adresi girin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    }

    if (empty($password)) {
        $errors[] = "Şifre girin.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    if (empty($errors)) {
        try {
            // Kullanıcı adı ve e-posta kontrolü
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $errors[] = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
            } else {
                // Kullanıcıyı kaydet
$stmt = $conn->prepare("
    INSERT INTO users (username, email, password, role, status, created_at)
    VALUES (?, ?, ?, 'user', 'active', NOW())
");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);

                // Başarılı mesajı göster ve giriş sayfasına yönlendir
                $_SESSION['success_message'] = "Kaydınız başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.";
                header("Location: login.php");
                exit;
            }
        } // try-catch bloğunun catch kısmında:
catch(PDOException $e) {
    // Hata mesajını göster
    echo "Hata: " . $e->getMessage();
    die();
}
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
    .register-page {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/register-bg.jpg');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .register-card {
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }
    .password-strength {
        height: 5px;
        transition: all 0.3s ease;
    }
    </style>
</head>
<body class="register-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Logo" height="60">
                </div>

                <!-- Register Card -->
                <div class="card register-card shadow">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Kayıt Ol</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                </div>
                                <small class="form-text text-muted">3-20 karakter, sadece harf, rakam ve alt çizgi.</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2"></div>
                                <small class="form-text text-muted">En az 6 karakter.</small>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">Şifre Tekrar</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">Kayıt Ol</button>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">
                                    Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a>
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

    <!-- Custom JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Şifre göster/gizle
        function togglePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            button.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('password_confirm', 'togglePasswordConfirm');

        // Şifre gücü kontrolü
        const password = document.getElementById('password');
        const strengthBar = document.querySelector('.password-strength');
        
        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            let color = '';

            if (value.length >= 6) strength += 1;
            if (value.match(/[a-z]+/)) strength += 1;
            if (value.match(/[A-Z]+/)) strength += 1;
            if (value.match(/[0-9]+/)) strength += 1;
            if (value.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength += 1;

            switch (strength) {
                case 0:
                    color = '#dc3545'; // Çok zayıf
                    break;
                case 1:
                    color = '#ffc107'; // Zayıf
                    break;
                case 2:
                    color = '#fd7e14'; // Orta
                    break;
                case 3:
                    color = '#20c997'; // İyi
                    break;
                case 4:
                case 5:
                    color = '#198754'; // Güçlü
                    break;
            }

            strengthBar.style.width = (strength * 20) + '%';
            strengthBar.style.backgroundColor = color;
        });

        // Form doğrulama
        const form = document.getElementById('registerForm');
        const passwordConfirm = document.getElementById('password_confirm');

        form.addEventListener('submit', function(e) {
            if (password.value !== passwordConfirm.value) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
            }
        });
    });
    </script>
</body>
</html> 