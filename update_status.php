<?php
include("db.php");

if (isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status']; 

    $stmt = $con->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    if ($status === 'Completed') {
        $appt_stmt = $con->prepare("SELECT patient_id, doctor_id, appointment_date FROM appointment WHERE appointment_id = ?");
        $appt_stmt->bind_param("i", $id);
        $appt_stmt->execute();
        $appt_result = $appt_stmt->get_result();
        $appointment = $appt_result->fetch_assoc();
        $appt_stmt->close();

        if ($appointment) {
            $check_stmt = $con->prepare("SELECT consultation_id FROM consultation WHERE appointment_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_stmt->close();

            if ($check_result->num_rows == 0) {
                $consult_stmt = $con->prepare("INSERT INTO consultation (appointment_id, patient_id, doctor_id, consultation_date, diagnosis, prescription, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $default_diagnosis = "Consultation completed - diagnosis pending";
                $default_prescription = "Prescription to be added";
                $default_notes = "Consultation completed. Please update diagnosis and prescription details.";
                $consult_stmt->bind_param("iiissss", $id, $appointment['patient_id'], $appointment['doctor_id'], $appointment['appointment_date'], $default_diagnosis, $default_prescription, $default_notes);
                $consult_stmt->execute();
                $consult_stmt->close();
            }
        }
    }

    echo json_encode(["success" => true]);
}
?>