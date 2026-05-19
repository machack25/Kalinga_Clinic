<?php
include("db.php");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $availability = $_POST['availability'] ?? '';

    if ($name === '' || !in_array($availability, ['Yes', 'No'])) {
        echo json_encode(["success" => false, "error" => "Invalid input"]);
        exit;
    }

    $stmt = $con->prepare("INSERT INTO inventory (medicine_name, availability) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $availability);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => $con->error]);
    }
}
?>
