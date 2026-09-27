<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN cashier_name VARCHAR(100) NULL AFTER cashier_id");
    echo "Added cashier_name to sales.\n";
} catch (Exception $e) {
    echo "cashier_name might exist: " . $e->getMessage() . "\n";
}
