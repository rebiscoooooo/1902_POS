<?php
// sales/export.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole(['ADMIN', 'PARTNER']);

$date_filter = $_GET['date'] ?? 'today';
$search = $_GET['search'] ?? '';

$query = "SELECT s.receipt_no, s.created_at, COALESCE(s.cashier_name, u.name) as cashier_display_name, s.subtotal, s.discount, s.total, s.payment_method, s.status 
          FROM sales s 
          LEFT JOIN users u ON s.cashier_id = u.id 
          WHERE 1=1";
$params = [];

$filename_suffix = $date_filter;

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
    $filename_suffix .= "_search";
}

$query .= " ORDER BY s.created_at DESC LIMIT 1000"; // Increased limit for export

$stmt = $pdo->prepare($query);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_export_' . $filename_suffix . '_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Receipt No', 'Date & Time', 'Cashier', 'Subtotal', 'Discount', 'Total', 'Payment Method', 'Status']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['receipt_no'],
        $row['created_at'],
        $row['cashier_display_name'] ?? 'Unknown',
        $row['subtotal'],
        $row['discount'],
        $row['total'],
        $row['payment_method'],
        $row['status']
    ]);
}

fclose($output);
exit;
