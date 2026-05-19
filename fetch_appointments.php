<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';


date_default_timezone_set('Asia/Manila');


$updateStmt = $con->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appointment_id = ?");

$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.status,
               COALESCE(u.full_name, '') AS patient_name,
               COALESCE(d.doctor_name, '') AS doctor_name
        FROM appointment a
        LEFT JOIN users u ON a.patient_id = u.id
        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
        ORDER BY a.appointment_date, a.appointment_time";

$res = $con->query($sql);
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        
        
        if ($row['status'] === 'Scheduled') {
            try {
                
                $appointmentDateTimeStr = $row['appointment_date'] . ' ' . $row['appointment_time'];
                $appointmentDateTime = new DateTime($appointmentDateTimeStr);

                
                $appointmentDateTime->add(new DateInterval('PT15M'));

                
                $now = new DateTime();

                
                if ($now > $appointmentDateTime) {
                   
                    $updateStmt->bind_param("i", $row['appointment_id']);
                    $updateStmt->execute();
                    
                    
                    $row['status'] = 'Cancelled';
                }
            } catch (Exception $e) {
                
            }
        }
        

        $out[] = $row;
    }
}
$updateStmt->close();
echo json_encode($out);
?>
