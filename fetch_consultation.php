<?php
include("db.php");
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "No ID provided."]);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $con->prepare("SELECT * FROM consultation WHERE consultation_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => "Record not found."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$con->close();
?>
