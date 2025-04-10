<?php
require_once 'includes/config.php';

try {
    // Token sütunu ekle
    $conn->exec("ALTER TABLE payments ADD COLUMN token VARCHAR(255) DEFAULT NULL AFTER payment_method");
    
    echo "Token sütunu başarıyla eklendi.";
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
} 