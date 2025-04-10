<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json; charset=utf-8');

// Yönetici kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST verilerini al
$id = $_POST['id'];
$username = $_POST['username'];
$email = $_POST['email'];
$role = $_POST['role'];
$status = $_POST['status'];
$balance = $_POST['balance'];

try {
    // Transaction başlat
    $conn->beginTransaction();

    // Mevcut kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcı bilgilerini güncelle
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
    $stmt->execute([$username, $email, $role, $status, $id]);
    
    // Bakiyeyi güncelle
    $stmt = $conn->prepare("UPDATE balances SET balance = ? WHERE user_id = ?");
    $stmt->execute([$balance, $id]);

    // Eğer kullanıcı booster yapıldıysa ve daha önce booster değilse
    if ($role === 'booster' && $current_user['role'] !== 'booster') {
        // Önce boosters tablosunda bu kullanıcı var mı kontrol et
        $stmt = $conn->prepare("SELECT id FROM boosters WHERE user_id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            // Booster kaydı oluştur
            $stmt = $conn->prepare("
                INSERT INTO boosters (user_id, pending_balance, total_balance, withdrawn_balance, created_at)
                VALUES (?, 0, 0, 0, NOW())
            ");
            $stmt->execute([$id]);
        }
    }
    // Eğer kullanıcı booster rolünden çıkarıldıysa
    elseif ($role !== 'booster' && $current_user['role'] === 'booster') {
        // Aktif siparişi var mı kontrol et
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE booster_id = ? AND status = 'in_progress'
        ");
        $stmt->execute([$id]);
        $active_orders = $stmt->fetchColumn();

        if ($active_orders > 0) {
            throw new Exception('Bu booster\'ın aktif siparişleri var. Önce siparişleri başka bir booster\'a aktarın.');
        }

        // Booster kaydını sil
        $stmt = $conn->prepare("DELETE FROM boosters WHERE user_id = ?");
        $stmt->execute([$id]);
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi']);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log('Kullanıcı güncelleme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 