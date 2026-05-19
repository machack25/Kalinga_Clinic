<?php
session_start();
include("db.php");
header('Content-Type: application/json');

// Security check: ensure only logged-in admins can access this
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit();
}

$query = "SELECT 
            a.appointment_id, 
            a.appointment_date, 
            a.appointment_time, 
            a.reason, 
            u.full_name AS patient_name, 
            d.doctor_name 
          FROM appointment a
          JOIN users u ON a.patient_id = u.id
          JOIN doctor d ON a.doctor_id = d.doctor_id
          WHERE a.status = 'Pending'
          ORDER BY a.appointment_date, a.appointment_time";

$result = $con->query($query);
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode($bookings);
$con->close();
?>