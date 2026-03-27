<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

// ADD ITEM
if ($action === 'add_item') {
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    
    if (empty($item_name) || empty($category)) {
        echo json_encode(['status' => 'error', 'message' => 'Item name and category are required.']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO inventory_items (item_name, category, stock_quantity, location) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$item_name, $category, $stock_quantity, $location])) {
        echo json_encode(['status' => 'success', 'message' => 'Asset registered successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

// DELETE ITEM
if ($action === 'delete_item') {
    $id = $_POST['id'] ?? 0;
    
    // Check issuances
    $check = $conn->prepare("SELECT COUNT(*) FROM inventory_issues WHERE item_id = ? AND status = 'issued'");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete asset. Some items are currently issued to staff.']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Asset deleted forever.']);
    }
    exit;
}

// ADD STOCK
if ($action === 'add_stock') {
    $id = intval($_POST['id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if ($quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity must be positive.']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE inventory_items SET stock_quantity = stock_quantity + ? WHERE id = ?");
    if ($stmt->execute([$quantity, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Stock updated.']);
    }
    exit;
}

// ISSUE ITEM
if ($action === 'issue_item') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $issued_to = intval($_POST['issued_to_user_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $issue_date = date('Y-m-d');
    
    if (empty($item_id) || empty($issued_to) || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid Item, recipient, and quantity are required.']);
        exit;
    }

    $conn->beginTransaction();
    try {
        // Evaluate stock
        $check = $conn->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ? FOR UPDATE");
        $check->execute([$item_id]);
        $item = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new Exception("Asset not found.");
        }
        if ($item['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock. Only {$item['stock_quantity']} left.");
        }
        
        // Form issue record
        $stmt = $conn->prepare("INSERT INTO inventory_issues (item_id, issued_to_user_id, quantity, issue_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$item_id, $issued_to, $quantity, $issue_date]);
        
        // Deplete stock
        $upd = $conn->prepare("UPDATE inventory_items SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $upd->execute([$quantity, $item_id]);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Asset completely issued.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// RETURN ITEM
if ($action === 'return_item') {
    $issue_id = intval($_POST['id'] ?? 0);
    $return_date = date('Y-m-d');
    
    $conn->beginTransaction();
    try {
        $check = $conn->prepare("SELECT item_id, quantity FROM inventory_issues WHERE id = ? AND status = 'issued' FOR UPDATE");
        $check->execute([$issue_id]);
        $issue = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$issue) {
            throw new Exception("Invalid or already completed issue.");
        }
        
        // Mark returned
        $stmt = $conn->prepare("UPDATE inventory_issues SET status = 'returned', return_date = ? WHERE id = ?");
        $stmt->execute([$return_date, $issue_id]);
        
        // Restore stock
        $upd = $conn->prepare("UPDATE inventory_items SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $upd->execute([$issue['quantity'], $issue['item_id']]);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Item marked as returned and stock restored.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
