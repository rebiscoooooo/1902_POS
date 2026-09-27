<?php
require_once 'includes/db.php'; 
require_once 'includes/auth.php'; 
if (isLoggedIn()) {
    header("Location: dashboard/");
    exit;
}
$error = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login) || empty($password)) {
        $error = "Username/Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'ACTIVE'");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);
            require_once 'includes/functions.php';
            logActivity($pdo, $user['id'], 'LOGIN', 'User logged in successfully');
            header("Location: dashboard/");
            exit;
        } else {
            $error = "Invalid credentials or account inactive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - 1902 POS</title>
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
    <div class="max-w-4xl w-full glass-panel rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row">
        <div class="hidden md:flex md:w-1/2 bg-black p-8 flex-col justify-center items-center">
            <img src="assets/img/logo.jpg" alt="1902 Logo" class="w-full h-auto max-w-[90%] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500">
        </div>
        <div class="w-full md:w-1/2 p-8 md:p-12 bg-white/30 backdrop-blur-md relative">
            <a href="index.php" class="absolute top-6 right-6 flex items-center text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Home
            </a>
            <div class="text-center md:text-left mb-8 mt-2">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Welcome back</h2>
                <p class="text-gray-600 font-medium">Log in to your account</p>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-50/80 text-red-600 p-4 rounded-lg mb-6 text-sm flex items-center border border-red-100">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-5">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="login">Username or Email</label>
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all shadow-inner" id="login" name="login" type="text" placeholder="e.g. admin or admin@example.com" required>
                </div>
                <div class="mb-6 relative">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input class="w-full px-4 py-3 rounded-xl bg-white/60 border border-white focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all shadow-inner pr-10" id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
                <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none transition-colors shadow-md" type="submit">LOG IN</button>
            </form>
            <div class="mt-8 text-center text-sm">
                <p class="text-gray-600">Don't have an account? <a href="signup.php" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Create one</a></p>
            </div>
        </div>
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