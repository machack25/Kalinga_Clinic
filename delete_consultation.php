<?php
include("db.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

if (!isset($_POST['consultation_id'])) {
    echo json_encode(["success" => false, "message" => "No ID provided."]);
    exit;
}

$id = (int)$_POST['consultation_id'];

try {
    $stmt = $con->prepare("DELETE FROM consultation WHERE consultation_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Record deleted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Record not found or already deleted."]);
        }
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$con->close();
?>
