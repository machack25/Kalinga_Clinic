<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success'=>false, 'message'=>'Invalid ID']);
    exit;
}

$stmt = $con->prepare("DELETE FROM inventory WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Medicine deleted successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Error deleting medicine']);
}
?>
