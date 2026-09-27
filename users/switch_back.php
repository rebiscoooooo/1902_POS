<?php
// users/switch_back.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isSwitchedUser()) {
    $original_id = getOriginalAdminId();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$original_id]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Log the return
        logActivity($pdo, $admin['id'], 'USER_SWITCHED_BACK', "Admin returned from viewing user ID: " . $_SESSION['user_id']);
        
        // Restore
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['name'] = $admin['name'];
        $_SESSION['role'] = $admin['role'];
        
        unset($_SESSION['original_admin_id']);
        
        header("Location: /1902_pos/users/");
        exit;
    }
}

header("Location: /1902_pos/dashboard/");
exit;
?>
