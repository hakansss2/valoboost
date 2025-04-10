<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    die("Bu sayfaya erişim yetkiniz yok.");
}

try {
    $conn->beginTransaction();

    // Önce kolonların var olup olmadığını kontrol et
    $table_info = $conn->query("SHOW COLUMNS FROM orders");
    $columns = [];
    while($column = $table_info->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $column['Field'];
    }

    // booster_earnings kolonunu ekle
    if (!in_array('booster_earnings', $columns)) {
        $conn->exec("ALTER TABLE orders ADD COLUMN booster_earnings DECIMAL(10,2) DEFAULT 0.00");
        echo "booster_earnings kolonu eklendi.<br>";
    }

    // progress kolonunu ekle
    if (!in_array('progress', $columns)) {
        $conn->exec("ALTER TABLE orders ADD COLUMN progress INT DEFAULT 0");
        echo "progress kolonu eklendi.<br>";
    }

    // completed_at kolonunu ekle
    if (!in_array('completed_at', $columns)) {
        $conn->exec("ALTER TABLE orders ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL");
        echo "completed_at kolonu eklendi.<br>";
    }
    
    // Mevcut siparişler için booster_earnings değerlerini güncelle
    $sql = "UPDATE orders 
            SET booster_earnings = price * 0.80
            WHERE booster_earnings = 0 AND status = 'completed'";
    
    $conn->exec($sql);
    echo "Mevcut siparişlerin kazanç değerleri güncellendi!<br>";

    // Booster değerlendirmeleri tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS booster_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booster_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booster_id) REFERENCES users(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )";
    
    $conn->exec($sql);
    echo "booster_ratings tablosu oluşturuldu.<br>";

    // Destek talepleri tablosunu oluştur
    $conn->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Destek mesajları tablosunu oluştur
    $conn->exec("
        CREATE TABLE IF NOT EXISTS support_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $conn->commit();
    echo "Destek sistemi tabloları başarıyla oluşturuldu.";
} catch (PDOException $e) {
    $conn->rollBack();
    die("Veritabanı güncelleme hatası: " . $e->getMessage());
}
?> 