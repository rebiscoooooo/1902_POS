<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('ADMIN');

$date_filter = $_GET['date'] ?? 'month';

$whereClause = "";
if ($date_filter === 'today') {
    $whereClause = "DATE(created_at) = CURDATE()";
    $filename = "sales_report_today_" . date('Y-m-d') . ".csv";
} elseif ($date_filter === 'week') {
    $whereClause = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    $filename = "sales_report_week_" . date('Y-m-d') . ".csv";
} else { 
    $whereClause = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $filename = "sales_report_month_" . date('Y-m') . ".csv";
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, ['Receipt No', 'Cashier', 'Date', 'Subtotal', 'Discount', 'Total', 'Payment Method', 'Status']);

$stmt = $pdo->query("SELECT s.receipt_no, u.name as cashier, s.created_at, s.subtotal, s.discount, s.total, s.payment_method, s.status 
                     FROM sales s 
                     LEFT JOIN users u ON s.cashier_id = u.id 
                     WHERE $whereClause 
                     ORDER BY s.created_at DESC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['receipt_no'],
        $row['cashier'] ?? 'System',
        $row['created_at'],
        $row['subtotal'],
        $row['discount'],
        $row['total'],
        $row['payment_method'],
        $row['status']
    ]);
}

fclose($output);
exit;
