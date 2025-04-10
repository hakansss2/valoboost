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
$booster_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username = isset($_POST['username']) ? clean($_POST['username']) : '';
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$game_ids = isset($_POST['games']) ? $_POST['games'] : [];
$status = isset($_POST['status']) ? clean($_POST['status']) : 'active';
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

if (!empty($password)) {
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }
}

if (empty($game_ids)) {
    $errors[] = "En az bir oyun seçilmelidir.";
}

if (!in_array($status, ['active', 'inactive'])) {
    $errors[] = "Geçersiz durum.";
}

// Booster'ı kontrol et
if (empty($errors)) {
    try {
        $stmt = $conn->prepare("
            SELECT u.id as user_id, u.username, u.email
            FROM users u
            JOIN boosters b ON u.id = b.user_id
            WHERE b.id = ?
        ");
        $stmt->execute([$booster_id]);
        $booster = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booster) {
            $errors[] = "Booster bulunamadı.";
        } else {
            // Kullanıcı adı ve e-posta kontrolü
            $stmt = $conn->prepare("
                SELECT id 
                FROM users 
                WHERE (username = ? OR email = ?) 
                AND id != ?
            ");
            $stmt->execute([$username, $email, $booster['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
            }
        }
    } catch(PDOException $e) {
        $errors[] = "Booster kontrolü yapılırken bir hata oluştu.";
    }
}

if (empty($errors)) {
    try {
        // Transaction başlat
        $conn->beginTransaction();

        // Users tablosunu güncelle
        if (!empty($password)) {
            $stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, password = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $status,
                $booster['user_id']
            ]);
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $username,
                $email,
                $status,
                $booster['user_id']
            ]);
        }

        // Boosters tablosunu güncelle
        $stmt = $conn->prepare("
            UPDATE boosters 
            SET iban = ?, bank_name = ?, account_holder = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $iban,
            $bank_name,
            $account_holder,
            $booster_id
        ]);

        // Oyunları güncelle
        $stmt = $conn->prepare("DELETE FROM booster_games WHERE booster_id = ?");
        $stmt->execute([$booster_id]);

        if (!empty($game_ids)) {
            $stmt = $conn->prepare("INSERT INTO booster_games (booster_id, game_id) VALUES (?, ?)");
            foreach ($game_ids as $game_id) {
                try {
                    $stmt->execute([$booster_id, $game_id]);
                } catch (PDOException $e) {
                    // Eğer oyun zaten atanmışsa hatayı görmezden gel
                    if ($e->getCode() != '23000') {
                        throw $e;
                    }
                }
            }
        }

        // Transaction'ı tamamla
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booster başarıyla güncellendi'
        ]);

    } catch(PDOException $e) {
        // Hata durumunda rollback yap
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Booster güncelleme hatası: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Booster güncellenirken bir hata oluştu: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => implode("\n", $errors)
    ]);
} 