<?php
session_start();
include("db.php");
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit();
}

$doctor_id = $_GET['doctor_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (empty($doctor_id) || empty($date)) {
    echo json_encode([]);
    exit();
}

// Get all appointments that are NOT 'Cancelled' or 'Completed'
$stmt = $con->prepare("SELECT appointment_time FROM appointment WHERE doctor_id = ? AND appointment_date = ? AND status IN ('Pending', 'Scheduled')");
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    // Format to H:i (e.g., 09:30)
    $booked_slots[] = date("H:i", strtotime($row['appointment_time']));
}

$stmt->close();
$con->close();

echo json_encode($booked_slots);
?>