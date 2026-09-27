<?php
// sales/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

$role = $_SESSION['role'];

// Filter logic
$date_filter = $_GET['date'] ?? 'today';
$search = $_GET['search'] ?? '';

$query = "SELECT s.*, COALESCE(s.cashier_name, u.name) as cashier_display_name 
          FROM sales s 
          LEFT JOIN users u ON s.cashier_id = u.id 
          WHERE 1=1";
$params = [];

if ($date_filter === 'today') {
    $query .= " AND DATE(s.created_at) = CURDATE()";
} elseif ($date_filter === 'yesterday') {
    $query .= " AND DATE(s.created_at) = CURDATE() - INTERVAL 1 DAY";
} elseif ($date_filter === 'week') {
    $query .= " AND YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter === 'month') {
    $query .= " AND MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
}

if ($search) {
    $query .= " AND (s.receipt_no LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY s.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Calculate totals for view
$total_revenue = 0;
$total_discount = 0;
foreach ($sales as $sale) {
    if ($sale['status'] === 'COMPLETED') {
        $total_revenue += $sale['total'];
        $total_discount += $sale['discount'];
    }
}
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 space-y-4 md:space-y-0 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Sales History</h2>
            <p class="text-gray-500 text-sm">View and manage all transactions.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 shrink-0">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mr-4">
                <i data-lucide="receipt" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Transactions (Filtered)</p>
                <p class="text-xl font-bold text-gray-900"><?= count($sales) ?></p>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center">
            <div class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center mr-4">
                <i data-lucide="banknote" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Revenue (Filtered)</p>
                <p class="text-xl font-bold text-gray-900">₱<?= number_format($total_revenue, 2) ?></p>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center">
            <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center mr-4">
                <i data-lucide="tag" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Discounts</p>
                <p class="text-xl font-bold text-gray-900">₱<?= number_format($total_discount, 2) ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 shrink-0">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search receipt or cashier..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none text-sm">
            </div>
            <div class="w-full md:w-48">
                <select name="date" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none text-sm bg-gray-50 text-gray-700" onchange="this.form.submit()">
                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $date_filter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Time (Limit 100)</option>
                </select>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="submit" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-4 py-2 rounded-lg font-medium transition text-sm flex items-center justify-center flex-1 md:flex-none">
                    Filter
                </button>
                <a href="export.php?date=<?= htmlspecialchars($date_filter) ?>&search=<?= urlencode($search) ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition text-sm flex items-center justify-center flex-1 md:flex-none shadow-sm">
                    <i data-lucide="sheet" class="w-4 h-4 mr-2"></i> Export Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Receipt No.</th>
                        <th class="px-6 py-4">Date & Time</th>
                        <th class="px-6 py-4">Cashier</th>
                        <th class="px-6 py-4 text-right">Total</th>
                        <th class="px-6 py-4 text-center">Payment</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i data-lucide="search-X" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p>No sales found for the selected filters.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 font-bold text-gray-900"><?= htmlspecialchars($sale['receipt_no']) ?></td>
                            <td class="px-6 py-4"><?= date('M d, Y h:i A', strtotime($sale['created_at'])) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($sale['cashier_display_name'] ?? 'Unknown') ?></td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">₱<?= number_format($sale['total'], 2) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-gray-100 text-gray-600 rounded-full">
                                    <?= htmlspecialchars($sale['payment_method']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($sale['status'] === 'COMPLETED'): ?>
                                    <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-green-100 text-green-700 rounded-full">Completed</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-red-100 text-red-700 rounded-full"><?= $sale['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button onclick="printReceipt(<?= $sale['id'] ?>)" class="p-1.5 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition" title="Print Receipt">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function printReceipt(id) {
        const url = `/1902_pos/pos/receipt.php?id=${id}&print=1`;
        const printWindow = window.open(url, 'Print', 'left=200, top=200, width=400, height=600, toolbar=0, resizable=0');
        printWindow.focus();
    }
</script>

<?php require_once '../includes/footer.php'; ?>
