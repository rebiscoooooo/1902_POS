<?php

require_once '../includes/header.php'; require_once '../includes/sidebar.php';
$today = date('Y-m-d'); $firstDayOfMonth = date('Y-m-01');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'COMPLETED' AND DATE(created_at) >= ?"); $stmt->execute([$firstDayOfMonth]); $periodSales = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(si.subtotal - (si.quantity * si.cost_price)), 0) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.status = 'COMPLETED' AND DATE(s.created_at) >= ?"); $stmt->execute([$firstDayOfMonth]); $periodProfit = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date >= ?"); $stmt->execute([$firstDayOfMonth]); $periodExpenses = $stmt->fetchColumn(); $periodNetProfit = $periodProfit - $periodExpenses;
$stmt = $pdo->query("SELECT COALESCE(SUM(cost_price * stock_quantity), 0) FROM products WHERE status = 'ACTIVE'"); $inventoryValue = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_IN', 'PARTNER_INVESTMENT', 'OWNER_INVESTMENT')) - (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_OUT', 'PARTNER_WITHDRAWAL', 'OWNER_WITHDRAWAL')) + (SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'COMPLETED' AND payment_method = 'CASH') - (SELECT COALESCE(SUM(amount), 0) FROM expenses) AS business_cash"); $businessCash = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE'"); $totalProducts = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE' AND stock_quantity <= reorder_level"); $lowStockCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT s.receipt_no, s.total, s.created_at, COALESCE(s.cashier_name, u.name) as cashier FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.created_at DESC LIMIT 5"); $recentSales = $stmt->fetchAll();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 space-y-4 md:space-y-0">
        <div><h2 class="text-2xl font-bold text-gray-900">Dashboard</h2><p class="text-gray-500 text-sm">Business Overview</p></div>
        <div class="inline-flex glass-panel rounded-lg p-1 shadow-sm overflow-x-auto max-w-full">
            <button class="px-4 py-1.5 text-sm font-medium rounded-md text-gray-600 hover:bg-white/50">Today</button>
            <button class="px-4 py-1.5 text-sm font-medium rounded-md text-gray-600 hover:bg-white/50">This Week</button>
            <button class="px-4 py-1.5 text-sm font-medium rounded-md bg-indigo-50/80 text-indigo-700 shadow-sm">This Month</button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-indigo-100 opacity-50"><i data-lucide="trending-up" class="w-32 h-32"></i></div>
            <div class="relative z-10"><p class="text-sm font-medium text-gray-600 mb-1">Period Sales</p><h3 class="text-3xl font-bold text-gray-900 mb-2"> <?= number_format($periodSales, 2) ?></h3></div>
        </div>
        <div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-green-100 opacity-50"><i data-lucide="banknote" class="w-32 h-32"></i></div>
            <div class="relative z-10"><p class="text-sm font-medium text-gray-600 mb-1">Period Net Profit</p><h3 class="text-3xl font-bold text-gray-900 mb-2"> <?= number_format($periodNetProfit, 2) ?></h3></div>
        </div>
        <div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-orange-100 opacity-50"><i data-lucide="boxes" class="w-32 h-32"></i></div>
            <div class="relative z-10"><p class="text-sm font-medium text-gray-600 mb-1">Inventory Value</p><h3 class="text-3xl font-bold text-gray-900 mb-2"> <?= number_format($inventoryValue, 2) ?></h3></div>
        </div>
        <div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-blue-100 opacity-50"><i data-lucide="landmark" class="w-32 h-32"></i></div>
            <div class="relative z-10"><p class="text-sm font-medium text-gray-600 mb-1">Business Cash</p><h3 class="text-3xl font-bold text-gray-900 mb-2"> <?= number_format($businessCash, 2) ?></h3></div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>