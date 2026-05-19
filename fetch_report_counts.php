<?php
include("db.php");
header('Content-Type: application/json');

try {
    $today = new DateTime();
    $today_str = $today->format('Y-m-d');

    
    $week_start = (clone $today)->modify('-7 days')->format('Y-m-d');
    $stmt = $con->prepare("SELECT COUNT(*) AS weekly_count FROM consultation WHERE consultation_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $week_start, $today_str);
    $stmt->execute();
    $weekly_count = $stmt->get_result()->fetch_assoc()['weekly_count'];

    
    $current_month = $today->format('Y-m');
    $stmt = $con->prepare("SELECT COUNT(*) AS monthly_count FROM consultation WHERE DATE_FORMAT(consultation_date, '%Y-%m') = ?");
    $stmt->bind_param("s", $current_month);
    $stmt->execute();
    $monthly_count = $stmt->get_result()->fetch_assoc()['monthly_count'];
    
    echo json_encode([
        "success" => true,
        "weekly_count" => $weekly_count,
        "monthly_count" => $monthly_count
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Could not fetch counts."]);
}

$con->close();
?>
