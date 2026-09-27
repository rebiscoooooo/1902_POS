<?php
// dashboard/partner.php


require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Get partner info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$partner = $stmt->fetch();

if (!$partner) {
    echo "<div class='p-8'><p>Partner record not found. Please contact administration.</p></div>";
    require_once '../includes/footer.php';
    exit;
}

// Total Commission Earned
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ?");
$stmt->execute([$user_id]);
$totalCommission = $stmt->fetchColumn();

// Credited Commission (currently in balance)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ? AND status = 'PENDING'");
$stmt->execute([$user_id]);
$creditedCommission = $stmt->fetchColumn();

// Current Balance
$currentCapital = $partner['balance'];

// Total Withdrawn
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type = 'CASH_OUT' AND purpose = 'Commission Withdrawal' AND description = ?");
$stmt->execute(["Withdrawal by " . $partner['name']]);
$totalWithdrawn = $stmt->fetchColumn();

// Recent Commissions
$stmt = $pdo->prepare("
    SELECT pc.amount, pc.status, pc.created_at, s.receipt_no
    FROM user_commissions pc
    LEFT JOIN sales s ON pc.sale_id = s.id
    WHERE pc.user_id = ?
    ORDER BY pc.created_at DESC LIMIT 10
");
$stmt->execute([$user_id]);
$recentCommissions = $stmt->fetchAll();

// Recent Withdrawals
$stmt = $pdo->prepare("
    SELECT amount, transaction_date 
    FROM capital_transactions 
    WHERE type = 'CASH_OUT' AND purpose = 'Commission Withdrawal' AND description = ?
    ORDER BY transaction_date DESC LIMIT 10
");
$stmt->execute(["Withdrawal by " . $partner['name']]);
$recentWithdrawals = $stmt->fetchAll();
?>
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Partner Dashboard</h2>
        <p class="text-gray-500 text-sm">Welcome back, <?= htmlspecialchars($partner['name']) ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Available Balance</p>
            <h3 class="text-3xl font-bold text-gray-900">₱<?= number_format($currentCapital, 2) ?></h3>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Earned</p>
            <h3 class="text-3xl font-bold text-gray-900 text-indigo-600">₱<?= number_format($totalCommission, 2) ?></h3>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Credited / Unwithdrawn</p>
            <h3 class="text-3xl font-bold text-gray-900 text-green-500">₱<?= number_format($creditedCommission, 2) ?></h3>
            <p class="text-xs text-gray-400 mt-2">Commission rate: <?= number_format($partner['commission_rate'], 1) ?>%</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Withdrawn</p>
            <h3 class="text-3xl font-bold text-gray-900 text-orange-500">₱<?= number_format($totalWithdrawn, 2) ?></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Commissions Table -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="trending-up" class="w-5 h-5 mr-2 text-indigo-500"></i> Recent Commissions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Date</th>
                            <th class="px-4 py-3">Receipt Ref</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentCommissions)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">No commissions recorded yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentCommissions as $comm): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3"><?= date('M d, Y', strtotime($comm['created_at'])) ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($comm['receipt_no'] ?? 'N/A') ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($comm['status'] === 'PAID'): ?>
                                        <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-md font-medium">PAID</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-md font-medium">CREDITED</span>
                                    <?php endif; ?>
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
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="wallet" class="w-5 h-5 mr-2 text-orange-500"></i> Recent Withdrawals</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Date & Time</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentWithdrawals)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No withdrawals recorded yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentWithdrawals as $w): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3"><?= date('M d, Y h:i A', strtotime($w['transaction_date'])) ?></td>
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
