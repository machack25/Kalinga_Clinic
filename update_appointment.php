<?php
include("db.php");

if (isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    $stmt = $con->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    echo json_encode(["success" => true]);
}
?>
