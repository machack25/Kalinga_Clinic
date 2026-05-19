<?php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id  = $_POST['patient'];
    $doctor_id   = $_POST['doctor'];
    $date        = $_POST['date'];
    $diagnosis   = $_POST['diagnosis'];
    $notes       = $_POST['notes'];

    $stmt = $con->prepare("INSERT INTO consultation (patient_id, doctor_id, consultation_date, diagnosis, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $date, $diagnosis, $notes);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;

        $patient = $con->query("SELECT full_name FROM users WHERE id = $patient_id")->fetch_assoc();
        $doctor  = $con->query("SELECT doctor_name FROM doctor WHERE doctor_id = $doctor_id")->fetch_assoc();

        echo json_encode([
            "success" => true,
            "id" => $id,
            "patient_name" => $patient['full_name'],
            "doctor_name" => $doctor['doctor_name']
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Insert failed"]);
    }
}
?>