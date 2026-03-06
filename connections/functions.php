<?php

/**
 * Record a system activity log.
 *
 * @param PDO    $conn     The database connection.
 * @param string $action   The category of the action (e.g., 'LOGIN', 'BLOG_POST').
 * @param string $details  A human-readable description of the activity.
 * @param string $severity 'info', 'warning', or 'critical'.
 * @return bool            True on success, false on failure.
 */
function recordActivity($conn, $action, $details, $severity = 'info') {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, severity, ip_address)
            VALUES (:user_id, :action, :details, :severity, :ip)
        ");
        
        return $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action'  => strtoupper($action),
            ':details' => $details,
            ':severity' => $severity,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}
?>
