<?php
require '../../connections/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'edit_fee_category') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $assigned_class = trim($_POST['assigned_class'] ?? '');

    if ($id <= 0 || empty($name) || $amount <= 0 || empty($assigned_class)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields correctly.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // 1. Update Category Details
        $stmt = $conn->prepare("UPDATE finance_fee_categories SET name = ?, amount = ?, assigned_class = ? WHERE id = ?");
        $stmt->execute([$name, $amount, $assigned_class, $id]);

        // 2. Adjust Amounts for Existing Student Fees
        $update_amounts = $conn->prepare("UPDATE finance_student_fees SET amount_due = ? WHERE fee_category_id = ?");
        $update_amounts->execute([$amount, $id]);

        // 3. Recalculate Status based on new amount_due vs amount_paid
        $update_status = $conn->prepare("
            UPDATE finance_student_fees 
            SET status = CASE 
                WHEN amount_paid >= amount_due THEN 'paid' 
                WHEN amount_paid > 0 THEN 'partial' 
                ELSE 'unpaid' 
            END 
            WHERE fee_category_id = ?
        ");
        $update_status->execute([$id]);

        // 4. Handle Target Class Shift (Add/Remove Unpaid Students)
        if ($assigned_class === 'All') {
            // Delete unpaid students that are somehow not in users anymore or just sync (usually not needed for 'All')
            // Get all students
            $students_stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'student' AND status = 1");
            $students_stmt->execute();
        } else {
            // Remove unpaid records for students NOT in the new class
            $del_unpaid = $conn->prepare("
                DELETE f FROM finance_student_fees f
                JOIN users u ON f.student_id = u.user_id
                WHERE f.fee_category_id = ? AND f.amount_paid = 0 AND u.class != ?
            ");
            $del_unpaid->execute([$id, $assigned_class]);

            // Get students in the new class
            $students_stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'student' AND class = ? AND status = 1");
            $students_stmt->execute([$assigned_class]);
        }

        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Insert for new students in the target class who don't already have this fee
        if (count($students) > 0) {
            $check_fee = $conn->prepare("SELECT id FROM finance_student_fees WHERE student_id = ? AND fee_category_id = ?");
            $insert_fee = $conn->prepare("INSERT INTO finance_student_fees (student_id, fee_category_id, amount_due, amount_paid, status) VALUES (?, ?, ?, 0.00, 'unpaid')");
            
            foreach ($students as $student) {
                $check_fee->execute([$student['user_id'], $id]);
                if ($check_fee->rowCount() === 0) {
                    $insert_fee->execute([$student['user_id'], $id, $amount]);
                }
            }
        }

        if (function_exists('recordActivity')) {
            recordActivity($conn, "FEE_UPDATED", "Updated fee category #$id: $name to N".number_format($amount)." for $assigned_class");
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Fee category updated and student fees adjusted!']);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_fee_category') {
    $name = trim($_POST['name'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $assigned_class = trim($_POST['assigned_class'] ?? '');
    $academic_session = trim($_POST['academic_session'] ?? '');
    $term = trim($_POST['term'] ?? '');

    if (empty($name) || empty($amount) || empty($academic_session) || empty($term)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required. Make sure an active session is set.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO finance_fee_categories (name, amount, assigned_class, academic_session, term) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $amount, $assigned_class, $academic_session, $term]);
        
        $cat_id = $conn->lastInsertId();

        // Assign fee to students automatically
        if ($assigned_class === 'All') {
            $students_stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'student' AND status = 1");
            $students_stmt->execute();
        } else {
            $students_stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'student' AND class = ? AND status = 1");
            $students_stmt->execute([$assigned_class]);
        }
        
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($students) > 0) {
            $insert_fee_stmt = $conn->prepare("INSERT INTO finance_student_fees (student_id, fee_category_id, amount_due, amount_paid, status) VALUES (?, ?, ?, 0.00, 'unpaid')");
            foreach ($students as $student) {
                $insert_fee_stmt->execute([$student['user_id'], $cat_id, $amount]);
            }
        }

        // Optional: Assuming recordActivity exists, log it. If it doesn't, we can gracefully ignore or mock it.
        if (function_exists('recordActivity')) {
            recordActivity($conn, "FEE_CREATED", "Created fee category: $name ($assigned_class)");
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Fee category created and assigned to ' . count($students) . ' student(s)!']);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'log_payment') {
    $student_id = trim($_POST['student_id'] ?? '');
    $total_amount = floatval($_POST['amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $reference_no = trim($_POST['reference_no'] ?? '');

    if (empty($student_id) || $total_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid student or payment amount.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $fees_stmt = $conn->prepare("SELECT * FROM finance_student_fees WHERE student_id = ? AND status != 'paid' ORDER BY created_at ASC");
        $fees_stmt->execute([$student_id]);
        $unpaid_fees = $fees_stmt->fetchAll(PDO::FETCH_OBJ);

        $remaining_payment = $total_amount;
        $fees_updated = 0;

        foreach ($unpaid_fees as $fee) {
            if ($remaining_payment <= 0) break;

            $balance_due = $fee->amount_due - $fee->amount_paid;
            if ($balance_due <= 0) continue;

            $amount_to_apply = min($balance_due, $remaining_payment);
            $new_paid = $fee->amount_paid + $amount_to_apply;
            $new_status = ($new_paid >= $fee->amount_due) ? 'paid' : 'partial';

            $update_stmt = $conn->prepare("UPDATE finance_student_fees SET amount_paid = ?, status = ? WHERE id = ?");
            $update_stmt->execute([$new_paid, $new_status, $fee->id]);

            $insert_payment = $conn->prepare("INSERT INTO finance_payments (student_fee_id, amount, reference_no, payment_method) VALUES (?, ?, ?, ?)");
            $insert_payment->execute([$fee->id, $amount_to_apply, $reference_no, $payment_method]);

            $remaining_payment -= $amount_to_apply;
            $fees_updated++;
        }

        if ($fees_updated === 0) {
            // They probably don't have any outstanding fees
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Student has no outstanding fees to pay against.']);
            exit;
        }

        if (function_exists('recordActivity')) {
            recordActivity($conn, "PAYMENT_LOGGED", "Logged payment of N".number_format($total_amount)." for student ID: $student_id");
        }

        $conn->commit();

        // TRIGGER: Invoice Receipt Email
        require_once '../../connections/mailer.php';
        $check_config = $conn->query("SELECT school_name, notify_invoice_email FROM school_config LIMIT 1");
        $config = $check_config->fetch(PDO::FETCH_ASSOC);

        if ($config && ($config['notify_invoice_email'] ?? 0) == 1) {
            // Find Student Info
            $stu_stmt = $conn->prepare("SELECT user_id, first_name, surname, id FROM users WHERE user_id = ? LIMIT 1");
            $stu_stmt->execute([$student_id]);
            $student = $stu_stmt->fetch(PDO::FETCH_OBJ);

            if ($student) {
                // Try to find connected guardian email
                $g_stmt = $conn->prepare("SELECT u.user_id as email, u.first_name FROM users u JOIN guardian_wards w ON u.id = w.guardian_id WHERE w.student_id = ?");
                $g_stmt->execute([$student->id]);
                $guardians = $g_stmt->fetchAll(PDO::FETCH_OBJ);

                $recipients = [];
                if (count($guardians) > 0) {
                    foreach ($guardians as $g) {
                        if (filter_var($g->email, FILTER_VALIDATE_EMAIL)) {
                            $recipients[$g->email] = $g->first_name;
                        }
                    }
                } else if (filter_var($student->user_id, FILTER_VALIDATE_EMAIL)) {
                    // Fallback to student email
                    $recipients[$student->user_id] = $student->first_name;
                }

                if (!empty($recipients)) {
                    $school = htmlspecialchars($config['school_name'] ?? 'The School');
                    $amt = number_format($total_amount, 2);
                    $sub = "Payment Receipt - $school";

                    $msgHTML = "
                    <div style='font-family: Arial, sans-serif; background-color: #f6f9fc; padding: 30px;'>
                        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                            <h2 style='color: #16a34a; margin-bottom: 15px'>Payment Receipt</h2>
                            <p style='color: #4b5563; font-size: 16px; line-height: 1.6;'>
                                Hello. This is to confirm that we successfully received a payment of <strong>₦$amt</strong> 
                                towards the account of <strong>" . htmlspecialchars($student->first_name . ' ' . $student->surname) . "</strong> 
                                ($student_id) on " . date('M j, Y') . ".
                            </p>
                            <div style='background-color: #f3f4f6; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                <p style='margin: 0 0 5px 0; color: #4b5563;'><strong>Amount Received:</strong> ₦$amt</p>
                                <p style='margin: 0 0 5px 0; color: #4b5563;'><strong>Method:</strong> " . htmlspecialchars($payment_method) . "</p>
                                <p style='margin: 0; color: #4b5563;'><strong>Ref:</strong> " . htmlspecialchars($reference_no ?: 'N/A') . "</p>
                            </div>
                            <p style='color: #6b7280; font-size: 13px;'>Thank you for your prompt payment.</p>
                        </div>
                    </div>";

                    $msgAlt = "Payment Receipt: We have received N$amt for student $student_id via $payment_method. Thank you!";
                    
                    send_school_email($conn, $recipients, $sub, $msgHTML, $msgAlt);
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Payment logged successfully!']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'send_reminder') {
    $student_id = trim($_POST['student_id'] ?? '');

    if (empty($student_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid student.']);
        exit;
    }

    try {
        // Find total outstanding
        $stmt = $conn->prepare("
            SELECT SUM(amount_due - amount_paid) as outstanding
            FROM finance_student_fees
            WHERE student_id = ? AND status != 'paid'
        ");
        $stmt->execute([$student_id]);
        $outstanding = $stmt->fetchColumn();

        if ($outstanding <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'This student has no outstanding balances.']);
            exit;
        }

        $subject = "Fee Payment Reminder";
        $message = "Please be reminded that you have an outstanding fee balance of ₦" . number_format($outstanding, 2) . ". Kindly make your payment as soon as possible to avoid disruption of your academic activities.";
        $sender = $_SESSION['username'] ?? 'Admin';
        $sender_id = $_SESSION['user_id'] ?? 0;

        $insert = $conn->prepare("
            INSERT INTO broadcast (recipient, subject, message, user_id, username, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
        ");
        $insert->execute([$student_id, $subject, $message, $sender_id, $sender]);

        // --- NEW: Notify Guardians ---
        $guardian_stmt = $conn->prepare("
            SELECT g.user_id 
            FROM users g
            JOIN guardian_wards w ON g.id = w.guardian_id
            WHERE w.student_id = (SELECT id FROM users WHERE user_id = ?)
        ");
        $guardian_stmt->execute([$student_id]);
        $guardians = $guardian_stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($guardians as $g_user_id) {
            $insert->execute([$g_user_id, $subject, $message, $sender_id, $sender]);
        }
        // --- END Notify Guardians ---

        if (function_exists('recordActivity')) {
            recordActivity($conn, "FEE_REMINDER", "Sent fee reminder to student ID: $student_id (and linked guardians)");
        }

        echo json_encode(['status' => 'success', 'message' => 'Reminder sent successfully to the student.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_expense_category') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
        exit;
    }

    try {
        // Check if exists
        $check = $conn->prepare("SELECT id FROM finance_expense_categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category already exists.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO finance_expense_categories (name) VALUES (?)");
        $stmt->execute([$name]);
        
        $cat_id = $conn->lastInsertId();

        if (function_exists('recordActivity')) {
            recordActivity($conn, "EXP_CAT_CREATED", "Created expense category: $name");
        }

        echo json_encode(['status' => 'success', 'message' => 'Category created successfully!', 'data' => ['id' => $cat_id, 'name' => htmlspecialchars($name)]]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_expense') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = trim($_POST['expense_date'] ?? date('Y-m-d'));

    if (empty($title) || $category_id <= 0 || $amount <= 0 || empty($expense_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields correctly.']);
        exit;
    }

    try {
        $academic_session = $_SESSION['active_session'] ?? '';
        $term = $_SESSION['active_term'] ?? '';

        $stmt = $conn->prepare("INSERT INTO finance_expenses (title, description, category_id, amount, expense_date, status, created_by, academic_session, term) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([$title, $description, $category_id, $amount, $expense_date, $_SESSION['user_id'] ?? 0, $academic_session, $term]);

        if (function_exists('recordActivity')) {
            recordActivity($conn, "EXPENSE_CREATED", "Logged new expense: $title");
        }

        echo json_encode(['status' => 'success', 'message' => 'Expense logged and is awaiting approval.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_expense_status') {
    $id = intval($_POST['id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($id <= 0 || !in_array($new_status, ['approved', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters provided.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE finance_expenses SET status = ?, approved_by = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $_SESSION['user_id'] ?? 0, $id]);

        if (function_exists('recordActivity')) {
            recordActivity($conn, "EXPENSE_".strtoupper($new_status), "Marked expense #$id as $new_status");
        }

        echo json_encode(['status' => 'success', 'message' => "Expense successfully $new_status."]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}



echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
?>
