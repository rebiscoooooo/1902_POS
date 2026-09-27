<?php 
require_once 'includes/db.php'; 
require_once 'includes/auth.php'; 
if (isLoggedIn()) {
    header("Location: dashboard/");
    exit;
}
$error = ''; $success = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, 'STAFF')");
            if ($stmt->execute([$name, $username, $email, $hashed])) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Failed to create account.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - 1902 POS</title>
    <link rel="icon" type="image/jpeg" href="/1902_pos/assets/img/logo.jpg">
    <link rel="manifest" href="/1902_pos/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="/1902_pos/assets/img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
    <style>
        body { font-family: 'Inter', sans-serif; background: #eef2ff; overflow-x: hidden; }
        /* Animated Background Blobs */
        .bg-shapes { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; overflow: hidden; pointer-events: none; }
        .shape { position: absolute; filter: blur(80px); opacity: 0.7; animation: float 20s infinite alternate ease-in-out; }
        .shape-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: #c7d2fe; animation-delay: 0s; }
        .shape-2 { top: 40%; right: -10%; width: 40vw; height: 40vw; background: #e0e7ff; animation-delay: -5s; }
        .shape-3 { bottom: -20%; left: 20%; width: 60vw; height: 60vw; background: #ddd6fe; animation-delay: -10s; }
        @keyframes float { 0% { transform: translate(0, 0) rotate(0deg); } 100% { transform: translate(100px, 50px) rotate(20deg); } }

        .glass-panel { background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="max-w-md w-full glass-panel rounded-2xl shadow-xl overflow-hidden p-8 relative">
        <a href="index.php" class="absolute top-6 right-6 flex items-center text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Home
        </a>
        <div class="text-center mb-6 mt-2">
            <h2 class="text-2xl font-bold text-gray-800">Create an Account</h2>
            <p class="text-gray-500 text-sm mt-1">Join 1902 POS as a Cashier</p>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-50/80 text-red-600 p-3 rounded-lg mb-4 text-sm flex items-center border border-red-100"><i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50/80 text-green-600 p-3 rounded-lg mb-4 text-sm flex items-center border border-green-100"><i data-lucide="check-circle-2" class="w-4 h-4 mr-2"></i><?= htmlspecialchars($success) ?></div>
            <div class="mt-4 text-center"><a href="login.php" class="inline-block bg-indigo-600 text-white font-medium py-2 px-6 rounded-lg hover:bg-indigo-700 transition">Go to Login</a></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="space-y-4">
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none text-sm transition-all shadow-inner" name="name" type="text" placeholder="Full Name" required>
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none text-sm transition-all shadow-inner" name="username" type="text" placeholder="Username" required>
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none text-sm transition-all shadow-inner" name="email" type="email" placeholder="Email" required>
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none text-sm transition-all shadow-inner" name="password" type="password" placeholder="Password" required>
                </div>
                <button class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-md" type="submit">Create Account</button>
            </form>
            <div class="mt-6 text-center text-xs">
                <p class="text-gray-600">Already have an account? <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Log in</a></p>
            </div>
        <?php endif; ?>
    </div>
    <script>lucide.createIcons();</script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/1902_pos/sw.js');
            });
        }
    </script>
</body>
</html>