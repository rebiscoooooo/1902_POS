<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['ADMIN', 'STAFF']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_products') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    
    $query = "SELECT id, sku, name, selling_price, stock_quantity, image_path FROM products WHERE status = 'ACTIVE' AND stock_quantity > 0";
    $params = [];
    
    if ($search !== '') {
        $query .= " AND (name LIKE ? OR sku LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category !== '') {
        $query .= " AND category_id = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY name ASC LIMIT 50"; // Limit for POS performance
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    jsonResponse(true, '', $products);
}

if ($action === 'checkout') {
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $payment = (float)($_POST['payment'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'CASH';
    
    if (empty($cart)) {
        jsonResponse(false, "Cart is empty.");
    }
    
    $total = $subtotal - $discount;
    if ($payment < $total) {
        jsonResponse(false, "Payment amount is less than total.");
    }
    
    $change = $payment - $total;
    $cashier_name = trim($_POST['cashier_name'] ?? '');
    
    // Attempt to find user ID based on name for commission purposes
    $cashier_id = null;
    if ($cashier_name !== '') {
        $stmtFind = $pdo->prepare("SELECT id FROM users WHERE name = ?");
        $stmtFind->execute([$cashier_name]);
        $found = $stmtFind->fetchColumn();
        if ($found) {
            $cashier_id = $found;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate receipt number
        $year = date('Y');
        $stmt = $pdo->query("SELECT COUNT(*) FROM sales WHERE YEAR(created_at) = '$year'");
        $count = $stmt->fetchColumn() + 1;
        $receipt_no = "POS-$year-" . str_pad($count, 6, '0', STR_PAD_LEFT);
        
        // Insert Sale
        $stmt = $pdo->prepare("INSERT INTO sales (receipt_no, cashier_id, cashier_name, subtotal, discount, total, payment, change_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$receipt_no, $cashier_id, $cashier_name, $subtotal, $discount, $total, $payment, $change, $payment_method]);
        $sale_id = $pdo->lastInsertId();
        
        // Insert Sale Items & Deduct Inventory
        $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, cost_price, selling_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach ($cart as $item) {
            // Verify stock
            $stmtCheck = $pdo->prepare("SELECT stock_quantity, cost_price, name, selling_price FROM products WHERE id = ?");
            $stmtCheck->execute([$item['id']]);
            $product = $stmtCheck->fetch();
            
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for " . ($product['name'] ?? 'an item'));
            }
            
            $item_subtotal = $item['quantity'] * $product['selling_price'];
            
            $stmtItem->execute([
                $sale_id, 
                $item['id'], 
                $product['name'], 
                $item['quantity'], 
                $product['cost_price'], 
                $product['selling_price'], 
                $item_subtotal
            ]);
            
            $stmtUpdateStock->execute([$item['quantity'], $item['id']]);
        }
        
        // Process Partner Commissions (if any active partners)
        // Calculate total profit for this sale
        $stmtProfit = $pdo->prepare("SELECT SUM(subtotal - (quantity * cost_price)) FROM sale_items WHERE sale_id = ?");
        $stmtProfit->execute([$sale_id]);
        $saleProfit = $stmtProfit->fetchColumn();
        
        if ($saleProfit > 0) {
            // 1. Cashier's commission (based on their set commission rate)
            if ($cashier_id) {
                $stmtUser = $pdo->prepare("SELECT commission_rate FROM users WHERE id = ? AND status = 'ACTIVE'");
                $stmtUser->execute([$cashier_id]);
                $cashier = $stmtUser->fetch();
                
                if ($cashier && $cashier['commission_rate'] > 0) {
                    $commission_amount = $saleProfit * ($cashier['commission_rate'] / 100);
                    
                    $stmtComm = $pdo->prepare("INSERT INTO user_commissions (user_id, sale_id, amount) VALUES (?, ?, ?)");
                    $stmtComm->execute([$cashier_id, $sale_id, $commission_amount]);
                    
                    $stmtBal = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmtBal->execute([$commission_amount, $cashier_id]);
                }
            }

            // 2. All partners get 10% commission on the sale profit
            $stmtPartners = $pdo->prepare("SELECT id FROM users WHERE role = 'PARTNER' AND status = 'ACTIVE'");
            $stmtPartners->execute();
            $activePartners = $stmtPartners->fetchAll();

            if ($activePartners) {
                $partner_commission = $saleProfit * 0.10; // 10%
                $stmtComm = $pdo->prepare("INSERT INTO user_commissions (user_id, sale_id, amount) VALUES (?, ?, ?)");
                $stmtBal = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                
                foreach ($activePartners as $partner) {
                    $stmtComm->execute([$partner['id'], $sale_id, $partner_commission]);
                    $stmtBal->execute([$partner_commission, $partner['id']]);
                }
            }
        }
        
        $pdo->commit();
        
        // Return receipt info
        jsonResponse(true, "Transaction completed successfully.", [
            'sale_id' => $sale_id,
            'receipt_no' => $receipt_no
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, "Checkout failed: " . $e->getMessage());
    }
}

jsonResponse(false, "Invalid action.");
?>
