<?php
session_start();
include("db.php");


if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}


header('Content-Type: application/json');


$today = new DateTime();
$today_str = $today->format('Y-m-d');


$week_start = (clone $today)->modify('-7 days')->format('Y-m-d');
$stmt_weekly = $con->prepare("SELECT COUNT(*) AS weekly_count FROM consultation WHERE consultation_date BETWEEN ? AND ?");
$stmt_weekly->bind_param("ss", $week_start, $today_str);
$stmt_weekly->execute();
$weekly_count = $stmt_weekly->get_result()->fetch_assoc()['weekly_count'];
$stmt_weekly->close();


$current_month = $today->format('Y-m');
$stmt_monthly = $con->prepare("SELECT COUNT(*) AS monthly_count FROM consultation WHERE DATE_FORMAT(consultation_date, '%Y-%m') = ?");
$stmt_monthly->bind_param("s", $current_month);
$stmt_monthly->execute();
$monthly_count = $stmt_monthly->get_result()->fetch_assoc()['monthly_count'];
$stmt_monthly->close();


echo json_encode([
    'weekly' => $weekly_count,
    'monthly' => $monthly_count
]);
?>