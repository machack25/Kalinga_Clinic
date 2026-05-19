<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
include("db.php");


$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');

    
    if (empty($type) || empty($name) || empty($details)) {
        $response['message'] = 'Error: Name and details (Position/Specialization) are required.';
        echo json_encode($response);
        exit();
    }

    
    $days = [
        "Monday" => 1, "Tuesday" => 2, "Wednesday" => 3,
        "Thursday" => 4, "Friday" => 5, "Saturday" => 6, "Sunday" => 7
    ];

    $con->begin_transaction(); 

    try {
        $new_id = 0;
        if ($type === 'doctor') {
            $stmt = $con->prepare("INSERT INTO doctor (doctor_name, specialization) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $details);
            $stmt->execute();
            $new_id = $con->insert_id;

            if ($new_id > 0 && !empty($schedule)) {
                $slots = explode(";", $schedule);
                foreach ($slots as $slot) {
                    $slot = trim($slot);
                    if (!$slot) continue;

                    
                    if (preg_match("/(\w+) (\d{2}:\d{2}(?::\d{2})?)-(\d{2}:\d{2}(?::\d{2})?)/i", $slot, $m)) {
                        $dayName = ucfirst(strtolower($m[1]));
                        $start = $m[2];
                        $end = $m[3];
                        $dayNum = $days[$dayName] ?? null;

                        if ($dayNum) {
                            $stmt_avail = $con->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                            $stmt_avail->bind_param("iiss", $new_id, $dayNum, $start, $end);
                            $stmt_avail->execute();
                        }
                    }
                }
            }
            $response['message'] = '✅ Doctor added successfully.';

        } elseif ($type === 'staff') {
            $stmt = $con->prepare("INSERT INTO staff (full_name, position) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $details);
            $stmt->execute();
            $new_id = $con->insert_id;

             if ($new_id > 0 && !empty($schedule)) {
                $slots = explode(";", $schedule);
                foreach ($slots as $slot) {
                    $slot = trim($slot);
                    if (!$slot) continue;
                    
                    if (preg_match("/(\w+) (\d{2}:\d{2}(?::\d{2})?)-(\d{2}:\d{2}(?::\d{2})?)/i", $slot, $m)) {
                        $dayName = ucfirst(strtolower($m[1]));
                        $start = $m[2];
                        $end = $m[3];
                        $dayNum = $days[$dayName] ?? null;

                        if ($dayNum) {
                            $stmt_avail = $con->prepare("INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                            $stmt_avail->bind_param("iiss", $new_id, $dayNum, $start, $end);
                            $stmt_avail->execute();
                        }
                    }
                }
            }
            $response['message'] = '✅ Staff added successfully.';
        } else {
             throw new Exception('Invalid type specified.');
        }

        $con->commit(); 
        $response['success'] = true;
        
        $response['newRecord'] = [
            'id' => $new_id,
            'name' => $name,
            'details' => $details,
            'schedule' => $schedule
        ];

    } catch (Exception $e) {
        $con->rollback(); 
        $response['message'] = 'Database Error: ' . $e->getMessage();
    }

    echo json_encode($response);
}
?>