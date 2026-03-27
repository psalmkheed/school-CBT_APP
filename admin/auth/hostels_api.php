<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Depending on how form is serialized, sometimes action is implicit
// If allocateForm was submitted, there's no action field, it has student_id and room_id.
$student_id = $_POST['student_id'] ?? '';
$room_id = $_POST['room_id'] ?? '';

if ($student_id && $room_id) {
    // This is a bed allocation request
    try {
        // Validate room capacity
        $chk = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM bed_allocations WHERE room_id = :rid AND status = 'active') as taken FROM rooms WHERE id = :rid2");
        $chk->execute([':rid' => $room_id, ':rid2' => $room_id]);
        $room = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            echo json_encode(['status' => 'error', 'message' => 'Room not found.']);
            exit;
        }

        if ($room['taken'] >= $room['capacity']) {
            echo json_encode(['status' => 'error', 'message' => 'Room is fully occupied.']);
            exit;
        }

        // Insert Allocation
        $stmt = $conn->prepare("INSERT INTO bed_allocations (room_id, student_id, allocated_by) VALUES (?, ?, ?)");
        $stmt->execute([$room_id, $student_id, $user->id]);

        echo json_encode(['status' => 'success', 'message' => 'Student successfully allocated to bed space.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create_hostel') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'Mixed';

    if(empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Hostel name is required.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO hostels (name, type) VALUES (?, ?)");
        $stmt->execute([$name, $type]);
        echo json_encode(['status' => 'success', 'message' => 'Hostel added successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_room') {
    $hostel_id = $_POST['hostel_id'] ?? '';
    $room_num = trim($_POST['room_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 4);

    if (empty($hostel_id) || empty($room_num)) {
        echo json_encode(['status' => 'error', 'message' => 'Hostel ID and Room Number are required.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO rooms (hostel_id, room_number, capacity) VALUES (?, ?, ?)");
        $stmt->execute([$hostel_id, $room_num, $capacity]);
        echo json_encode(['status' => 'success', 'message' => "Room $room_num added."]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Number might already exist.']);
    }
    exit;
}

if ($action === 'revoke_bed') {
    $id = $_POST['id'] ?? '';
    if(empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Allocation ID missing.']);
        exit;
    }

    try {
        // We set status to vacated instead of deleting fully for record keeping
        $stmt = $conn->prepare("UPDATE bed_allocations SET status = 'vacated' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'message' => 'Student removed from room.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
