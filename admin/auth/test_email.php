<?php
require '../../auth/check.php';
require '../../connections/mailer.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: Insufficient permissions']);
    exit;
}

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$test_email = filter_var($_POST['test_email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address provided for testing.']);
    exit;
}

// The connection `$conn` is defined in `auth/check.php` -> `connections/db.php`

$subject = 'SMTP Test - School Application Portal';
$bodyHTML = "
<div style='font-family: Arial, sans-serif; background-color: #f6f9fc; padding: 30px;'>
    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center'>
        <h2 style='color: #4f46e5; margin-bottom: 10px'>SMTP Configuration Successful!</h2>
        <p style='color: #4b5563; font-size: 16px; line-height: 1.5; margin-top: 5px'>
            If you are reading this email, it means your school portal's SMTP server is properly configured and can successfully deliver communications to parents, students, and staff!
        </p>
        <div style='margin-top: 30px; font-size: 12px; color: #9ca3af;'>
            This is an automated test message initiated from the Admin Dashboard.
        </div>
    </div>
</div>
";
$altBody = "SMTP Configuration Successful! If you are reading this email, it means your school portal's SMTP server is properly configured and can successfully deliver communications.";

$result = send_school_email($conn, $test_email, $subject, $bodyHTML, $altBody);

if ($result['status'] === 'success') {
    if (function_exists('recordActivity')) {
        recordActivity($conn, "SMTP_TEST", "Successfully sent a test email to: " . $test_email);
    }
} else {
    if (function_exists('recordActivity')) {
        recordActivity($conn, "SMTP_FAIL", "Failed to send test email to: " . $test_email . ". Details: " . $result['message'], 'error');
    }
}

echo json_encode($result);
exit;
