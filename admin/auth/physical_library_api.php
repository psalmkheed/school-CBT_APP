<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

// ADD BOOK
if ($action === 'add_book') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (empty($title) || empty($author) || empty($category)) {
        echo json_encode(['status' => 'error', 'message' => 'Title, Author, and Category are required.']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO physical_books (title, author, category, isbn, quantity, status) VALUES (?, ?, ?, ?, ?, 'available')");
    if ($stmt->execute([$title, $author, $category, $isbn, $quantity])) {
        echo json_encode(['status' => 'success', 'message' => 'Book added to catalog.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

// DELETE BOOK
if ($action === 'delete_book') {
    $id = $_POST['id'] ?? 0;
    
    // Check if issued
    $check = $conn->prepare("SELECT COUNT(*) FROM physical_book_issues WHERE book_id = ? AND status = 'issued'");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete book. Some copies are currently issued.']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM physical_books WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Book removed from catalog.']);
    }
    exit;
}

// ISSUE BOOK
if ($action === 'issue_book') {
    $book_id = intval($_POST['book_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $user_type = trim($_POST['user_type'] ?? 'student');
    $due_date = trim($_POST['due_date'] ?? '');
    $issue_date = date('Y-m-d');
    
    if (empty($book_id) || empty($user_id) || empty($due_date)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $conn->beginTransaction();
    try {
        // Check availability
        $check = $conn->prepare("SELECT quantity, issued_qty FROM physical_books WHERE id = ? FOR UPDATE");
        $check->execute([$book_id]);
        $book = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception("Book not found.");
        }
        
        if ($book['issued_qty'] >= $book['quantity']) {
            throw new Exception("No available copies to issue.");
        }
        
        // Issue
        $stmt = $conn->prepare("INSERT INTO physical_book_issues (book_id, user_id, user_type, issue_date, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$book_id, $user_id, $user_type, $issue_date, $due_date]);
        
        // Update stats
        $upd = $conn->prepare("UPDATE physical_books SET issued_qty = issued_qty + 1 WHERE id = ?");
        $upd->execute([$book_id]);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Book issued successfully.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// RETURN BOOK
if ($action === 'return_book') {
    $issue_id = intval($_POST['id'] ?? 0);
    $return_date = date('Y-m-d');
    
    $conn->beginTransaction();
    try {
        $check = $conn->prepare("SELECT book_id FROM physical_book_issues WHERE id = ? AND status = 'issued' FOR UPDATE");
        $check->execute([$issue_id]);
        $issue = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$issue) {
            throw new Exception("Invalid or already returned issue.");
        }
        
        $book_id = $issue['book_id'];
        
        // Mark returned
        $stmt = $conn->prepare("UPDATE physical_book_issues SET status = 'returned', return_date = ? WHERE id = ?");
        $stmt->execute([$return_date, $issue_id]);
        
        // Update stats
        $upd = $conn->prepare("UPDATE physical_books SET issued_qty = issued_qty - 1 WHERE id = ?");
        $upd->execute([$book_id]);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Book returned successfully.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
