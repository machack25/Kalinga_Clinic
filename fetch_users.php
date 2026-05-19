<?php
session_start();
include("db.php");

// Set header to return JSON
header('Content-Type: application/json');

// Security check: Ensure an admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([]); // Return empty array if not authorized
    exit();
}

// This query now selects the 'details' column
$query = "SELECT id, full_name, username, email, phone, created_at, birthdate, age, details 
          FROM users 
          WHERE role = 'user' 
          ORDER BY full_name";
          
$result = $con->query($query);

if (!$result) {
    echo json_encode(['error' => 'Database query failed: ' . $con->error]);
    exit();
}

$users = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($users);

$con->close();
?>