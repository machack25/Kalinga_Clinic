<?php
include("db.php");
header('Content-Type: application/json');
session_start();


if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['type'], $data['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit;
}

$type = $data['type'];
$id = (int)$data['id'];
$response = ["success" => false];

$con->begin_transaction();

try {
    if ($type === 'doctor') {
        $stmt = $con->prepare("DELETE FROM doctor WHERE doctor_id = ?");
    } elseif ($type === 'staff') {
        $stmt = $con->prepare("DELETE FROM staff WHERE id = ?");
    } else {
        throw new Exception("Invalid type specified.");
    }

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $con->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $con->commit();
        $response = ["success" => true];
    } else {
        throw new Exception("Record not found or already deleted.");
    }
    
    $stmt->close();

} catch (Exception $e) {
    $con->rollback();
    $response["message"] = "Error: " . $e->getMessage();
}

echo json_encode($response);
$con->close();
?>