<?php
session_start();
include("db.php"); // Include your database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json'); // Set header for JSON response

// --- Admin Security check ---
// Check specifically for admin_id
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(["error" => "Access denied. Admin privileges required."]);
    exit();
}
// --- END Security check ---

$booked_slots = []; // Initialize empty array

// Validate input parameters
$doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT);
$date = $_GET['date'] ?? ''; // Keep as string for SQL

// Basic validation for date format (YYYY-MM-DD)
if (!empty($doctor_id) && !empty($date) && DateTime::createFromFormat('Y-m-d', $date) !== false) {
    try {
        $stmt = $con->prepare("
            SELECT TIME_FORMAT(appointment_time, '%H:%i') as booked_time
            FROM appointment
            WHERE doctor_id = ?
              AND appointment_date = ?
              AND status IN ('Pending', 'Scheduled')
        ");
        $stmt->bind_param("is", $doctor_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $booked_slots[] = $row['booked_time']; // Add HH:MM format
        }
        $stmt->close();

    } catch (Exception $e) {
        // Log error, but return empty array to client to avoid breaking JS
        error_log("Error fetching booked slots (admin): " . $e->getMessage());
       
    }
} else {
    // Invalid parameters received
    error_log("get_admin_booked_slots.php: Invalid or missing doctor_id ($doctor_id) or date ($date)");
   
}

// Always return a JSON array (even if empty)
echo json_encode($booked_slots);

if (isset($con)) {
    $con->close(); // Close connection if established
}
?>