<?php
include("db.php");
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['type'], $data['name'], $data['details'], $data['schedules']) || !is_array($data['schedules'])) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit;
}

$type = $data['type'];
$name = $data['name'];
$details = $data['details'];
$schedules = $data['schedules'];

$con->begin_transaction();

try {
    $newId = 0;
    if ($type === 'doctor') {
        $stmt = $con->prepare("INSERT INTO doctor (doctor_name, specialization) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $details);
        $stmt->execute();
        $newId = $con->insert_id;
        
        $stmt_avail = $con->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        foreach ($schedules as $s) {
            $stmt_avail->bind_param("iiss", $newId, $s['day'], $s['start_time'], $s['end_time']);
            $stmt_avail->execute();
        }

    } elseif ($type === 'staff') {
        $stmt = $con->prepare("INSERT INTO staff (full_name, position) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $details);
        $stmt->execute();
        $newId = $con->insert_id;

        $stmt_avail = $con->prepare("INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        foreach ($schedules as $s) {
            
            $stmt_avail->bind_param("iiss", $newId, $s['day'], $s['start_time'], $s['end_time']);
            $stmt_avail->execute();
        }

    } else {
        throw new Exception("Invalid person type.");
    }
    
    if ($newId == 0) throw new Exception("Failed to insert person record.");

    $con->commit();
    echo json_encode(["success" => true, "message" => ucfirst($type) . " added successfully."]);

} catch (Exception $e) {
    $con->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$con->close();
?>