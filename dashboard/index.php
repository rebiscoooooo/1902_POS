<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$role = $_SESSION['role'] ?? 'STAFF';

if ($role === 'ADMIN') {
    require_once 'admin.php';
} elseif ($role === 'PARTNER') {
    require_once 'partner.php';
} else {
    require_once 'staff.php';
}
?>
