<?php
// capital/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] !== 'ADMIN') die("Unauthorized");
    $type = $_POST['type'] ?? '';
    $amount = (float)$_POST['amount'] ?? 0;
    $purpose = trim($_POST['purpose'] ?? '');
    
    if ($amount > 0 && !empty($purpose) && in_array($type, ['CASH_IN', 'CASH_OUT', 'PARTNER_INVESTMENT', 'PARTNER_WITHDRAWAL', 'OWNER_INVESTMENT', 'OWNER_WITHDRAWAL'])) {
        $stmt = $pdo->prepare("INSERT INTO capital_transactions (type, amount, purpose, recorded_by, transaction_date) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$type, $amount, $purpose, $_SESSION['user_id']]);
        require_once '../includes/functions.php';
        logActivity($pdo, $_SESSION['user_id'], 'CAPITAL_TRANSACTION', "Recorded $type of ₱$amount for $purpose");
        echo "<script>window.location.href='index.php?success=1';</script>";
        exit;
    }
}

$stmt = $pdo->query("SELECT ct.*, u.name as user_name FROM capital_transactions ct LEFT JOIN users u ON ct.recorded_by = u.id ORDER BY ct.transaction_date DESC LIMIT 50");
$transactions = $stmt->fetchAll();

// Calculate total business cash (simplified calculation)
$stmt = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_IN', 'PARTNER_INVESTMENT', 'OWNER_INVESTMENT')) -
        (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_OUT', 'PARTNER_WITHDRAWAL', 'OWNER_WITHDRAWAL')) +
        (SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'COMPLETED' AND payment_method = 'CASH') -
        (SELECT COALESCE(SUM(amount), 0) FROM expenses)
    AS business_cash
");
$businessCash = $stmt->fetchColumn();

// Calculate Total Partner Capital
$stmt = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type = 'PARTNER_INVESTMENT') -
        (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type = 'PARTNER_WITHDRAWAL')
    AS partner_capital
");
$partnerCapital = $stmt->fetchColumn();
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Capital Management</h2>
            <p class="text-gray-500 text-sm">Track investments, cash-ins, and withdrawals.</p>
        </div>
        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
        <button onclick="openModal()" class="mt-4 md:mt-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Record Transaction
        </button>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 shrink-0">
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-blue-50 opacity-50"><i data-lucide="landmark" class="w-32 h-32"></i></div>
            <p class="text-sm font-medium text-gray-500 mb-1 relative z-10">Business Cash (Estimated)</p>
            <h3 class="text-3xl font-bold text-gray-900 relative z-10">₱<?= number_format($businessCash, 2) ?></h3>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-indigo-50 opacity-50"><i data-lucide="users" class="w-32 h-32"></i></div>
            <p class="text-sm font-medium text-gray-500 mb-1 relative z-10">Total Partner Capital</p>
            <h3 class="text-3xl font-bold text-gray-900 text-indigo-600 relative z-10">₱<?= number_format($partnerCapital, 2) ?></h3>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Purpose</th>
                        <th class="px-6 py-4">Recorded By</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No transactions recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4"><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                    $is_in = in_array($t['type'], ['CASH_IN', 'PARTNER_INVESTMENT', 'OWNER_INVESTMENT']);
                                    $color = $is_in ? 'text-green-700 bg-green-50' : 'text-orange-700 bg-orange-50';
                                ?>
                                <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold <?= $color ?> rounded-full">
                                    <?= str_replace('_', ' ', $t['type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($t['purpose']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($t['user_name'] ?? 'System') ?></td>
                            <td class="px-6 py-4 text-right font-bold <?= $is_in ? 'text-green-600' : 'text-orange-600' ?>">
                                <?= $is_in ? '+' : '-' ?>₱<?= number_format($t['amount'], 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="txnModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-2xl w-full max-w-md shadow-xl overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between">
                <h3 class="text-lg font-bold">Record Transaction</h3>
                <button onclick="closeModal()"><i data-lucide="x" class="w-5 h-5 text-gray-400"></i></button>
            </div>
            <form method="POST" class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                        <option value="CASH_IN">Cash In (Add to Register)</option>
                        <option value="CASH_OUT">Cash Out (Remove from Register)</option>
                        <option value="PARTNER_INVESTMENT">Partner Investment</option>
                        <option value="PARTNER_WITHDRAWAL">Partner Withdrawal</option>
                        <option value="OWNER_INVESTMENT">Owner Investment</option>
                        <option value="OWNER_WITHDRAWAL">Owner Withdrawal</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose / Description</label>
                    <input type="text" name="purpose" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required placeholder="e.g. Added change fund">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded-lg hover:bg-indigo-700 transition">Save Transaction</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() { document.getElementById('txnModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('txnModal').classList.add('hidden'); }
</script>

<?php require_once '../includes/footer.php'; ?>
