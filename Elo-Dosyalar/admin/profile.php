<?php
require_once 'includes/header.php';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? clean($_POST['username']) : '';
    $email = isset($_POST['email']) ? clean($_POST['email']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    $errors = [];

    // Mevcut kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validasyon
    if (empty($username)) {
        $errors[] = "Kullanıcı adı gereklidir.";
    } elseif ($username !== $user['username']) {
        // Kullanıcı adı değiştirilmek isteniyorsa benzersiz olmalı
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu kullanıcı adı zaten kullanılıyor.";
        }
    }

    if (empty($email)) {
        $errors[] = "E-posta adresi gereklidir.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    } elseif ($email !== $user['email']) {
        // E-posta değiştirilmek isteniyorsa benzersiz olmalı
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
        }
    }

    // Şifre değiştirilmek isteniyorsa
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Mevcut şifrenizi girin.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Mevcut şifre hatalı.";
        }

        if (strlen($new_password) < 6) {
            $errors[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "Şifreler eşleşmiyor.";
        }
    }

    if (empty($errors)) {
        try {
            // Temel bilgileri güncelle
            $sql = "UPDATE users SET username = ?, email = ?";
            $params = [$username, $email];

            // Şifre değiştirilecekse ekle
            if (!empty($new_password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $_SESSION['user_id'];

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Session'daki kullanıcı adını güncelle
            $_SESSION['username'] = $username;

            $_SESSION['success'] = "Profiliniz başarıyla güncellendi.";
            header("Location: profile.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Profil güncellenirken bir hata oluştu.";
        }
    }
}

// Kullanıcı bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- Başlık -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Profil Ayarları</h1>
</div>

<!-- Hata Mesajları -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Başarı Mesajı -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Profil Formu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="" id="profileForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-4">Şifre Değiştir</h5>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               minlength="6">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="6">
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form değişikliklerini izle
    watchFormChanges('profileForm');
});
</script>

<?php require_once 'includes/footer.php'; ?> 