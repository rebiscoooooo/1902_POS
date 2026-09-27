<?php
// settings/index.php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($name && $email) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($current, $hash)) {
            if ($new === $confirm && strlen($new) >= 6) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
                $success = "Password updated successfully.";
            } else {
                $error = "New passwords do not match or are too short (min 6 chars).";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }

    if (isset($_POST['update_settings']) && $role === 'ADMIN') {
        $updates = [
            'store_name' => $_POST['store_name'] ?? '',
            'store_address' => $_POST['store_address'] ?? '',
            'store_contact' => $_POST['store_contact'] ?? '',
            'receipt_footer' => $_POST['receipt_footer'] ?? ''
        ];
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($updates as $key => $val) {
            $stmt->execute([$val, $key]);
        }
        $success = "Global settings updated.";
    }
}

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch settings if admin
$settings = [];
if ($role === 'ADMIN') {
    $stmt = $pdo->query("SELECT * FROM settings");
    foreach ($stmt->fetchAll() as $s) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
}
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="mb-6 shrink-0">
        <h2 class="text-2xl font-bold text-gray-900">Settings</h2>
        <p class="text-gray-500 text-sm">Manage your account and preferences.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 shrink-0 flex items-center border border-green-100">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 shrink-0 flex items-center border border-red-100">
            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="flex-1 overflow-y-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="space-y-8">
                <!-- Profile Settings -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Profile Information</h3>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Immutable)</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm" disabled>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg hover:bg-indigo-700 transition text-sm">Save Profile</button>
                    </form>
                </div>

                <!-- Password Settings -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Change Password</h3>
                    <form method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg hover:bg-indigo-700 transition text-sm">Update Password</button>
                    </form>
                </div>
            </div>

            <?php if ($role === 'ADMIN'): ?>
            <!-- Global Store Settings -->
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Global Store Settings</h3>
                    <form method="POST">
                        <input type="hidden" name="update_settings" value="1">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                            <input type="text" name="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store Address</label>
                            <textarea name="store_address" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store Contact</label>
                            <input type="text" name="store_contact" value="<?= htmlspecialchars($settings['store_contact'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Footer Message</label>
                            <input type="text" name="receipt_footer" value="<?= htmlspecialchars($settings['receipt_footer'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center justify-center w-full">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Global Settings
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
