<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$name = $_POST['medicine_name'] ?? '';
$availability = $_POST['availability'] ?? 'no';

if ($id <= 0 || !$name) {
    echo json_encode(['success'=>false, 'message'=>'Invalid input']);
    exit;
}

$stmt = $con->prepare("UPDATE inventory SET medicine_name=?, availability=? WHERE id=?");
$stmt->bind_param("ssi", $name, $availability, $id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$id, 'message'=>'Medicine updated successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Error updating medicine']);
}
?>
