<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $con->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$medicine = $result->fetch_assoc();

if ($medicine) {
    echo json_encode($medicine);
} else {
    echo json_encode(['error' => 'Medicine not found']);
}
?>
