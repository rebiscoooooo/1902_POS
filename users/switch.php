<?php
// users/switch.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_id'])) {
    $target_id = $_POST['target_id'];
    
    // Prevent switching if already switched to avoid nested switching
    if (isSwitchedUser()) {
        die("Already viewing as another user.");
    }
    
    // Don't switch to self
    if ($target_id == $_SESSION['user_id']) {
        header("Location: index.php");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'ACTIVE'");
    $stmt->execute([$target_id]);
    $target_user = $stmt->fetch();
    
    if ($target_user) {
        // Log the switch
        logActivity($pdo, $_SESSION['user_id'], 'USER_SWITCHED', "Admin switched to view as user ID: $target_id (" . $target_user['username'] . ")");
        
        // Save original admin state
        $_SESSION['original_admin_id'] = $_SESSION['user_id'];
        
        // Impersonate
        $_SESSION['user_id'] = $target_user['id'];
        $_SESSION['username'] = $target_user['username'];
        $_SESSION['name'] = $target_user['name'];
        $_SESSION['role'] = $target_user['role'];
        
        header("Location: /1902_pos/dashboard/");
        exit;
    }
}

header("Location: index.php");
exit;
?>
