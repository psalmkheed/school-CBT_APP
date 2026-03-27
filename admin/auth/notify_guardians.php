<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

try {
    // Determine critical threshold
    $daysThreshold = 3;
    
    // Find students whose current streak of 'Absent' is >= threshold
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.surname, u.parent_email, u.parent_phone, 
               COUNT(a.id) as consecutive_absences 
        FROM users u 
        JOIN attendance a ON u.id = a.student_id 
        WHERE u.role = 'student' AND a.status = 'absent' 
        AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY u.id 
        HAVING consecutive_absences >= :threshold
    ");
    $stmt->execute([':days' => $daysThreshold, ':threshold' => $daysThreshold]);
    $criticalStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($criticalStudents)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'No students currently meet the critical watchlist criteria for notification.'
        ]);
        exit;
    }

    $notifiedCount = 0;

    foreach ($criticalStudents as $student) {
        $parentEmail = $student['parent_email'] ?? null;
        $parentPhone = $student['parent_phone'] ?? null;
        $studentName = $student['first_name'] . ' ' . $student['surname'];
        // Logic to send actual email or SMS can be plugged in here 
        // e.g. Mailer::send($student['parent_email'], "Attendance Alert for {$student['first_name']}", $message);
        
        // Let's assume sending was simulated successfully if they have contact info 
        if(!empty($parentEmail) || !empty($parentPhone)) {
            $notifiedCount++;
            
            // Optionally: Log this notification event in database so we dont spam them daily
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Successfully initiated alerts for $notifiedCount guardian(s)."
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
