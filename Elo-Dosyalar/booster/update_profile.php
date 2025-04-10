<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Booster kontrolü
if (!isBooster()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST verilerini al ve temizle
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$iban = isset($_POST['iban']) ? clean($_POST['iban']) : '';
$bank_name = isset($_POST['bank_name']) ? clean($_POST['bank_name']) : '';
$account_holder = isset($_POST['account_holder']) ? clean($_POST['account_holder']) : '';
$game_ids = isset($_POST['games']) ? $_POST['games'] : [];

$errors = [];

// E-posta kontrolü
if (empty($email)) {
    $errors[] = "E-posta adresi gereklidir.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Geçerli bir e-posta adresi girin.";
}

// Şifre kontrolü
if (!empty($password)) {
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }
}

// Oyun kontrolü
if (empty($game_ids)) {
    $errors[] = "En az bir oyun seçilmelidir.";
}

if (empty($errors)) {
    try {
        // Transaction başlat
        $conn->beginTransaction();

        // Booster ID'sini al
        $stmt = $conn->prepare("SELECT b.id as booster_id FROM boosters b WHERE b.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $booster = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booster) {
            throw new Exception('Booster kaydı bulunamadı');
        }

        // E-posta güncelleme
        $stmt = $conn->prepare("
            UPDATE users 
            SET email = ?
            WHERE id = ?
        ");
        $stmt->execute([$email, $_SESSION['user_id']]);

        // Şifre güncelleme
        if (!empty($password)) {
            $stmt = $conn->prepare("
                UPDATE users 
                SET password = ?
                WHERE id = ?
            ");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        }

        // Banka bilgilerini güncelle
        $stmt = $conn->prepare("
            UPDATE boosters 
            SET iban = ?, bank_name = ?, account_holder = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $iban,
            $bank_name,
            $account_holder,
            $booster['booster_id']
        ]);

        // Oyunları güncelle
        $stmt = $conn->prepare("DELETE FROM booster_games WHERE booster_id = ?");
        $stmt->execute([$booster['booster_id']]);

        if (!empty($game_ids)) {
            $stmt = $conn->prepare("INSERT INTO booster_games (booster_id, game_id) VALUES (?, ?)");
            foreach ($game_ids as $game_id) {
                try {
                    $stmt->execute([$booster['booster_id'], $game_id]);
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
            'message' => 'Profil bilgileriniz başarıyla güncellendi'
        ]);

    } catch(PDOException $e) {
        // Hata durumunda rollback yap
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Profil güncelleme hatası: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Profil güncellenirken bir hata oluştu'
        ]);
    } catch(Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => implode("\n", $errors)
    ]);
} 