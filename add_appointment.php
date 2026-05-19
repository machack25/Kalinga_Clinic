<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Check if admin is logged in (adjust session variable if needed)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Validate required fields
$required_fields = ['patient', 'doctor', 'date', 'time', 'reason'];
$missing_fields = [];
foreach($required_fields as $field) {
    // Use trim() to ensure spaces don't count as filled
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
    exit();
}

$patient_id = (int)$_POST['patient'];
$doctor_id = (int)$_POST['doctor'];
$appointment_date = $_POST['date'];
$appointment_time = $_POST['time'] . ":00"; // Add seconds for TIME type compatibility
$reason = trim($_POST['reason']);
$status = 'Scheduled'; // Admin bookings are directly scheduled

// Validate date (not in the past) - Allow booking for today
if ($appointment_date < date('Y-m-d')) {
     echo json_encode(['success' => false, 'message' => 'Cannot book appointments on past dates.']);
    exit();
}

// Validate time format (HH:MM:SS) - basic check
if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $appointment_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format submitted.']);
    exit();
}


// --- SERVER-SIDE ANTI-DUPLICATION CHECK ---
try {
    $checkStmt = $con->prepare("SELECT COUNT(*) FROM appointment
                                WHERE doctor_id = ?
                                AND appointment_date = ?
                                AND appointment_time = ?
                                AND status IN ('Pending', 'Scheduled')");
    $checkStmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $checkStmt->execute();
    $checkStmt->bind_result($conflictCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($conflictCount > 0) {
        echo json_encode(['success' => false, 'message' => "This time slot [{$_POST['time']}] is already booked or pending for the selected doctor on this date."]);
        exit();
    }

    // --- Proceed with Insertion ---
    $insertStmt = $con->prepare("INSERT INTO appointment (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                                 VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        // Throw exception if insert fails
        throw new Exception($insertStmt->error);
    }
    $insertStmt->close();

} catch (mysqli_sql_exception $e) {
    error_log("Add Appointment Error: " . $e->getMessage() . " | SQL State: " . $e->getSqlState() . " | Code: " . $e->getCode()); // Log detailed error
    // Check for specific constraint violations
    if ($e->getCode() == 1062) { // Duplicate entry based on unique constraints
         // Determine which constraint failed (this requires knowing your unique key names)
         if (strpos($e->getMessage(), 'unique_patient_slot') !== false) {
              echo json_encode(['success' => false, 'message' => 'Error: This patient already has an appointment scheduled at this exact date and time.']);
         } elseif (strpos($e->getMessage(), 'unique_doctor_slot') !== false || strpos($e->getMessage(), 'unique_doctor_schedule') !== false) {
             echo json_encode(['success' => false, 'message' => 'Error: This time slot is already booked for the selected doctor. Please refresh and choose another slot.']);
         } else {
             echo json_encode(['success' => false, 'message' => 'Error: A scheduling conflict occurred. Please try a different time or date.']);
         }
    } elseif ($e->getCode() == 1452) { // Foreign key constraint fails
        echo json_encode(['success' => false, 'message' => 'Error: Invalid Patient or Doctor ID provided.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'A database error occurred (' . $e->getCode() . '). Please try again.']);
    }
} catch (Exception $e) { // Catch general exceptions
     error_log("General Add Appointment Error: " . $e->getMessage());
     echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

$con->close();
?>