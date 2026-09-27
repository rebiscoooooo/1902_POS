<?php
// dashboard/staff.php


require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$today = date('Y-m-d');
$cashier_id = $_SESSION['user_id'];

// 1. Today's Sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'COMPLETED' AND DATE(created_at) = ? AND cashier_id = ?");
$stmt->execute([$today, $cashier_id]);
$todaySales = $stmt->fetchColumn();

// 2. Total Transactions Today
$stmt = $pdo->prepare("SELECT COUNT(id) FROM sales WHERE status = 'COMPLETED' AND DATE(created_at) = ? AND cashier_id = ?");
$stmt->execute([$today, $cashier_id]);
$todayTransactions = $stmt->fetchColumn();

// 3. Recent Transactions
$stmt = $pdo->prepare("
    SELECT receipt_no, total, created_at, payment_method 
    FROM sales 
    WHERE cashier_id = ? 
    ORDER BY created_at DESC LIMIT 10
");
$stmt->execute([$cashier_id]);
$recentSales = $stmt->fetchAll();

// Total Commission Earned
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ?");
$stmt->execute([$cashier_id]);
$totalCommission = $stmt->fetchColumn();

// Credited Commission (currently in balance)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ? AND status = 'PENDING'");
$stmt->execute([$cashier_id]);
$creditedCommission = $stmt->fetchColumn();

// Current Balance
$stmt = $pdo->prepare("SELECT balance, name, commission_rate FROM users WHERE id = ?");
$stmt->execute([$cashier_id]);
$staff = $stmt->fetch();
$currentCapital = $staff['balance'] ?? 0;

// Total Withdrawn
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type = 'CASH_OUT' AND purpose = 'Commission Withdrawal' AND description = ?");
$stmt->execute(["Withdrawal by " . $staff['name']]);
$totalWithdrawn = $stmt->fetchColumn();

// Recent Commissions
$stmt = $pdo->prepare("
    SELECT pc.amount, pc.status, pc.created_at, s.receipt_no
    FROM user_commissions pc
    LEFT JOIN sales s ON pc.sale_id = s.id
    WHERE pc.user_id = ?
    ORDER BY pc.created_at DESC LIMIT 10
");
$stmt->execute([$cashier_id]);
$recentCommissions = $stmt->fetchAll();

// Recent Withdrawals
$stmt = $pdo->prepare("
    SELECT amount, transaction_date 
    FROM capital_transactions 
    WHERE type = 'CASH_OUT' AND purpose = 'Commission Withdrawal' AND description = ?
    ORDER BY transaction_date DESC LIMIT 10
");
$stmt->execute(["Withdrawal by " . $staff['name']]);
$recentWithdrawals = $stmt->fetchAll();
?>
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
        <p class="text-gray-500 text-sm">Here is your sales summary for today.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">My Sales Today</p>
            <h3 class="text-3xl font-bold text-gray-900">₱<?= number_format($todaySales, 2) ?></h3>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Available Balance</p>
            <h3 class="text-3xl font-bold text-gray-900 text-green-600">₱<?= number_format($currentCapital, 2) ?></h3>
            <p class="text-xs text-gray-400 mt-2">Comm. Rate: <?= number_format($staff['commission_rate'], 1) ?>%</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Earned</p>
            <h3 class="text-3xl font-bold text-gray-900 text-indigo-600">₱<?= number_format($totalCommission, 2) ?></h3>
        </div>
        <div class="bg-indigo-600 rounded-2xl p-6 shadow-sm flex items-center justify-center">
            <a href="/1902_pos/pos/" class="text-white font-bold text-xl flex items-center hover:text-indigo-200 transition">
                <i data-lucide="monitor-stop" class="w-6 h-6 mr-2"></i> Open Register
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sales Table -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm lg:col-span-1">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="receipt" class="w-5 h-5 mr-2 text-blue-500"></i> My Recent Sales</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Receipt</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentSales)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No transactions found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($sale['receipt_no']) ?></td>
                                <td class="px-4 py-3"><?= date('h:i A', strtotime($sale['created_at'])) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">₱<?= number_format($sale['total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Commissions Table -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm lg:col-span-1">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="trending-up" class="w-5 h-5 mr-2 text-indigo-500"></i> Recent Commissions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentCommissions)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No commissions yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentCommissions as $comm): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3"><?= date('M d, Y', strtotime($comm['created_at'])) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-md font-medium">CREDITED</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-600">₱<?= number_format($comm['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Withdrawals Table -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm lg:col-span-1">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="wallet" class="w-5 h-5 mr-2 text-orange-500"></i> Recent Withdrawals</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentWithdrawals)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No withdrawals yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentWithdrawals as $w): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3"><?= date('M d, Y', strtotime($w['transaction_date'])) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-md font-medium">WITHDRAWN</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-orange-600">-₱<?= number_format($w['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
