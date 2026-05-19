<?php
session_start();
include("db.php");
header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Authentication required."]);
    exit();
}

$patient_id = $_SESSION['user_id'];
$consultation_id = $_GET['id'] ?? 0;

if (!$consultation_id) {
    echo json_encode(["success" => false, "message" => "Invalid Consultation ID."]);
    exit();
}


$stmt = $con->prepare("
    SELECT 
        c.consultation_date,
        c.diagnosis,
        c.prescription,
        c.notes,
        d.doctor_name
    FROM consultation c
    JOIN doctor d ON c.doctor_id = d.doctor_id
    WHERE c.consultation_id = ? AND c.patient_id = ?
");
$stmt->bind_param("ii", $consultation_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if ($data) {
    
    foreach ($data as $key => $value) {
        $data[$key] = htmlspecialchars($value);
    }
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "Consultation not found or you do not have permission to view it."]);
}

$con->close();
?>
