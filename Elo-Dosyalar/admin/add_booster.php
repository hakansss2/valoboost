<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Yönetici kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST verilerini al ve temizle
$username = isset($_POST['username']) ? clean($_POST['username']) : '';
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$game_ids = isset($_POST['games']) ? $_POST['games'] : [];
$iban = isset($_POST['iban']) ? clean($_POST['iban']) : '';
$bank_name = isset($_POST['bank_name']) ? clean($_POST['bank_name']) : '';
$account_holder = isset($_POST['account_holder']) ? clean($_POST['account_holder']) : '';

$errors = [];

// Validasyon
if (empty($username)) {
    $errors[] = "Kullanıcı adı gereklidir.";
} elseif (strlen($username) < 3) {
    $errors[] = "Kullanıcı adı en az 3 karakter olmalıdır.";
}

if (empty($email)) {
    $errors[] = "E-posta adresi gereklidir.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Geçerli bir e-posta adresi girin.";
}

if (empty($password)) {
    $errors[] = "Şifre gereklidir.";
} elseif (strlen($password) < 6) {
    $errors[] = "Şifre en az 6 karakter olmalıdır.";
} elseif ($password !== $password_confirm) {
    $errors[] = "Şifreler eşleşmiyor.";
}

if (empty($game_ids)) {
    $errors[] = "En az bir oyun seçilmelidir.";
}

// Kullanıcı adı ve e-posta kontrolü
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
    }
} catch(PDOException $e) {
    error_log("Kullanıcı kontrolü hatası: " . $e->getMessage());
    $errors[] = "Kullanıcı kontrolü yapılırken bir hata oluştu.";
}

if (empty($errors)) {
    try {
        // Transaction başlat
        $conn->beginTransaction();

        // Kullanıcıyı ekle
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, 'booster', 'active', NOW())
        ");
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT)
        ]);
        $user_id = $conn->lastInsertId();

        // Booster kaydını oluştur
        $stmt = $conn->prepare("
            INSERT INTO boosters (user_id, iban, bank_name, account_holder, total_balance, pending_balance, withdrawn_balance, created_at) 
            VALUES (?, ?, ?, ?, 0, 0, 0, NOW())
        ");
        $stmt->execute([
            $user_id,
            $iban,
            $bank_name,
            $account_holder
        ]);
        $booster_id = $conn->lastInsertId();

        // Oyunları ekle
        $stmt = $conn->prepare("INSERT INTO booster_games (booster_id, game_id, created_at) VALUES (?, ?, NOW())");
        foreach ($game_ids as $game_id) {
            try {
                $stmt->execute([$booster_id, $game_id]);
            } catch(PDOException $e) {
                // Eğer oyun zaten ekliyse hata verme
                if ($e->getCode() != '23000') {
                    throw $e;
                }
            }
        }

        // Transaction'ı tamamla
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booster başarıyla eklendi'
        ]);

    } catch(PDOException $e) {
        // Hata durumunda rollback yap
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Booster ekleme hatası: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Booster eklenirken bir hata oluştu'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => implode("\n", $errors)
    ]);
} 