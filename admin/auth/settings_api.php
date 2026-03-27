<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: Insufficient permissions']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'update_config') {
    $school_name = trim($_POST['school_name'] ?? '');
    $school_tagline = trim($_POST['school_tagline'] ?? '');
    $school_address = trim($_POST['school_address'] ?? '');
    $school_phone_number = trim($_POST['school_phone_number'] ?? '');
    $school_phone_number_2 = trim($_POST['school_phone_number_2'] ?? '');
    $school_email = trim($_POST['school_email'] ?? '');
    $academic_session = trim($_POST['academic_session'] ?? '');
    $active_term = trim($_POST['active_term'] ?? '');
    
    $school_primary = trim($_POST['school_primary'] ?? '#0084D1');
    $school_secondary = trim($_POST['school_secondary'] ?? '#0084D1');
    $account_details = trim($_POST['account_details'] ?? '');
    
    // new fields
    $maintenance_mode = intval($_POST['maintenance_mode'] ?? 0);
    $pwa_theme_color = trim($_POST['pwa_theme_color'] ?? '#ffffff');
    $pwa_status_bar_color = trim($_POST['pwa_status_bar_color'] ?? '#ffffff');
    $pwa_bg_color = trim($_POST['pwa_bg_color'] ?? '#ffffff');
    $pwa_display = trim($_POST['pwa_display'] ?? 'standalone');
    
    // SMS Configuration
    $sms_from = trim($_POST['sms_from'] ?? '');
    $sms_provider = trim($_POST['sms_provider'] ?? '');
    $sms_api_key = trim($_POST['sms_api_key'] ?? '');
    $sms_api_secret = trim($_POST['sms_api_secret'] ?? '');
    $mail_from_address = trim($_POST['mail_from_address'] ?? '');
    $mail_from_name = trim($_POST['mail_from_name'] ?? '');
    $mail_host = trim($_POST['mail_host'] ?? '');
    $mail_port = trim($_POST['mail_port'] ?? '');
    $mail_username = trim($_POST['mail_username'] ?? '');
    // Ignore mail_password if it wasn't submitted (because we disabled the field in JS if unchanged)
    $mail_password = isset($_POST['mail_password']) ? trim($_POST['mail_password']) : null;
    $mail_encryption = trim($_POST['mail_encryption'] ?? '');
    
    $notify_welcome_sms = intval($_POST['notify_welcome_sms'] ?? 0);
    $notify_welcome_email = intval($_POST['notify_welcome_email'] ?? 0);
    $notify_new_fee_admin = intval($_POST['notify_new_fee_admin'] ?? 0);
    $notify_invoice_email = intval($_POST['notify_invoice_email'] ?? 0);
    
    $newsletter_enabled = intval($_POST['newsletter_enabled'] ?? 0);
    $mailchimp_api_key = trim($_POST['mailchimp_api_key'] ?? '');
    $mailchimp_list_id = trim($_POST['mailchimp_list_id'] ?? '');
    
    // AI Integration
    $groq_api_key = isset($_POST['groq_api_key']) ? trim($_POST['groq_api_key']) : null;

    if (empty($school_name)) {
        echo json_encode(['status' => 'error', 'message' => 'School Name is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Ensure exactly one config row
        $check = $conn->query("SELECT * FROM school_config LIMIT 1");
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        $logo_path = $existing['school_logo'] ?? '';
        $logo_small_path = $existing['school_logo_small'] ?? '';
        $pwa_icon_path = $existing['pwa_icon'] ?? '';
        $signature_path = $existing['signature'] ?? '';

        // Prevent password overwrite with empty if they didn't supply one and it wasn't passed as empty
        if ($mail_password === null && $existing) {
            $mail_password = $existing['mail_password'];
        }
        
        if ($groq_api_key === null && $existing) {
            $groq_api_key = $existing['groq_api_key'] ?? '';
        }

        // Handle File Uploads
        $upload_dir = '../../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_dir . $filename)) {
                $logo_path = 'uploads/' . $filename;
            }
        }
        
        if (isset($_FILES['school_logo_small']) && $_FILES['school_logo_small']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['school_logo_small']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_sm_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['school_logo_small']['tmp_name'], $upload_dir . $filename)) {
                $logo_small_path = 'uploads/' . $filename;
            }
        }

        if (isset($_FILES['pwa_icon']) && $_FILES['pwa_icon']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['pwa_icon']['name'], PATHINFO_EXTENSION);
            $filename = 'pwa_icon_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['pwa_icon']['tmp_name'], $upload_dir . $filename)) {
                $pwa_icon_path = 'uploads/' . $filename;
            }
        }

        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
            $filename = 'signature_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['signature']['tmp_name'], $upload_dir . $filename)) {
                $signature_path = 'uploads/' . $filename;
            }
        }

        if (!$existing) {
            $stmt = $conn->prepare("INSERT INTO school_config (
                school_name, school_tagline, school_address, school_phone_number, school_phone_number_2, school_email, 
                account_details, academic_session, active_term, maintenance_mode,
                school_logo, school_logo_small, pwa_icon, signature,
                school_primary, school_secondary, pwa_theme_color, pwa_status_bar_color, pwa_bg_color, pwa_display,
                sms_from, sms_provider, sms_api_key, sms_api_secret, 
                mail_from_address, mail_from_name, mail_host, mail_port, mail_username, mail_password, mail_encryption,
                notify_welcome_sms, notify_welcome_email, notify_new_fee_admin, notify_invoice_email,
                newsletter_enabled, mailchimp_api_key, mailchimp_list_id,
                groq_api_key
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $school_name, $school_tagline, $school_address, $school_phone_number, $school_phone_number_2, $school_email,
                $account_details, $academic_session, $active_term, $maintenance_mode,
                $logo_path, $logo_small_path, $pwa_icon_path, $signature_path,
                $school_primary, $school_secondary, $pwa_theme_color, $pwa_status_bar_color, $pwa_bg_color, $pwa_display,
                $sms_from, $sms_provider, $sms_api_key, $sms_api_secret,
                $mail_from_address, $mail_from_name, $mail_host, $mail_port, $mail_username, $mail_password, $mail_encryption,
                $notify_welcome_sms, $notify_welcome_email, $notify_new_fee_admin, $notify_invoice_email,
                $newsletter_enabled, $mailchimp_api_key, $mailchimp_list_id,
                $groq_api_key
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE school_config SET 
                school_name=?, school_tagline=?, school_address=?, school_phone_number=?, school_phone_number_2=?, school_email=?, 
                account_details=?, academic_session=?, active_term=?, maintenance_mode=?,
                school_logo=?, school_logo_small=?, pwa_icon=?, signature=?,
                school_primary=?, school_secondary=?, pwa_theme_color=?, pwa_status_bar_color=?, pwa_bg_color=?, pwa_display=?,
                sms_from=?, sms_provider=?, sms_api_key=?, sms_api_secret=?, 
                mail_from_address=?, mail_from_name=?, mail_host=?, mail_port=?, mail_username=?, mail_password=?, mail_encryption=?,
                notify_welcome_sms=?, notify_welcome_email=?, notify_new_fee_admin=?, notify_invoice_email=?,
                newsletter_enabled=?, mailchimp_api_key=?, mailchimp_list_id=?,
                groq_api_key=?
                WHERE id=?");
            $stmt->execute([
                $school_name, $school_tagline, $school_address, $school_phone_number, $school_phone_number_2, $school_email,
                $account_details, $academic_session, $active_term, $maintenance_mode,
                $logo_path, $logo_small_path, $pwa_icon_path, $signature_path,
                $school_primary, $school_secondary, $pwa_theme_color, $pwa_status_bar_color, $pwa_bg_color, $pwa_display,
                $sms_from, $sms_provider, $sms_api_key, $sms_api_secret,
                $mail_from_address, $mail_from_name, $mail_host, $mail_port, $mail_username, $mail_password, $mail_encryption,
                $notify_welcome_sms, $notify_welcome_email, $notify_new_fee_admin, $notify_invoice_email,
                $newsletter_enabled, $mailchimp_api_key, $mailchimp_list_id,
                $groq_api_key,
                $existing['id']
            ]);
        }

        $_SESSION['active_session'] = $academic_session;
        $_SESSION['active_term'] = $active_term;

        if (function_exists('recordActivity')) {
            recordActivity($conn, "SETTINGS_UPDATED", "Updated global system settings and configuration parameters.");
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Sys settings successfully updated!']);

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid API Action']);
