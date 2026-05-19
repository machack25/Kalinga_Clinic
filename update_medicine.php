<?php
include("db.php");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $availability = $_POST['availability'] ?? '';

    if ($id <= 0 || $name === '' || !in_array($availability, ['Yes', 'No'])) {
        echo json_encode(["success" => false, "error" => "Invalid input"]);
        exit;
    }

    $stmt = $con->prepare("UPDATE inventory SET medicine_name = ?, availability = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $availability, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $con->error]);
    }
}
?>
