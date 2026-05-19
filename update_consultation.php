<?php
include("db.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

if (empty($_POST['consultation_id']) || empty($_POST['patient_id']) || empty($_POST['doctor_id']) || empty($_POST['diagnosis']) || empty($_POST['consultation_date'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields for update."]);
    exit;
}

$consultation_id = $_POST['consultation_id'];
$patient_id = $_POST['patient_id'];
$doctor_id = $_POST['doctor_id'];
$consultation_date = $_POST['consultation_date'];
$diagnosis = $_POST['diagnosis'];
$prescription = $_POST['prescription'] ?? '';
$notes = $_POST['notes'] ?? '';

try {
    $stmt = $con->prepare("UPDATE consultation SET patient_id = ?, doctor_id = ?, consultation_date = ?, diagnosis = ?, prescription = ?, notes = ? WHERE consultation_id = ?");
    $stmt->bind_param("iissssi", $patient_id, $doctor_id, $consultation_date, $diagnosis, $prescription, $notes, $consultation_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Consultation updated successfully."]);
        } else {
            echo json_encode(["success" => true, "message" => "No changes were made."]);
        }
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$con->close();
?>
