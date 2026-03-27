<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_staff_directory') {
    $stmt = $conn->query("SELECT id, user_id, first_name, surname, profile_photo, created_at, 
                                 basic_salary, bank_name, account_number, account_name, status 
                          FROM users 
                          WHERE role = 'staff' 
                          ORDER BY first_name ASC");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $staff]);
    exit;
}

if ($action === 'update_staff_finance') {
    $staff_id = $_POST['staff_id'] ?? '';
    $basic_salary = $_POST['basic_salary'] ?? 0;
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');

    if (empty($staff_id)) {
         echo json_encode(['status' => 'error', 'message' => 'Staff ID required']);
         exit;
    }

    $stmt = $conn->prepare("UPDATE users SET basic_salary = ?, bank_name = ?, account_number = ?, account_name = ? WHERE id = ? AND role = 'staff'");
    if ($stmt->execute([$basic_salary, $bank_name, $account_number, $account_name, $staff_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Staff financial details updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
    exit;
}

if ($action === 'save_attendance') {
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $attendance_data = $_POST['attendance'] ?? []; // format: ['staff_id' => ['status' => 'Present', 'remarks' => '']]

    if (empty($attendance_data)) {
        echo json_encode(['status' => 'error', 'message' => 'No attendance data provided']);
        exit;
    }

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO staff_attendance (staff_id, attendance_date, status, remarks) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)");
        foreach ($attendance_data as $staff_id => $data) {
            $stmt->execute([$staff_id, $date, $data['status'], $data['remarks'] ?? '']);
        }
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Attendance logged successfully']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save attendance: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_attendance') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Get all staff + their attendance for this date (if any)
    $stmt = $conn->prepare("
        SELECT u.id as staff_id, u.first_name, u.surname, u.user_id,
               COALESCE(a.status, 'Present') as status, a.remarks
        FROM users u
        LEFT JOIN staff_attendance a ON u.id = a.staff_id AND a.attendance_date = ?
        WHERE u.role = 'staff' AND u.status = 1
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$date]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $records]);
    exit;
}

if ($action === 'get_attendance_report') {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');

    $stmt = $conn->prepare("
        SELECT u.id as staff_id, u.first_name, u.surname, u.user_id,
               COUNT(a.id) as total_days_logged,
               SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
               SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
               SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days,
               SUM(CASE WHEN a.status = 'Half Day' THEN 1 ELSE 0 END) as half_days
        FROM users u
        LEFT JOIN staff_attendance a ON u.id = a.staff_id AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
        WHERE u.role = 'staff' AND u.status = 1
        GROUP BY u.id
        ORDER BY present_days DESC
    ");
    $stmt->execute([$month, $year]);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'generate_payroll') {
    $month = $_POST['month'] ?? date('m');
    $year = $_POST['year'] ?? date('Y');

    // Fetch all active staff
    $staff_stmt = $conn->query("SELECT id, basic_salary FROM users WHERE role = 'staff' AND status = 1");
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO hr_payroll (staff_id, month, year, basic_salary, net_salary) VALUES (?, ?, ?, ?, ?)");
        
        foreach($staff_list as $st) {
            $base = floatval($st['basic_salary']);
            $stmt->execute([$st['id'], $month, $year, $base, $base]);
        }
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payroll generated for active staff']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate payroll: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_payroll_records') {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');

    $stmt = $conn->prepare("
        SELECT p.*, u.first_name, u.surname, u.user_id, u.bank_name, u.account_number, u.account_name
        FROM hr_payroll p
        JOIN users u ON p.staff_id = u.id
        WHERE p.month = ? AND p.year = ?
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$month, $year]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $records]);
    exit;
}

if ($action === 'update_payroll_record') {
    $record_id = $_POST['record_id'];
    $allowances = floatval($_POST['allowances'] ?? 0);
    $deductions = floatval($_POST['deductions'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    
    // Get basic
    $chk = $conn->prepare("SELECT basic_salary FROM hr_payroll WHERE id = ?");
    $chk->execute([$record_id]);
    $base = (float)$chk->fetchColumn();

    $net = ($base + $allowances) - $deductions;
    $payment_date = ($status === 'Paid') ? date('Y-m-d') : null;

    $stmt = $conn->prepare("UPDATE hr_payroll SET allowances=?, deductions=?, net_salary=?, status=?, payment_date=? WHERE id=?");
    if ($stmt->execute([$allowances, $deductions, $net, $status, $payment_date, $record_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Record updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
    exit;
}

if ($action === 'mark_all_paid') {
    $month = $_POST['month'] ?? date('m');
    $year = $_POST['year'] ?? date('Y');
    
    $stmt = $conn->prepare("UPDATE hr_payroll SET status = 'Paid', payment_date = CURRENT_DATE WHERE month = ? AND year = ? AND status != 'Paid'");
    if($stmt->execute([$month, $year])) {
        echo json_encode(['status' => 'success', 'message' => 'All pending records marked as Paid']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update records']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
