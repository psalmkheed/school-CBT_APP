<?php
require 'connections/db.php';

try {
    // GAMIFICATION TABLES
    $conn->exec("CREATE TABLE IF NOT EXISTS student_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        points INT DEFAULT 0,
        activity VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(100) NOT NULL,
        points_required INT DEFAULT 0
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS student_badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        badge_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
    )");

    // Insert Default Badges
    $check_badges = $conn->query("SELECT COUNT(*) FROM badges")->fetchColumn();
    if ($check_badges == 0) {
        $conn->exec("INSERT INTO badges (name, description, icon, points_required) VALUES 
            ('Rookie Scholar', 'Earned your first 100 points!', 'bx-star', 100),
            ('Consistent Learner', 'Reached 500 points across activities.', 'bx-medal', 500),
            ('Academic Pro', 'Reached the 1000 points milestone!', 'bx-trophy', 1000),
            ('Exam Master', 'Scored high in multiple exams gaining 2000 points.', 'bx-crown', 2000)
        ");
    }

    // PARENT PORTAL UPDATES
    // Need a mapping between parent and student, or just add parent_id to users.
    // Easiest is to add parent_id to users if not exists, but a parent is just another user with role 'parent'
    
    // Check if parent_id exists in users
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'parent_code'");
    if ($result->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN parent_code VARCHAR(50) NULL");
    }
    
    echo "Gamification & Parents Portal tables setup complete!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
