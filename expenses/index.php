<?php
// expenses/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] !== 'ADMIN') die("Unauthorized");
    $category = $_POST['category'] ?? '';
    $amount = (float)$_POST['amount'] ?? 0;
    $purpose = trim($_POST['purpose'] ?? '');
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    
    if ($amount > 0 && !empty($purpose) && !empty($category)) {
        $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, purpose, recorded_by, expense_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category, $amount, $purpose, $_SESSION['user_id'], $expense_date]);
        require_once '../includes/functions.php';
        logActivity($pdo, $_SESSION['user_id'], 'EXPENSE_RECORDED', "Recorded expense ₱$amount for $purpose");
        echo "<script>window.location.href='index.php?success=1';</script>";
        exit;
    }
}

$stmt = $pdo->query("SELECT e.*, u.name as user_name FROM expenses e LEFT JOIN users u ON e.recorded_by = u.id ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 100");
$expenses = $stmt->fetchAll();

// Summaries
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURDATE()");
$todayExpenses = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
$monthExpenses = $stmt->fetchColumn();
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Expenses</h2>
            <p class="text-gray-500 text-sm">Track and manage business expenses.</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 md:mt-0">
            <a href="export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="sheet" class="w-4 h-4 mr-2"></i> Export Excel
            </a>
            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
            <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Expense
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 shrink-0">
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1 relative z-10">Today's Expenses</p>
            <h3 class="text-3xl font-bold text-gray-900 relative z-10">₱<?= number_format($todayExpenses, 2) ?></h3>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm relative overflow-hidden">
            <p class="text-sm font-medium text-gray-500 mb-1 relative z-10">This Month's Expenses</p>
            <h3 class="text-3xl font-bold text-gray-900 text-orange-600 relative z-10">₱<?= number_format($monthExpenses, 2) ?></h3>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Purpose</th>
                        <th class="px-6 py-4">Recorded By</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($expenses)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No expenses recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-gray-100 text-gray-600 rounded-full"><?= htmlspecialchars($e['category']) ?></span></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($e['purpose']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($e['user_name'] ?? 'System') ?></td>
                            <td class="px-6 py-4 text-right font-bold text-orange-600">₱<?= number_format($e['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="expenseModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-2xl w-full max-w-md shadow-xl overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between">
                <h3 class="text-lg font-bold">Add Expense</h3>
                <button onclick="closeModal()"><i data-lucide="x" class="w-5 h-5 text-gray-400"></i></button>
            </div>
            <form method="POST" class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                        <option value="Supplies">Supplies</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Rent">Rent</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose / Description</label>
                    <input type="text" name="purpose" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required placeholder="e.g. Bought printer ink">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded-lg hover:bg-indigo-700 transition">Save Expense</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() { document.getElementById('expenseModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('expenseModal').classList.add('hidden'); }
</script>

<?php require_once '../includes/footer.php'; ?>
