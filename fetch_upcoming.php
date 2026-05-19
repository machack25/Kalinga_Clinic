<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.status,
               COALESCE(u.full_name, '') AS patient_name,
               COALESCE(d.doctor_name, '') AS doctor_name
        FROM appointment a
        LEFT JOIN users u ON a.patient_id = u.id
        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date, a.appointment_time
        LIMIT 20";

$res = $con->query($sql);
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $out[] = $row;
}
echo json_encode($out);
?>
