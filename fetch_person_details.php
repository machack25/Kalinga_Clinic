<?php
include("db.php");
header('Content-Type: application/json');

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Missing required parameters."]);
    exit;
}

$type = $_GET['type'];
$id = (int)$_GET['id'];
$response = ["success" => false];

try {
    if ($type === 'doctor') {
        $stmt = $con->prepare("SELECT doctor_id as id, doctor_name as name, specialization as details FROM doctor WHERE doctor_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $person_result = $stmt->get_result();
        $person = $person_result->fetch_assoc();

        if ($person) {
            $sched_stmt = $con->prepare("SELECT availability_id, day_of_week, start_time, end_time FROM doctor_availability WHERE doctor_id = ? ORDER BY day_of_week, start_time");
            $sched_stmt->bind_param("i", $id);
            $sched_stmt->execute();
            $schedules_result = $sched_stmt->get_result();
            $person['schedules'] = $schedules_result->fetch_all(MYSQLI_ASSOC);
            $response = ["success" => true, "data" => $person];
        }
    } elseif ($type === 'staff') {
        $stmt = $con->prepare("SELECT id, full_name as name, position as details FROM staff WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $person_result = $stmt->get_result();
        $person = $person_result->fetch_assoc();
        
        if ($person) {
            
            $sched_stmt = $con->prepare("
                SELECT 
                    id AS availability_id, 
                    day_of_week, 
                    start_time, 
                    end_time 
                FROM staff_availability 
                WHERE staff_id = ? 
                ORDER BY day_of_week, start_time
            ");
            $sched_stmt->bind_param("i", $id);
            $sched_stmt->execute();
            $schedules_result = $sched_stmt->get_result();
            $person['schedules'] = $schedules_result->fetch_all(MYSQLI_ASSOC);
            $response = ["success" => true, "data" => $person];
        }
    } else {
        $response["message"] = "Invalid type.";
    }

} catch (Exception $e) {
    $response["message"] = "Server Error: " . $e->getMessage();
}

echo json_encode($response);
$con->close();
?>