<?php
// connections/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pre-load composer autoload assuming it's in the root
require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Sends an email using the global school_config SMTP settings
 *
 * @param PDO $conn Active database connection
 * @param string|array $to Single email, or associative array ['email@example.com' => 'Name']
 * @param string $subject The email subject
 * @param string $htmlBody The HTML content
 * @param string $altBody The plain-text alternative (optional)
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function send_school_email($conn, $to, $subject, $htmlBody, $altBody = '') {
    // 1. Fetch SMTP settings
    $stmt = $conn->query("SELECT school_name, mail_from_address, mail_from_name, mail_host, mail_port, mail_username, mail_password, mail_encryption FROM school_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['mail_host'])) {
        return ['status' => 'error', 'message' => 'SMTP Configuration is missing. Please update email settings.'];
    }

    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['mail_host'];
        $mail->SMTPAuth   = !empty($config['mail_username']);
        $mail->Username   = $config['mail_username'];
        $mail->Password   = $config['mail_password'];
        $mail->Port       = $config['mail_port'];

        // Encryption
        $encryption = strtolower($config['mail_encryption']);
        if ($encryption == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        // Recipients
        $fromEmail = $config['mail_from_address'] ?: 'no-reply@schoolapp.com';
        $fromName  = $config['mail_from_name']    ?: $config['school_name'];
        $mail->setFrom($fromEmail, $fromName);

        if (is_array($to)) {
            foreach ($to as $email => $name) {
                if (is_int($email)) {
                    $mail->addAddress($name); // indexed array
                } else {
                    $mail->addAddress($email, $name); // assoc array
                }
            }
        } else {
            $mail->addAddress($to);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags(str_replace(['<br>', '<br/>', '<p>'], ["\n", "\n", "\n\n"], $htmlBody));

        $mail->send();
        return ['status' => 'success', 'message' => 'Message has been sent'];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}
