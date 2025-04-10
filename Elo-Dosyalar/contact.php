<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? clean($_POST['name']) : '';
    $email = isset($_POST['email']) ? clean($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? clean($_POST['subject']) : '';
    $message = isset($_POST['message']) ? clean($_POST['message']) : '';
    $errors = [];

    // Validasyon
    if (empty($name)) {
        $errors[] = "Adınızı girin.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    }

    if (empty($subject)) {
        $errors[] = "Konu başlığı girin.";
    }

    if (empty($message)) {
        $errors[] = "Mesajınızı girin.";
    }

    if (empty($errors)) {
        try {
            // İletişim mesajını veritabanına kaydet
            $stmt = $conn->prepare("
                INSERT INTO contact_messages (name, email, subject, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $subject, $message]);

            // Başarılı mesajı göster
            $_SESSION['success_message'] = "Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.";
            
            // Form verilerini temizle
            unset($_SESSION['contact_form_data']);
            
            // Sayfayı yeniden yükle
            header("Location: contact.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
        }
    }

    // Hata varsa form verilerini sakla
    if (!empty($errors)) {
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
    .contact-info-card {
        transition: transform 0.3s;
    }
    .contact-info-card:hover {
        transform: translateY(-5px);
    }
    .contact-icon {
        font-size: 2rem;
        color: #0d6efd;
        margin-bottom: 1rem;
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo.png" alt="Logo" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#games">Oyunlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#features">Özellikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#testimonials">Yorumlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">İletişim</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isLoggedIn()): ?>
                        <a href="user/index.php" class="btn btn-outline-light me-2">Panel</a>
                        <a href="logout.php" class="btn btn-danger">Çıkış Yap</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Giriş Yap</a>
                        <a href="register.php" class="btn btn-primary">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-5">İletişim</h1>

                <!-- İletişim Bilgileri -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card h-100 contact-info-card">
                            <div class="card-body text-center">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h5 class="card-title">E-posta</h5>
                                <p class="card-text"><?php echo getSetting('contact_email'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 contact-info-card">
                            <div class="card-body text-center">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h5 class="card-title">Telefon</h5>
                                <p class="card-text"><?php echo getSetting('contact_phone'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 contact-info-card">
                            <div class="card-body text-center">
                                <div class="contact-icon">
                                    <i class="fab fa-discord"></i>
                                </div>
                                <h5 class="card-title">Discord</h5>
                                <p class="card-text"><?php echo getSetting('discord_invite'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İletişim Formu -->
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Bize Ulaşın</h4>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Adınız</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['contact_form_data']['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresiniz</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['contact_form_data']['email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Konu</label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($_SESSION['contact_form_data']['subject'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Mesajınız</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_SESSION['contact_form_data']['message'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Gönder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Hakkımızda</h5>
                    <p><?php echo getSetting('site_description'); ?></p>
                </div>
                <div class="col-md-4">
                    <h5>Hızlı Bağlantılar</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php#games" class="text-white">Oyunlar</a></li>
                        <li><a href="index.php#features" class="text-white">Özellikler</a></li>
                        <li><a href="index.php#testimonials" class="text-white">Yorumlar</a></li>
                        <li><a href="contact.php" class="text-white">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>İletişim</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> <?php echo getSetting('contact_email'); ?></li>
                        <li><i class="fas fa-phone me-2"></i> <?php echo getSetting('contact_phone'); ?></li>
                        <li><i class="fab fa-discord me-2"></i> <?php echo getSetting('discord_invite'); ?></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_title'); ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 