<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$name = $_POST['medicine_name'] ?? '';
$availability = $_POST['availability'] ?? 'no';

if (!$name) {
    echo json_encode(['success'=>false, 'message'=>'Medicine name required']);
    exit;
}

$stmt = $con->prepare("INSERT INTO inventory (medicine_name, availability) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $availability);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$stmt->insert_id, 'message'=>'Medicine added successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Error adding medicine']);
}
?>
