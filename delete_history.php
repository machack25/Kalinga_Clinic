<?php
include("db.php");

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $con->prepare("DELETE FROM consultation WHERE consultation_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(["success" => true]);
}
?>
