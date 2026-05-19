<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Add for detailed errors
date_default_timezone_set('Asia/Manila'); // Ensure timezone

// Set header to return JSON
header('Content-Type: application/json');

// 1. Security Check: Ensure an admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

// Check if it's a POST request and required data (excluding appointment_id) is present
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['patient_id'], $_POST['doctor_id'], $_POST['consultation_date'], $_POST['diagnosis'])) {

    // Make appointment_id optional (use null if not provided or empty)
    $appointment_id = isset($_POST['appointment_id']) && !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
    $patient_id = (int)$_POST['patient_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $consultation_date = $_POST['consultation_date']; // Get date from form
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription'] ?? ''); // Optional
    $notes = trim($_POST['notes'] ?? ''); // Optional

    // Basic validation
    if (empty($patient_id) || empty($doctor_id) || empty($consultation_date) || empty($diagnosis)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields (Patient, Doctor, Date, Diagnosis).']);
        exit();
    }
    // Validate date format if needed
    if (DateTime::createFromFormat('Y-m-d', $consultation_date) === false) {
         echo json_encode(['success' => false, 'message' => 'Invalid date format. Please use YYYY-MM-DD.']);
        exit();
    }


    try {
        // Insert into the consultation table (handle NULL appointment_id)
        $stmt = $con->prepare("INSERT INTO consultation (appointment_id, consultation_date, diagnosis, prescription, notes, patient_id, doctor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Use "i" for integers/null, "s" for strings
        $stmt->bind_param("isssiii", $appointment_id, $consultation_date, $diagnosis, $prescription, $notes, $patient_id, $doctor_id);

        if ($stmt->execute()) {
             echo json_encode(['success' => true]);
        } else {
             // Throw exception if execute fails
             throw new Exception($stmt->error);
        }
        $stmt->close();

        // REMOVED: Update appointment status - not needed when adding directly

    } catch (mysqli_sql_exception $exception) {
        // Log the error for debugging, show generic message
        error_log("Consultation Save Error (add_consultation.php): " . $exception->getMessage());
        // Provide more specific feedback for known errors if possible
        if($exception->getCode() == 1062) { // Duplicate entry
             echo json_encode(['success' => false, 'message' => 'Error: Potential duplicate record detected.']);
        } elseif ($exception->getCode() == 1452) { // Foreign key constraint fails
             echo json_encode(['success' => false, 'message' => 'Error: Invalid Patient or Doctor selected.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'A database error occurred. Could not save the consultation.']);
        }
    } catch (Exception $e) { // Catch general exceptions
         error_log("General Error (add_consultation.php): " . $e->getMessage());
         echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    }

} else {
    // Be more specific about what's missing if possible (for debugging)
    $missing = [];
    if (!isset($_POST['patient_id'])) $missing[] = 'patient_id';
    if (!isset($_POST['doctor_id'])) $missing[] = 'doctor_id';
    if (!isset($_POST['consultation_date'])) $missing[] = 'consultation_date';
    if (!isset($_POST['diagnosis'])) $missing[] = 'diagnosis';
    $message = 'Invalid request' . (!empty($missing) ? ' - missing: ' . implode(', ', $missing) : '.');

    echo json_encode(['success' => false, 'message' => $message]);
}

$con->close(); // Close connection
?>