<?php
session_start();
include("db.php");
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

if (!isset($_GET['id'])) {
    // No ID provided, just go back
    header("Location: patient dashboard.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'];

// Prepare statement to cancel the appointment
// This query ensures that a patient can only cancel their own 'Scheduled' appointments
$stmt = $con->prepare("
    UPDATE appointment 
    SET status = 'Cancelled' 
    WHERE appointment_id = ? AND patient_id = ? AND status = 'Scheduled'
");
$stmt->bind_param("ii", $appointment_id, $patient_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Success
        $_SESSION['status_message'] = "Your appointment has been successfully cancelled.";
        $_SESSION['status_type'] = "success";
    } else {
        // No rows were changed.
        $_SESSION['status_message'] = "Could not cancel the appointment. It may have already been processed or does not exist.";
        $_SESSION['status_type'] = "error";
    }
} else {
    // Query failed
    $_SESSION['status_message'] = "An error occurred while cancelling. Please try again.";
    $_SESSION['status_type'] = "error";
}

$stmt->close();
$con->close();


header("Location: patient dashboard.php");
exit();
?>