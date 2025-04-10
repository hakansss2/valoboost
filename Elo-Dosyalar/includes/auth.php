<?php
// Kullanıcı kaydı
function registerUser($username, $email, $password) {
    global $conn;
    
    // Kullanıcı adı ve email kontrolü
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.'];
    }
    
    // Şifreyi hashle
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcıyı ekle
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
    } else {
        return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.'];
    }
}

// Kullanıcı girişi
function loginUser($username, $password) {
    global $conn;
    
    try {
        // Hata ayıklama için
        error_log("Login attempt for email: " . $username);
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
        // Kullanıcı aktif mi?
        if ($user['status'] != 'active') {
            return ['success' => false, 'message' => 'Hesabınız aktif değil.'];
            }
            
            // Oturum bilgilerini kaydet
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
            
            return ['success' => true, 'message' => 'Giriş başarılı!', 'role' => $user['role']];
    } else {
        return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Bir hata oluştu.'];
    }
}
// Kullanıcı çıkışı
function logoutUser() {
    // Oturum bilgilerini temizle
    session_unset();
    session_destroy();
    
    return ['success' => true, 'message' => 'Çıkış yapıldı.'];
}

// Şifre sıfırlama
function resetPassword($email) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return ['success' => false, 'message' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.'];
    }
    
    // Gerçek bir uygulamada, burada şifre sıfırlama e-postası gönderilir
    // Bu örnek için basit bir mesaj döndürüyoruz
    return ['success' => true, 'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.'];
}
?> 