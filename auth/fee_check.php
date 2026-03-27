<?php
/**
 * Fee Integrity Check Utility
 * Verifies if a student has cleared their academic obligations.
 */

function isFeeCleared($conn, $userId) {
    // If not a student, they are always "cleared" for administrative tools
    $stmt = $conn->prepare("SELECT role, user_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$user || $user->role !== 'student') return true;

    // Check for any unpaid or partial fees for this student
    // We consider "cleared" only if all assigned fees are 'paid'
    $fee_stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM finance_student_fees 
        WHERE student_id = ? AND status != 'paid'
    ");
    $fee_stmt->execute([$user->user_id]);
    $outstanding = $fee_stmt->fetchColumn();

    return $outstanding == 0;
}

function getOutstandingFees($conn, $userId) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $uid = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT sf.*, fc.name 
        FROM finance_student_fees sf
        JOIN finance_fee_categories fc ON sf.fee_category_id = fc.id
        WHERE sf.student_id = ? AND sf.status != 'paid'
    ");
    $stmt->execute([$uid]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}
