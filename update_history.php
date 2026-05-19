<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = $_POST['id'] ?? '';
    $date      = $_POST['date'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $notes     = $_POST['notes'] ?? '';

    if (empty($id) || empty($date) || empty($diagnosis)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $stmt = $con->prepare("
        UPDATE consultation 
        SET consultation_date = ?, diagnosis = ?, notes = ?
        WHERE consultation_id = ?
    ");
    $stmt->bind_param("sssi", $date, $diagnosis, $notes, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>
