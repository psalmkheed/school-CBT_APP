<?php
require 'connections/db.php';

$sql = "
-- 1. Transport Management
CREATE TABLE IF NOT EXISTS `transport_routes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `route_name` varchar(255) NOT NULL,
    `stops` text,
    `fare` decimal(10,2) DEFAULT 0.00,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transport_vehicles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `vehicle_number` varchar(50) NOT NULL,
    `driver_name` varchar(100) NOT NULL,
    `driver_phone` varchar(50) NOT NULL,
    `route_id` int(11) DEFAULT NULL,
    `capacity` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transport_allocations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `vehicle_id` int(11) NOT NULL,
    `pickup_point` varchar(255) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Physical Library Management
CREATE TABLE IF NOT EXISTS `physical_books` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `author` varchar(255) NOT NULL,
    `category` varchar(100) NOT NULL,
    `isbn` varchar(50) DEFAULT NULL,
    `quantity` int(11) DEFAULT 1,
    `issued_qty` int(11) DEFAULT 0,
    `status` varchar(50) DEFAULT 'available',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `physical_book_issues` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `book_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `user_type` varchar(50) NOT NULL, -- student, staff
    `issue_date` date NOT NULL,
    `due_date` date NOT NULL,
    `return_date` date DEFAULT NULL,
    `status` varchar(50) DEFAULT 'issued', -- issued, returned, lost
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Inventory Management
CREATE TABLE IF NOT EXISTS `inventory_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `item_name` varchar(255) NOT NULL,
    `category` varchar(100) NOT NULL,
    `stock_quantity` int(11) DEFAULT 0,
    `location` varchar(100) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_issues` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `item_id` int(11) NOT NULL,
    `issued_to_user_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `issue_date` date NOT NULL,
    `return_date` date DEFAULT NULL,
    `status` varchar(50) DEFAULT 'issued',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Certificate & ID Card Engine Templates
CREATE TABLE IF NOT EXISTS `document_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(100) NOT NULL,
    `type` varchar(50) NOT NULL, -- id_card, certificate
    `html_content` longtext NOT NULL,
    `bg_image` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Student Promotion History (Optional but good for tracking)
CREATE TABLE IF NOT EXISTS `promotion_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `from_class` varchar(100) NOT NULL,
    `to_class` varchar(100) NOT NULL,
    `promoted_by` int(11) NOT NULL, -- admin/staff
    `promotion_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->exec($sql);
    echo "New module tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
