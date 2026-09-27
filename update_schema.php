<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00");
    echo "Added commission_rate to users.\n";
} catch (Exception $e) {
    echo "commission_rate might exist.\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    echo "Added balance to users.\n";
} catch (Exception $e) {
    echo "balance might exist.\n";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_commissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sale_id INT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('PENDING', 'PAID') NOT NULL DEFAULT 'PENDING',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
    )");
    echo "Created user_commissions.\n";
} catch (Exception $e) {
    echo "Error creating user_commissions: " . $e->getMessage() . "\n";
}
