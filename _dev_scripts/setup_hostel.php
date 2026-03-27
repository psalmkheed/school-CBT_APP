<?php
require 'connections/db.php';

try {
    // 1. Hostels table
    $conn->exec("CREATE TABLE IF NOT EXISTS hostels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type ENUM('Male', 'Female', 'Mixed') DEFAULT 'Mixed',
        capacity INT DEFAULT 0,
        supervisor_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Rooms table
    $conn->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hostel_id INT NOT NULL,
        room_number VARCHAR(50) NOT NULL,
        capacity INT DEFAULT 4,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
    )");

    // 3. Bed Allocations table
    $conn->exec("CREATE TABLE IF NOT EXISTS bed_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        student_id INT NOT NULL,
        allocated_by INT NULL,
        allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'vacated') DEFAULT 'active',
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo "Hostel tables created successfully!\n";

    // Insert some dummy hostels if none exist
    $chk = $conn->query("SELECT COUNT(*) FROM hostels")->fetchColumn();
    if ($chk == 0) {
        $conn->exec("INSERT INTO hostels (name, type, capacity) VALUES ('Boys Quarters A', 'Male', 100), ('Girls Block B', 'Female', 100)");
        
        $stmt_boys=$conn->query("SELECT id FROM hostels WHERE name='Boys Quarters A'");
        $boys_id=$stmt_boys->fetchColumn();
        
        $stmt_girls=$conn->query("SELECT id FROM hostels WHERE name='Girls Block B'");
        $girls_id=$stmt_girls->fetchColumn();

        // Add rooms
        for($i=1; $i<=5; $i++) {
            $conn->exec("INSERT INTO rooms (hostel_id, room_number, capacity) VALUES ({$boys_id}, 'A10{$i}', 4)");
        }
        for($i=1; $i<=5; $i++) {
            $conn->exec("INSERT INTO rooms (hostel_id, room_number, capacity) VALUES ({$girls_id}, 'B20{$i}', 4)");
        }
        echo "Dummy data seeded successfully.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
