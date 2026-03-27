<?php
require 'connections/db.php';
$cols = [
    'maintenance_mode' => 'TINYINT(1) DEFAULT 0',
    'school_phone_number_2' => 'VARCHAR(50) NULL',
    'school_logo_small' => 'VARCHAR(255) NULL',
    'pwa_icon' => 'VARCHAR(255) NULL',
    'pwa_theme_color' => 'VARCHAR(50) DEFAULT "#ffffff"',
    'pwa_status_bar_color' => 'VARCHAR(50) DEFAULT "#ffffff"',
    'pwa_bg_color' => 'VARCHAR(50) DEFAULT "#ffffff"',
    'pwa_display' => 'VARCHAR(50) DEFAULT "standalone"',
    'sms_from' => 'VARCHAR(50) NULL',
    'mail_from_address' => 'VARCHAR(150) NULL',
    'mail_from_name' => 'VARCHAR(100) NULL',
    'mail_host' => 'VARCHAR(100) NULL',
    'mail_port' => 'VARCHAR(10) NULL',
    'mail_username' => 'VARCHAR(100) NULL',
    'mail_password' => 'VARCHAR(255) NULL',
    'mail_encryption' => 'VARCHAR(20) NULL',
    'notify_welcome_sms' => 'TINYINT(1) DEFAULT 0',
    'notify_welcome_email' => 'TINYINT(1) DEFAULT 0',
    'notify_new_fee_admin' => 'TINYINT(1) DEFAULT 0',
    'notify_invoice_email' => 'TINYINT(1) DEFAULT 0',
    'newsletter_enabled' => 'TINYINT(1) DEFAULT 0',
    'mailchimp_api_key' => 'VARCHAR(255) NULL',
    'mailchimp_list_id' => 'VARCHAR(255) NULL',
    'sms_provider' => 'VARCHAR(50) NULL',
    'sms_api_key' => 'VARCHAR(255) NULL',
    'sms_api_secret' => 'VARCHAR(255) NULL'
];

foreach ($cols as $col => $def) {
    try {
        $conn->exec("ALTER TABLE school_config ADD COLUMN $col $def");
        echo "Added $col\n";
    } catch(Exception $e) {
        echo "Could not add $col: " . $e->getMessage() . "\n";
    }
}
