<?php
session_start();
include("db.php");
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $data['appointment_id'] ?? 0;
    $action = $data['action'] ?? '';

    if (empty($appointment_id) || empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }

    if ($action == 'confirm') {
        // 1. Get appointment details to calculate queue
        $stmt_info = $con->prepare("SELECT appointment_date, doctor_id FROM appointment WHERE appointment_id = ?");
        $stmt_info->bind_param("i", $appointment_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $appt_info = $result_info->fetch_assoc();
        $stmt_info->close();

        if (!$appt_info) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
            exit();
        }
        
        $appointment_date = $appt_info['appointment_date'];
        $doctor_id = $appt_info['doctor_id'];

        // 2. Calculate new queue number (for that doctor on that day)
        $stmt_queue = $con->prepare("SELECT COUNT(*) as current_queue FROM appointment WHERE doctor_id = ? AND appointment_date = ? AND status = 'Scheduled'");
        $stmt_queue->bind_param("is", $doctor_id, $appointment_date);
        $stmt_queue->execute();
        $result_queue = $stmt_queue->get_result();
        $queue_data = $result_queue->fetch_assoc();
        $new_queue_number = (int)$queue_data['current_queue'] + 1;
        $stmt_queue->close();

        // 3. Update the appointment
        $stmt_confirm = $con->prepare("UPDATE appointment SET status = 'Scheduled', queue_number = ? WHERE appointment_id = ?");
        $stmt_confirm->bind_param("ii", $new_queue_number, $appointment_id);
        if ($stmt_confirm->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment confirmed. Queue: ' . $new_queue_number]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to confirm appointment.']);
        }
        $stmt_confirm->close();

    } elseif ($action == 'cancel') {
        $stmt_cancel = $con->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appointment_id = ?");
        $stmt_cancel->bind_param("i", $appointment_id);
        if ($stmt_cancel->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment.']);
        }
        $stmt_cancel->close();
    }
}
$con->close();
?>