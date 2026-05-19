<?php
include("db.php");
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['type'], $data['id'], $data['name'], $data['details'])) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$name = $data['name'];
$details = $data['details'];
$updatedSchedules = $data['updatedSchedules'] ?? [];
$newSchedules = $data['newSchedules'] ?? [];
$deletedScheduleIds = $data['deletedScheduleIds'] ?? [];

$con->begin_transaction();

try {
    if ($type === 'doctor') {
        $mainTable = 'doctor'; $mainIdCol = 'doctor_id'; $nameCol = 'doctor_name';
        $detailsCol = 'specialization'; $availTable = 'doctor_availability';
        $availIdCol = 'doctor_id'; $availPK = 'availability_id';
    } elseif ($type === 'staff') {
        $mainTable = 'staff'; $mainIdCol = 'id'; $nameCol = 'full_name';
        $detailsCol = 'position'; $availTable = 'staff_availability';
        $availIdCol = 'staff_id'; $availPK = 'id';
    } else {
        throw new Exception("Invalid type specified.");
    }

    $stmt = $con->prepare("UPDATE $mainTable SET $nameCol = ?, $detailsCol = ? WHERE $mainIdCol = ?");
    $stmt->bind_param("ssi", $name, $details, $id);
    $stmt->execute();

    if (!empty($deletedScheduleIds)) {
        $ids_placeholder = implode(',', array_fill(0, count($deletedScheduleIds), '?'));
        $stmt_del = $con->prepare("DELETE FROM $availTable WHERE $availPK IN ($ids_placeholder)");
        $stmt_del->bind_param(str_repeat('i', count($deletedScheduleIds)), ...$deletedScheduleIds);
        $stmt_del->execute();
    }

    if (!empty($updatedSchedules)) {
        // Since we are only updating time, the day_of_week is not involved here. This is fine.
        $stmt_update = $con->prepare("UPDATE $availTable SET start_time = ?, end_time = ? WHERE $availPK = ?");
        foreach ($updatedSchedules as $s) {
            $stmt_update->bind_param("ssi", $s['start_time'], $s['end_time'], $s['availability_id']);
            $stmt_update->execute();
        }
    }
    
    if (!empty($newSchedules)) {
        $stmt_add = $con->prepare("INSERT INTO $availTable ($availIdCol, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        foreach ($newSchedules as $s) {
            // **FIX: Saves the numeric day directly for both staff and doctors**
            $stmt_add->bind_param("iiss", $id, $s['day_of_week'], $s['start_time'], $s['end_time']);
            $stmt_add->execute();
        }
    }

    $con->commit();
    echo json_encode(["success" => true, "message" => "Changes saved successfully."]);

} catch (Exception $e) {
    $con->rollback();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$con->close();
?>