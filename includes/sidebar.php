<?php $role = $_SESSION['role'] ?? 'STAFF'; ?>
<aside id="sidebar" class="glass-sidebar w-64 h-full flex flex-col absolute z-30 lg:relative transition-transform duration-300">
    <div class="h-16 flex items-center px-6 border-b border-white/50 text-indigo-600">
        <img src="/1902_pos/assets/img/logo.jpg" alt="1902 Logo" class="w-10 h-10 mr-3 object-contain" style="mix-blend-mode: multiply; filter: invert(1) contrast(1.2);">
        <div>
            <span class="font-bold text-lg leading-none block">1902 POS</span>
            <span class="text-[10px] uppercase font-semibold text-gray-500 tracking-wider">Crawler Toy Store</span>
        </div>
    </div>
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <a href="/1902_pos/dashboard/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'dashboard' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50 hover:text-gray-900' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3 <?= $currentPage === 'dashboard' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Dashboard
        </a>
        <?php if ($role === 'ADMIN' || $role === 'STAFF'): ?>
        <a href="/1902_pos/pos/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'pos' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50 hover:text-gray-900' ?>">
            <i data-lucide="monitor-stop" class="w-5 h-5 mr-3 <?= $currentPage === 'pos' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> POS / Checkout
        </a>
        <?php endif; ?>
        <?php if (in_array($role, ['ADMIN', 'PARTNER'])): ?>
        <div class="pt-4 pb-2"><p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p></div>
        <a href="/1902_pos/inventory/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'inventory' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="boxes" class="w-5 h-5 mr-3 <?= $currentPage === 'inventory' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Inventory</a>
        <a href="/1902_pos/sales/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'sales' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="receipt" class="w-5 h-5 mr-3 <?= $currentPage === 'sales' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Sales</a>
        <a href="/1902_pos/reports/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'reports' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="bar-chart-3" class="w-5 h-5 mr-3 <?= $currentPage === 'reports' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Reports</a>
        <a href="/1902_pos/expenses/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'expenses' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="wallet" class="w-5 h-5 mr-3 <?= $currentPage === 'expenses' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Expenses</a>
        <a href="/1902_pos/capital/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'capital' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="landmark" class="w-5 h-5 mr-3 <?= $currentPage === 'capital' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Capital</a>
        <a href="/1902_pos/partners/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'partners' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="users" class="w-5 h-5 mr-3 <?= $currentPage === 'partners' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Partners</a>
        <div class="pt-4 pb-2"><p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p></div>
        <a href="/1902_pos/users/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'users' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="users-cog" class="w-5 h-5 mr-3 <?= $currentPage === 'users' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> Users</a>
        <a href="/1902_pos/history/" class="flex items-center px-3 py-2.5 rounded-lg transition-colors <?= $currentPage === 'history' ? 'bg-indigo-100/50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-white/50' ?>"><i data-lucide="history" class="w-5 h-5 mr-3 <?= $currentPage === 'history' ? 'text-indigo-600' : 'text-gray-400' ?>"></i> History Log</a>
        <?php endif; ?>
    </div>
    <div class="border-t border-white/50 p-4">
        <a href="/1902_pos/settings/" class="flex items-center w-full hover:bg-white/60 p-2 rounded-lg transition">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
            <div class="ml-3 flex-1 overflow-hidden">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($_SESSION['role']) ?></p>
            </div>
            <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
        </a>
    </div>
</aside>
<div class="flex-1 flex flex-col min-w-0 bg-transparent">
    <header class="glass-panel h-16 flex items-center justify-between px-4 lg:px-8 rounded-b-none border-t-0 border-x-0">
        <div class="flex items-center">
            <button id="mobile-menu-btn" class="lg:hidden text-gray-500 hover:text-gray-700 p-2 mr-2"><i data-lucide="menu" class="w-6 h-6"></i></button>
            <?php if (isSwitchedUser()): ?>
                <div class="hidden sm:flex items-center bg-amber-100/60 text-amber-800 px-3 py-1.5 rounded-full text-xs font-medium border border-amber-200">
                    <i data-lucide="eye" class="w-4 h-4 mr-1.5"></i> Viewing as <?= htmlspecialchars($_SESSION['name']) ?>
                    <a href="/1902_pos/users/switch_back.php" class="ml-3 underline hover:text-amber-900">Return</a>
                </div>
            <?php else: ?>
                <h1 class="text-xl font-bold text-gray-800 capitalize hidden sm:block">
                    <?= $currentPage === 'pos' ? 'Point of Sale' : str_replace('-', ' ', $currentPage) ?>
                </h1>
            <?php endif; ?>
        </div>
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="hidden sm:block text-sm font-medium text-gray-500 mr-2" id="live-clock">--:-- --</div>
            <a href="/1902_pos/logout.php" class="flex items-center text-sm font-medium text-gray-500 hover:text-red-600 transition">
                <i data-lucide="log-out" class="w-4 h-4 sm:mr-2"></i><span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>
    <main class="flex-1 overflow-y-auto p-4 lg:p-8">