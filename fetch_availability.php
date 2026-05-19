<?php
include("db.php");


date_default_timezone_set("Asia/Manila"); 


$currentDayName    = date('l');     
$currentDayNumeric = date('N'); 
$currentTime       = date('H:i:s');  

$response = [
    "doctors" => [],
    "staff" => []
];


$sqlDoctors = "SELECT d.doctor_id, d.doctor_name, d.specialization,
                      da.day_of_week, da.start_time, da.end_time
               FROM doctor d
               JOIN doctor_availability da ON d.doctor_id = da.doctor_id
               WHERE da.day_of_week = ?
                 AND ? BETWEEN da.start_time AND da.end_time
               ORDER BY d.doctor_name";

$stmt = $con->prepare($sqlDoctors);
$stmt->bind_param("is", $currentDayNumeric, $currentTime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response["doctors"][] = $row;
}
$stmt->close();


$sqlStaff = "SELECT s.id, s.full_name, s.position,
                    sa.day_of_week, sa.start_time, sa.end_time
             FROM staff s
             JOIN staff_availability sa ON s.id = sa.staff_id
             WHERE sa.day_of_week = ?
               AND ? BETWEEN sa.start_time AND sa.end_time
             ORDER BY s.full_name";

$stmt = $con->prepare($sqlStaff);

$stmt->bind_param("is", $currentDayNumeric, $currentTime); 
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response["staff"][] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($response);

$con->close();
?>