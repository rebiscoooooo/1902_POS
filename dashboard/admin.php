<?php

require_once '../includes/header.php'; require_once '../includes/sidebar.php';
$filter = $_GET['filter'] ?? 'month';
$periodLabel = '';
$whereClause = "";
$expenseWhere = "";

if ($filter === 'today') {
    $periodLabel = 'Today';
    $whereClause = "DATE(s.created_at) = CURDATE()";
    $expenseWhere = "expense_date = CURDATE()";
} elseif ($filter === 'week') {
    $periodLabel = 'This Week';
    $whereClause = "YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    $expenseWhere = "YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)";
} else { // month
    $periodLabel = 'This Month';
    $whereClause = "MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
    $expenseWhere = "MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
}

$stmt = $pdo->query("SELECT COALESCE(SUM(s.total), 0) FROM sales s WHERE s.status = 'COMPLETED' AND $whereClause"); 
$periodSales = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(si.subtotal - (si.quantity * si.cost_price)), 0) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.status = 'COMPLETED' AND $whereClause"); 
$periodProfit = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $expenseWhere"); 
$periodExpenses = $stmt->fetchColumn(); 
$periodNetProfit = $periodProfit - $periodExpenses;

$stmt = $pdo->query("SELECT COALESCE(SUM(cost_price * stock_quantity), 0) as total_cost, COALESCE(SUM(selling_price * stock_quantity), 0) as total_retail FROM products WHERE status = 'ACTIVE'"); 

$invStats = $stmt->fetch();
$inventoryValue = $invStats['total_cost'];
$totalRetailValue = $invStats['total_retail'];
$potentialProfit = $totalRetailValue - $inventoryValue;

$stmt = $pdo->query("SELECT (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_IN', 'PARTNER_INVESTMENT', 'OWNER_INVESTMENT')) - (SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type IN ('CASH_OUT', 'PARTNER_WITHDRAWAL', 'OWNER_WITHDRAWAL')) + (SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'COMPLETED' AND payment_method = 'CASH') - (SELECT COALESCE(SUM(amount), 0) FROM expenses) AS business_cash"); $businessCash = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE'"); $totalProducts = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE' AND stock_quantity <= reorder_level"); $lowStockCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT s.receipt_no, s.total, s.created_at, COALESCE(s.cashier_name, u.name) as cashier FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.created_at DESC LIMIT 5"); $recentSales = $stmt->fetchAll();

// Trend Data for Chart
$trendQuery = "";
if ($filter === 'today') {
    $trendQuery = "SELECT HOUR(s.created_at) as label, SUM(s.total) as amount FROM sales s WHERE s.status = 'COMPLETED' AND $whereClause GROUP BY HOUR(s.created_at) ORDER BY HOUR(s.created_at)";
} else {
    $trendQuery = "SELECT DATE(s.created_at) as label, SUM(s.total) as amount FROM sales s WHERE s.status = 'COMPLETED' AND $whereClause GROUP BY DATE(s.created_at) ORDER BY DATE(s.created_at)";
}
$stmt = $pdo->query($trendQuery);
$trendDataRaw = $stmt->fetchAll();

$trendLabels = [];
$trendAmounts = [];
foreach($trendDataRaw as $row) {
    if ($filter === 'today') {
        $trendLabels[] = str_pad($row['label'], 2, '0', STR_PAD_LEFT) . ':00';
    } else {
        $trendLabels[] = date('M d', strtotime($row['label']));
    }
    $trendAmounts[] = $row['amount'];
}

// Top Selling Products
$topProductsQuery = "
    SELECT p.name, SUM(si.quantity) as total_sold, SUM(si.subtotal) as total_revenue
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    WHERE s.status = 'COMPLETED' AND $whereClause
    GROUP BY si.product_id
    ORDER BY total_sold DESC
    LIMIT 5
";
$stmt = $pdo->query($topProductsQuery);
$topProducts = $stmt->fetchAll();

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 space-y-4 md:space-y-0">
        <div><h2 class="text-2xl font-bold text-gray-900">Dashboard</h2><p class="text-gray-500 text-sm">Business Overview</p></div>
        <form method="GET" class="inline-flex glass-panel rounded-lg p-1 shadow-sm overflow-x-auto max-w-full">
            <button type="submit" name="filter" value="today" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $filter === 'today' ? 'bg-indigo-50/80 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-white/50' ?>">Today</button>
            <button type="submit" name="filter" value="week" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $filter === 'week' ? 'bg-indigo-50/80 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-white/50' ?>">This Week</button>
            <button type="submit" name="filter" value="month" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $filter === 'month' ? 'bg-indigo-50/80 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-white/50' ?>">This Month</button>
        </form>
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

    <!-- Inventory Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="/1902_pos/inventory/index.php" class="glass-panel rounded-2xl p-6 flex items-center justify-between hover:border-indigo-500 transition-all cursor-pointer border-l-4 border-transparent bg-white group shadow-sm">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1 group-hover:text-indigo-600 transition-colors">Total Products in Inventory</p>
                <h3 class="text-3xl font-black text-gray-900"><?= number_format($totalProducts) ?></h3>
            </div>
            <div class="p-4 bg-indigo-50 rounded-2xl text-indigo-600 group-hover:scale-110 transition-transform"><i data-lucide="package" class="w-8 h-8"></i></div>
        </a>
        <a href="/1902_pos/inventory/index.php?filter=low_stock" class="glass-panel rounded-2xl p-6 flex items-center justify-between hover:border-orange-500 transition-all cursor-pointer border-l-4 border-transparent bg-white group shadow-sm">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1 group-hover:text-orange-600 transition-colors">Low Stock Alerts</p>
                <h3 class="text-3xl font-black <?= $lowStockCount > 0 ? 'text-orange-600' : 'text-gray-900' ?>"><?= number_format($lowStockCount) ?> <span class="text-sm font-normal text-gray-500">items need restocking</span></h3>
            </div>
            <div class="p-4 <?= $lowStockCount > 0 ? 'bg-orange-50 text-orange-600' : 'bg-gray-50 text-gray-400' ?> rounded-2xl group-hover:scale-110 transition-transform"><i data-lucide="alert-circle" class="w-8 h-8"></i></div>
        </a>
    </div>

    <!-- Sales Trend Chart Full Width -->
    <div class="glass-panel rounded-2xl p-6 shadow-sm border border-gray-100 mb-8">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="line-chart" class="w-5 h-5 mr-2 text-indigo-500"></i> Sales Trend (<?= $periodLabel ?>)</h3>
        <div class="relative w-full h-64">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Inventory Valuation Chart -->
        <div class="glass-panel rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="pie-chart" class="w-5 h-5 mr-2 text-indigo-500"></i> Overall Inventory Valuation</h3>
            
            <div class="relative w-full flex-1 min-h-[250px] flex items-center justify-center">
                <canvas id="inventoryChart"></canvas>
            </div>
            
            <div class="mt-6 grid grid-cols-2 gap-4 text-center shrink-0">
                <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100">
                    <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest mb-1">Total Cost Value</p>
                    <p class="text-xl font-black text-gray-900">₱<?= number_format($inventoryValue, 2) ?></p>
                </div>
                <div class="bg-green-50/50 rounded-xl p-4 border border-green-100">
                    <p class="text-[10px] text-green-600 font-bold uppercase tracking-widest mb-1">Total Selling Value</p>
                    <p class="text-xl font-black text-gray-900">₱<?= number_format($totalRetailValue, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Top Selling Products Table -->
        <div class="glass-panel rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="award" class="w-5 h-5 mr-2 text-yellow-500"></i> Top Products (<?= $periodLabel ?>)</h3>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Product</th>
                            <th class="px-4 py-3 text-center">Sold</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($topProducts)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No products sold yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $idx => $tp): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-500 text-xs flex items-center justify-center font-bold mr-2"><?= $idx + 1 ?></span>
                                        <span class="font-medium text-gray-900 truncate max-w-[120px]" title="<?= htmlspecialchars($tp['name']) ?>"><?= htmlspecialchars($tp['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-indigo-600"><?= number_format($tp['total_sold']) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">₱<?= number_format($tp['total_revenue'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="glass-panel rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center"><i data-lucide="receipt" class="w-5 h-5 mr-2 text-blue-500"></i> Recent Sales</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Receipt</th>
                            <th class="px-4 py-3">Cashier</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recentSales)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">No sales recorded yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($sale['receipt_no']) ?></td>
                                <td class="px-4 py-3 text-xs"><?= htmlspecialchars($sale['cashier']) ?></td>
                                <td class="px-4 py-3 text-xs"><?= date('h:i A', strtotime($sale['created_at'])) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-600">₱<?= number_format($sale['total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('inventoryChart').getContext('2d');
    
    // Gradient for Cost
    const gradientCost = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCost.addColorStop(0, 'rgba(79, 70, 229, 0.8)'); // Indigo 600
    gradientCost.addColorStop(1, 'rgba(99, 102, 241, 0.8)'); // Indigo 500

    // Gradient for Profit (to make up retail value)
    const gradientProfit = ctx.createLinearGradient(0, 0, 0, 400);
    gradientProfit.addColorStop(0, 'rgba(16, 185, 129, 0.8)'); // Emerald 500
    gradientProfit.addColorStop(1, 'rgba(52, 211, 153, 0.8)'); // Emerald 400

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Total Cost Value', 'Total Selling Value'],
            datasets: [{
                label: 'Valuation (₱)',
                data: [<?= $inventoryValue ?>, <?= $totalRetailValue ?>],
                backgroundColor: [gradientCost, gradientProfit],
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Hide legend since labels explain it
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleFont: { size: 13, family: "'Inter', sans-serif" },
                    bodyFont: { size: 14, weight: 'bold', family: "'Inter', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: "'Inter', sans-serif" },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: "'Inter', sans-serif", weight: 'bold' }
                    }
                }
            }
        }
    });

    // Sales Trend Line Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 400);
    trendGradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)'); // Indigo 600 with opacity
    trendGradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)'); 

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Sales (₱)',
                data: <?= json_encode($trendAmounts) ?>,
                borderColor: 'rgba(79, 70, 229, 1)',
                backgroundColor: trendGradient,
                borderWidth: 2,
                pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(79, 70, 229, 1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleFont: { size: 13, family: "'Inter', sans-serif" },
                    bodyFont: { size: 14, weight: 'bold', family: "'Inter', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif" },
                        callback: function(value) { return '₱' + value.toLocaleString(); }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: "'Inter', sans-serif" } }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>