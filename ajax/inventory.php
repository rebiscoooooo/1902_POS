<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['ADMIN', 'PARTNER']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category !== '') {
        $query .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    if ($status !== '') {
        if ($status === 'low_stock') {
            $query .= " AND p.stock_quantity <= p.reorder_level AND p.status = 'ACTIVE'";
        } else if ($status === 'out_of_stock') {
            $query .= " AND p.stock_quantity = 0 AND p.status = 'ACTIVE'";
        } else {
            $query .= " AND p.status = ?";
            $params[] = $status;
        }
    }
    
    $query .= " ORDER BY p.name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    jsonResponse(true, '', $products);
}

if ($action === 'get_categories') {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    jsonResponse(true, '', $stmt->fetchAll());
}

if ($action === 'save') {
    if ($_SESSION['role'] !== 'ADMIN') jsonResponse(false, 'Unauthorized');
    $id = $_POST['id'] ?? '';
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $reorder_level = (int)($_POST['reorder_level'] ?? 10);
    $status = $_POST['status'] ?? 'ACTIVE';

    if (empty($sku) || empty($name)) {
        jsonResponse(false, "SKU and Name are required.");
    }
    
    if (empty($category_id)) $category_id = null;

    try {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, category_id=?, cost_price=?, selling_price=?, stock_quantity=?, reorder_level=?, status=? WHERE id=?");
            $stmt->execute([$sku, $name, $category_id, $cost_price, $selling_price, $stock_quantity, $reorder_level, $status, $id]);
            logActivity($pdo, $_SESSION['user_id'], 'PRODUCT_UPDATED', "Updated product $sku");
            jsonResponse(true, "Product updated successfully.");
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO products (sku, name, category_id, cost_price, selling_price, stock_quantity, reorder_level, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sku, $name, $category_id, $cost_price, $selling_price, $stock_quantity, $reorder_level, $status]);
            logActivity($pdo, $_SESSION['user_id'], 'PRODUCT_CREATED', "Created product $sku");
            jsonResponse(true, "Product added successfully.");
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation
            jsonResponse(false, "SKU already exists.");
        }
        jsonResponse(false, "Database error: " . $e->getMessage());
    }
}

if ($action === 'delete') {
    if ($_SESSION['role'] !== 'ADMIN') jsonResponse(false, 'Unauthorized');
    $id = $_POST['id'] ?? '';
    if (!$id) jsonResponse(false, "ID required.");
    
    try {
        $stmt = $pdo->prepare("SELECT sku FROM products WHERE id=?");
        $stmt->execute([$id]);
        $sku = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
        
        logActivity($pdo, $_SESSION['user_id'], 'PRODUCT_DELETED', "Deleted product $sku");
        jsonResponse(true, "Product deleted successfully.");
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            // Probably tied to a sale
            jsonResponse(false, "Cannot delete product because it is linked to past sales. Consider setting status to INACTIVE instead.");
        }
        jsonResponse(false, "Failed to delete product.");
    }
}

jsonResponse(false, "Invalid action.");
?>
