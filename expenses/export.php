<?php
// expenses/export.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('ADMIN');

$query = "SELECT e.expense_date, e.category, e.purpose, u.name as user_name, e.amount, e.created_at 
          FROM expenses e 
          LEFT JOIN users u ON e.recorded_by = u.id 
          ORDER BY e.expense_date DESC, e.created_at DESC";

$stmt = $pdo->query($query);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=expenses_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Expense Date', 'Category', 'Purpose', 'Recorded By', 'Amount', 'Created At']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['expense_date'],
        $row['category'],
        $row['purpose'],
        $row['user_name'] ?? 'System',
        $row['amount'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
