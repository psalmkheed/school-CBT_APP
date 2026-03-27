<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

// ROUTES
if ($action === 'create_route') {
    $route_name = trim($_POST['route_name'] ?? '');
    $stops = trim($_POST['stops'] ?? '');
    $fare = floatval($_POST['fare'] ?? 0);
    
    if (empty($route_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Route name is required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO transport_routes (route_name, stops, fare) VALUES (?, ?, ?)");
    if ($stmt->execute([$route_name, $stops, $fare])) {
        echo json_encode(['status' => 'success', 'message' => 'Route created successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'delete_route') {
    $id = $_POST['id'] ?? 0;
    // check if used by vehicles
    $check = $conn->prepare("SELECT COUNT(*) FROM transport_vehicles WHERE route_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete route. Vehicles are assigned to it.']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM transport_routes WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Route deleted successfully']);
    }
    exit;
}

// VEHICLES
if ($action === 'create_vehicle') {
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $driver_phone = trim($_POST['driver_phone'] ?? '');
    $route_id = intval($_POST['route_id'] ?? 0);
    $capacity = intval($_POST['capacity'] ?? 0);
    
    if (empty($vehicle_number) || empty($driver_name) || empty($route_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle number, driver, and route are required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO transport_vehicles (vehicle_number, driver_name, driver_phone, route_id, capacity) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$vehicle_number, $driver_name, $driver_phone, $route_id, $capacity])) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle added successfully']);
    }
    exit;
}

if ($action === 'delete_vehicle') {
    $id = $_POST['id'] ?? 0;
    
    // check allocations
    $check = $conn->prepare("SELECT COUNT(*) FROM transport_allocations WHERE vehicle_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete vehicle. Students are assigned to it.']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM transport_vehicles WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle deleted successfully']);
    }
    exit;
}

// ALLOCATIONS
if ($action === 'assign_student') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $pickup_point = trim($_POST['pickup_point'] ?? '');
    
    if (empty($student_id) || empty($vehicle_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Student and Vehicle are required']);
        exit;
    }
    
    // Check if already assigned
    $check = $conn->prepare("SELECT id FROM transport_allocations WHERE student_id = ?");
    $check->execute([$student_id]);
    if ($check->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student is already assigned to a transport vehicle.']);
        exit;
    }
    
    // Check capacity
    $capacity_check = $conn->prepare("SELECT v.capacity, COUNT(a.id) as assigned FROM transport_vehicles v LEFT JOIN transport_allocations a ON v.id = a.vehicle_id WHERE v.id = ? GROUP BY v.id");
    $capacity_check->execute([$vehicle_id]);
    $v_data = $capacity_check->fetch(PDO::FETCH_ASSOC);
    
    if ($v_data && $v_data['assigned'] >= $v_data['capacity'] && $v_data['capacity'] > 0) {
         echo json_encode(['status' => 'error', 'message' => 'Vehicle is at full capacity!']);
         exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO transport_allocations (student_id, vehicle_id, pickup_point) VALUES (?, ?, ?)");
    if ($stmt->execute([$student_id, $vehicle_id, $pickup_point])) {
        echo json_encode(['status' => 'success', 'message' => 'Student assigned to vehicle successfully']);
    }
    exit;
}

if ($action === 'delete_allocation') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM transport_allocations WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Allocation removed successfully']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
