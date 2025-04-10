<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Remember token'ı temizle
if (isset($_COOKIE['remember_token'])) {
    // Veritabanından token'ı sil
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    
    // Cookie'yi sil
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Session'ı temizle
session_unset();
session_destroy();

// Ana sayfaya yönlendir
header("Location: index.php");
exit; 