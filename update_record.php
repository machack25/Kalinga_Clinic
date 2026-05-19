<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id   = intval($_POST['id']);

    $days = [
        "Monday"=>1,"Tuesday"=>2,"Wednesday"=>3,
        "Thursday"=>4,"Friday"=>5,"Saturday"=>6,"Sunday"=>7
    ];

    if ($type === 'doctor') {
        $name     = $_POST['name'] ?? '';
        $details  = $_POST['details'] ?? '';
        $schedule = $_POST['schedule'] ?? '';

        $stmt = $con->prepare("UPDATE doctor SET doctor_name=?, specialization=? WHERE doctor_id=?");
        $stmt->bind_param("ssi", $name, $details, $id);
        $stmt->execute();

        if (!empty($schedule)) {
            $con->query("DELETE FROM doctor_availability WHERE doctor_id=$id");
            $slots = explode(";", $schedule);
            foreach ($slots as $slot) {
                $slot = trim($slot);
                if (!$slot) continue;

                if (preg_match("/(\w+) (\d{2}:\d{2}(?::\d{2})?)-(\d{2}:\d{2}(?::\d{2})?)/", $slot, $m)) {
                    $day = $m[1];
                    $start = $m[2];
                    $end = $m[3];
                    $dayNum = $days[$day] ?? 1;

                    $stmt = $con->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $id, $dayNum, $start, $end);
                    $stmt->execute();
                }
            }
        }

        echo "✅ Doctor updated successfully.";

    } elseif ($type === 'staff') {
        $name     = $_POST['name'] ?? '';
        $details  = $_POST['details'] ?? '';
        $schedule = $_POST['schedule'] ?? '';

        $stmt = $con->prepare("UPDATE staff SET full_name=?, position=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $details, $id);
        $stmt->execute();

        if (!empty($schedule)) {
            $con->query("DELETE FROM staff_availability WHERE staff_id=$id");
            $slots = explode(";", $schedule);
            foreach ($slots as $slot) {
                $slot = trim($slot);
                if (!$slot) continue;

                if (preg_match("/(\w+) (\d{2}:\d{2}(?::\d{2})?)-(\d{2}:\d{2}(?::\d{2})?)/", $slot, $m)) {
                    $day = $m[1];
                    $start = $m[2];
                    $end = $m[3];
                    $dayNum = $days[$day] ?? 1;

                    $stmt = $con->prepare("INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $id, $dayNum, $start, $end);
                    $stmt->execute();
                }
            }
        }

        echo "✅ Staff updated successfully.";

    } elseif ($type === 'patient') {
        $name  = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';

        $stmt = $con->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $phone, $id);
        $stmt->execute();

        echo "✅ Patient updated successfully.";
    }
}
?>
