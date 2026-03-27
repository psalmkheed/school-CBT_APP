<?php
require 'connections/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS edu_app.staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Present',
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (staff_id, attendance_date)
);

CREATE TABLE IF NOT EXISTS edu_app.hr_payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    month VARCHAR(2) NOT NULL,
    year VARCHAR(4) NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deductions DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    payment_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (staff_id, month, year)
);
";

try {
    $conn->exec($sql);
    echo "Tables created successfully.\n";
} catch(PDOException $e) {
    echo "Table creation error: " . $e->getMessage() . "\n";
}

$columns = [
    'basic_salary' => 'DECIMAL(10,2) DEFAULT 0.00',
    'bank_name' => 'VARCHAR(100) NULL',
    'account_number' => 'VARCHAR(50) NULL',
    'account_name' => 'VARCHAR(150) NULL'
];

foreach ($columns as $col => $def) {
    try {
        $conn->exec("ALTER TABLE edu_app.users ADD COLUMN $col $def");
        echo "Added column $col.\n";
    } catch(PDOException $e) {
        // Ignore if column exists
        echo "Column $col might already exist or error: " . $e->getMessage() . "\n";
    }
}
?>
