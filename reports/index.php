<?php
// reports/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php

$date_filter = $_GET['date'] ?? 'month';

$whereClause = "";
if ($date_filter === 'today') {
    $whereClause = "DATE(s.created_at) = CURDATE()";
    $expenseWhere = "expense_date = CURDATE()";
    $title = "Today's Report";
} elseif ($date_filter === 'week') {
    $whereClause = "YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    $expenseWhere = "YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)";
    $title = "This Week's Report";
} else { // month
    $whereClause = "MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
    $expenseWhere = "MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
    $title = "This Month's Report";
}

// Gross Sales & Discounts
$stmt = $pdo->query("SELECT COALESCE(SUM(subtotal), 0) as gross, COALESCE(SUM(discount), 0) as discount FROM sales s WHERE status = 'COMPLETED' AND $whereClause");
$salesData = $stmt->fetch();
$gross_sales = $salesData['gross'];
$total_discount = $salesData['discount'];
$net_sales = $gross_sales - $total_discount;

// Cost of Goods Sold (COGS)
$stmt = $pdo->query("SELECT COALESCE(SUM(si.quantity * si.cost_price), 0) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.status = 'COMPLETED' AND $whereClause");
$cogs = $stmt->fetchColumn();

// Gross Profit
$gross_profit = $net_sales - $cogs;

// Expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $expenseWhere");
$total_expenses = $stmt->fetchColumn();

// Net Profit
$net_profit = $gross_profit - $total_expenses;

// Partner Commissions (Approximate pending/paid for the period)
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM partner_commissions pc JOIN sales s ON pc.sale_id = s.id WHERE s.status = 'COMPLETED' AND $whereClause");
$partner_commissions = $stmt->fetchColumn();

// Final Business Profit (after commissions)
$final_profit = $net_profit - $partner_commissions;
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 space-y-4 md:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Financial Reports</h2>
            <p class="text-gray-500 text-sm">Analyze your business performance.</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <form method="GET" class="inline-flex bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                <button type="submit" name="date" value="today" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $date_filter === 'today' ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Today</button>
                <button type="submit" name="date" value="week" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $date_filter === 'week' ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">This Week</button>
                <button type="submit" name="date" value="month" class="px-4 py-1.5 text-sm font-medium rounded-md <?= $date_filter === 'month' ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">This Month</button>
            </form>
            
            <div class="relative group">
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                    <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                    <div class="p-2 space-y-1">
                        <button onclick="exportPDF()" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-md flex items-center transition">
                            <i data-lucide="file-text" class="w-4 h-4 mr-2"></i> Export to PDF
                        </button>
                        <a href="export.php?date=<?= htmlspecialchars($date_filter) ?>" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-md flex items-center transition">
                            <i data-lucide="sheet" class="w-4 h-4 mr-2 text-green-600"></i> Export to Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-10 border border-gray-100 shadow-sm mb-8 relative" id="report-content">
        <!-- Professional Letterhead (looks great on screen and PDF) -->
        <div class="flex flex-col md:flex-row md:items-start justify-between mb-8 pb-6 border-b-2 border-gray-100">
            <div class="flex items-center mb-4 md:mb-0">
                <div class="bg-black p-3 rounded-2xl mr-5 flex-shrink-0 shadow-lg">
                    <img src="/1902_pos/assets/img/logo.jpg" alt="1902 Logo" class="w-16 h-16 object-contain">
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight leading-none mb-1">1902 POS</h2>
                    <p class="text-xs font-bold tracking-widest text-indigo-600 uppercase">Crawler Toy Store</p>
                    <p class="text-xs text-gray-400 mt-2 font-medium">Generated: <?= date('F j, Y, g:i a') ?></p>
                </div>
            </div>
            <div class="text-left md:text-right">
                <h3 class="text-2xl md:text-3xl font-black text-gray-200 uppercase tracking-widest leading-none"><?= $title ?></h3>
                <p class="text-sm text-gray-500 font-bold mt-2 uppercase tracking-wide">Official Financial Summary</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            
            <!-- Income Statement Style -->
            <div>
                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Revenue & Profit</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Gross Sales</span>
                        <span class="font-medium">₱<?= number_format($gross_sales, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Discounts Given</span>
                        <span class="font-medium text-red-500">- ₱<?= number_format($total_discount, 2) ?></span>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex justify-between font-bold text-gray-900">
                        <span>Net Sales</span>
                        <span>₱<?= number_format($net_sales, 2) ?></span>
                    </div>
                    
                    <div class="flex justify-between text-gray-600 mt-4">
                        <span>Cost of Goods Sold (COGS)</span>
                        <span class="font-medium text-orange-500">- ₱<?= number_format($cogs, 2) ?></span>
                    </div>
                    
                    <div class="pt-2 border-t border-gray-100 flex justify-between font-bold text-indigo-700 text-lg mt-2">
                        <span>Gross Profit</span>
                        <span>₱<?= number_format($gross_profit, 2) ?></span>
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Expenses & Final Profit</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Gross Profit</span>
                        <span class="font-medium">₱<?= number_format($gross_profit, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Operating Expenses</span>
                        <span class="font-medium text-red-500">- ₱<?= number_format($total_expenses, 2) ?></span>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex justify-between font-bold text-gray-900">
                        <span>Net Profit Before Comm.</span>
                        <span>₱<?= number_format($net_profit, 2) ?></span>
                    </div>
                    
                    <div class="flex justify-between text-gray-600 mt-4">
                        <span>Partner Commissions</span>
                        <span class="font-medium text-red-500">- ₱<?= number_format($partner_commissions, 2) ?></span>
                    </div>
                    
                    <div class="pt-2 border-t border-gray-100 flex justify-between font-bold text-green-700 text-2xl mt-2">
                        <span>Final Retained Profit</span>
                        <span>₱<?= number_format($final_profit, 2) ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function exportPDF() {
        const element = document.getElementById('report-content');
        const opt = {
            margin:       0.5,
            filename:     '<?= str_replace(["'", " "], ["", "_"], strtolower($title)) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>

<?php require_once '../includes/footer.php'; ?>
