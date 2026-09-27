<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['ADMIN', 'PARTNER']);

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    if ($_SESSION['role'] !== 'ADMIN') jsonResponse(false, 'Unauthorized');
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'STAFF';
    $status = $_POST['status'] ?? 'ACTIVE';
    $password = $_POST['password'] ?? '';
    $commission_rate = (float)($_POST['commission_rate'] ?? 0);
    
    if (empty($name) || empty($username) || empty($email)) {
        jsonResponse(false, "Name, Username, and Email are required.");
    }
    
    try {
        if ($id) {
            // Check if username/email already exists for OTHER users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $id]);
            if ($stmt->fetch()) {
                jsonResponse(false, "Username or Email already taken by another user.");
            }
            
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ?, status = ?, password = ?, commission_rate = ? WHERE id = ?");
                $stmt->execute([$name, $username, $email, $role, $status, $hashed, $commission_rate, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ?, status = ?, commission_rate = ? WHERE id = ?");
                $stmt->execute([$name, $username, $email, $role, $status, $commission_rate, $id]);
            }
            
            logActivity($pdo, $_SESSION['user_id'], 'UPDATE_USER', "Updated user $username (ID: $id)");
            jsonResponse(true, "User updated successfully.");
        } else {
            // Check if username/email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                jsonResponse(false, "Username or Email already exists.");
            }
            
            if (empty($password)) {
                jsonResponse(false, "Password is required for new users.");
            }
            
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, status, commission_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $email, $hashed, $role, $status, $commission_rate]);
            
            $new_id = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'ADD_USER', "Created user $username (ID: $new_id)");
            jsonResponse(true, "User created successfully.");
        }
    } catch (Exception $e) {
        jsonResponse(false, "Database error: " . $e->getMessage());
    }
}

if ($action === 'delete') {
    if ($_SESSION['role'] !== 'ADMIN') jsonResponse(false, 'Unauthorized');
    $id = $_POST['id'] ?? '';
    
    if (empty($id) || $id == $_SESSION['user_id']) {
        jsonResponse(false, "Invalid user or cannot delete yourself.");
    }
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(false, "User not found.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity($pdo, $_SESSION['user_id'], 'DELETE_USER', "Deleted user {$user['username']} (ID: $id)");
        jsonResponse(true, "User deleted successfully.");
    } catch (Exception $e) {
        jsonResponse(false, "Error deleting user: " . $e->getMessage());
    }
}

if ($action === 'withdraw') {
    $id = $_POST['id'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    
    if (empty($id) || $amount <= 0) {
        jsonResponse(false, "Invalid amount.");
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT username, name, balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found.");
        }
        
        if ($user['balance'] < $amount) {
            throw new Exception("Insufficient balance.");
        }
        
        // Deduct from user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $id]);
        
        // Log in capital transactions
        $stmt = $pdo->prepare("INSERT INTO capital_transactions (type, amount, purpose, description, recorded_by, transaction_date) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute(['CASH_OUT', $amount, "Commission Withdrawal", "Withdrawal by {$user['name']}", $_SESSION['user_id']]);
        
        logActivity($pdo, $_SESSION['user_id'], 'WITHDRAWAL', "Processed withdrawal of ₱$amount for {$user['username']}");
        
        $pdo->commit();
        jsonResponse(true, "Withdrawal successful.");
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, "Error: " . $e->getMessage());
    }
}

jsonResponse(false, "Invalid action.");
?>
