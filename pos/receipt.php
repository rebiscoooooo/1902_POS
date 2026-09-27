<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole(['ADMIN', 'STAFF']);

$sale_id = $_GET['id'] ?? 0;
$print = $_GET['print'] ?? 0;

$stmt = $pdo->prepare("
    SELECT s.*, 
           COALESCE(s.cashier_name, u.name) as cashier_display_name 
    FROM sales s 
    LEFT JOIN users u ON s.cashier_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Receipt not found.");
}

$stmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

// Get settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($sale['receipt_no']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');
        
        body {
            font-family: 'Space Mono', 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #111;
            background: #f3f4f6;
            margin: 0;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }
        .receipt-container {
            background: #fff;
            width: 300px;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        /* Zigzag bottom edge effect for digital preview */
        .receipt-container::after {
            content: "";
            position: absolute;
            bottom: -8px;
            left: 0;
            right: 0;
            height: 8px;
            background-size: 16px 16px;
            background-image: linear-gradient(135deg, transparent 25%, #fff 25%, #fff 50%, transparent 50%, transparent 75%, #fff 75%, #fff 100%), linear-gradient(225deg, transparent 25%, #fff 25%, #fff 50%, transparent 50%, transparent 75%, #fff 75%, #fff 100%);
            background-position: 0 0, 8px 0;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .mb-2 { margin-bottom: 8px; }
        .mt-2 { margin-top: 8px; }
        .divider { border-bottom: 1px dashed #aaa; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th { padding: 4px 0; border-bottom: 1px dashed #aaa; font-weight: 700; }
        td { padding: 6px 0; vertical-align: top; }
        
        .store-title { margin:0 0 4px 0; font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
        .meta-text { color: #555; font-size: 11px; margin: 2px 0; }
        
        @media print {
            body { background: none; padding: 0; display: block; width: 100%; }
            .receipt-container { box-shadow: none; border-radius: 0; padding: 0; width: 100%; max-width: 300px; margin: 0 auto; }
            .receipt-container::after { display: none; }
            @page { margin: 0; }
        }
    </style>
</head>
<body>
<div class="receipt-container">

    <div class="text-center mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 8px auto; display: block;"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
        <h2 class="store-title"><?= htmlspecialchars($settings['store_name'] ?? '1902 POS') ?></h2>
        <p class="meta-text"><?= nl2br(htmlspecialchars($settings['store_address'] ?? '')) ?></p>
        <p class="meta-text"><?= htmlspecialchars($settings['store_contact'] ?? '') ?></p>
    </div>

    <div class="divider"></div>

    <div>
        <p class="meta-text"><span class="font-bold">RCPT:</span> <?= htmlspecialchars($sale['receipt_no']) ?></p>
        <p class="meta-text"><span class="font-bold">DATE:</span> <?= date('M d, Y', strtotime($sale['created_at'])) ?> <?= date('h:i A', strtotime($sale['created_at'])) ?></p>
        <p class="meta-text"><span class="font-bold">CASHIER:</span> <?= htmlspecialchars($sale['cashier_display_name'] ?? 'System') ?></p>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="text-left" style="width: 45%;"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-center" style="width: 15%;"><?= $item['quantity'] ?></td>
                <td class="text-right" style="width: 20%;">₱<?= number_format($item['selling_price'], 2) ?></td>
                <td class="text-right" style="width: 20%;">₱<?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Subtotal:</td>
            <td class="text-right">₱<?= number_format($sale['subtotal'], 2) ?></td>
        </tr>
        <?php if ($sale['discount'] > 0): ?>
        <tr>
            <td class="text-left">Discount:</td>
            <td class="text-right">-₱<?= number_format($sale['discount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="text-left font-bold" style="font-size: 14px;">TOTAL:</td>
            <td class="text-right font-bold" style="font-size: 14px;">₱<?= number_format($sale['total'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Payment (<?= $sale['payment_method'] ?>):</td>
            <td class="text-right">₱<?= number_format($sale['payment'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-left font-bold">Change:</td>
            <td class="text-right font-bold">₱<?= number_format($sale['change_amount'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center mt-2">
        <p class="meta-text font-bold" style="font-size: 12px; margin-bottom: 4px;"><?= nl2br(htmlspecialchars($settings['receipt_footer'] ?? 'Thank you!')) ?></p>
        <p class="meta-text" style="font-size: 9px;">Powered by 1902 POS</p>
    </div>
</div>

    <?php if ($print): ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>
</body>
</html>
