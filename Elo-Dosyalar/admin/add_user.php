<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON yanıt için header
header('Content-Type: application/json; charset=utf-8');

// Yönetici kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek']));
}

// Debug için POST verilerini logla
error_log('POST verileri: ' . print_r($_POST, true));

// Verileri al ve temizle
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');

// Basit validasyon
if (empty($username) || empty($email) || empty($password) || empty($role)) {
    die(json_encode(['success' => false, 'message' => 'Tüm alanları doldurun']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi']));
}

if (strlen($password) < 6) {
    die(json_encode(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır']));
}

if (!in_array($role, ['user', 'booster', 'admin'])) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı rolü']));
}

try {
    // Kullanıcı adı veya e-posta kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        die(json_encode(['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor']));
    }
    
    $conn->beginTransaction();

    // Kullanıcıyı ekle
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([$username, $email, $hashed_password, $role]);
    $user_id = $conn->lastInsertId();
    
    // Bakiye kaydı oluştur
    $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, 0)");
    $stmt->execute([$user_id]);

    // Eğer kullanıcı booster olarak eklendiyse
    if ($role === 'booster') {
        // Booster kaydı oluştur
        $stmt = $conn->prepare("
            INSERT INTO boosters (user_id, pending_balance, total_balance, withdrawn_balance, created_at)
            VALUES (?, 0, 0, 0, NOW())
        ");
        $stmt->execute([$user_id]);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla eklendi']);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Kullanıcı ekleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu, lütfen tekrar deneyin']);
} 