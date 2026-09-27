<?php
session_start(); 
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1902 POS - Crawler Toy Store</title>
    <link rel="icon" type="image/jpeg" href="/1902_pos/assets/img/logo.jpg">
    <link rel="manifest" href="/1902_pos/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="/1902_pos/assets/img/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; background: #eef2ff; overflow-x: hidden; }
        
        /* Animated Background Blobs */
        .bg-shapes { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; overflow: hidden; pointer-events: none; }
        .shape { position: absolute; filter: blur(80px); opacity: 0.6; animation: float 20s infinite alternate ease-in-out; }
        .shape-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: #c7d2fe; animation-delay: 0s; }
        .shape-2 { top: 40%; right: -10%; width: 40vw; height: 40vw; background: #e0e7ff; animation-delay: -5s; }
        .shape-3 { bottom: -20%; left: 20%; width: 60vw; height: 60vw; background: #ddd6fe; animation-delay: -10s; }
        @keyframes float { 0% { transform: translate(0, 0) rotate(0deg); } 100% { transform: translate(100px, 50px) rotate(20deg); } }

        .glass-nav { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .glass-card { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07); }
    </style>
</head>
<body class="text-gray-800 selection:bg-indigo-100 selection:text-indigo-900 relative">
    
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <nav class="glass-nav fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i data-lucide="package" class="w-8 h-8 text-indigo-600 mr-2"></i>
                    <span class="font-bold text-xl tracking-tight">1902 POS</span>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Buttons moved to the hero section -->
                </div>
            </div>
        </div>
    </nav>
    <section class="pt-32 pb-20 lg:pt-48 lg:pb-32 px-4 relative">
        <div class="max-w-7xl mx-auto text-center relative z-10">
            <div class="inline-block glass-card text-indigo-700 px-5 py-2 rounded-full text-sm font-bold mb-8 uppercase tracking-wider">
                Crawler Toy Store Edition
            </div>
            <h1 class="text-6xl lg:text-8xl font-extrabold tracking-tight mb-6 text-gray-900 leading-tight">
                Retail made <br/><span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-500">beautifully simple.</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-10 leading-relaxed">
                Manage inventory, process sales, track expenses, monitor capital, and understand your business from one powerful POS system designed for modern retail.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6 mt-4">
                <a href="login.php" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white text-lg px-8 py-4 rounded-2xl font-bold transition-all shadow-xl hover:shadow-indigo-500/40 transform hover:-translate-y-1 flex items-center justify-center">
                    <i data-lucide="log-in" class="w-5 h-5 mr-3"></i> Log In
                </a>
                <span class="text-gray-400 font-medium hidden sm:block">or</span>
                <a href="signup.php" class="w-full sm:w-auto bg-white/60 hover:bg-white text-indigo-700 border-2 border-indigo-100 hover:border-indigo-200 text-lg px-8 py-4 rounded-2xl font-bold transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center backdrop-blur-md">
                    <i data-lucide="user-plus" class="w-5 h-5 mr-3"></i> Create Account
                </a>
            </div>
        </div>
    </section>
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