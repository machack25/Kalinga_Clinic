<?php

date_default_timezone_set('Asia/Manila');


include("db.php");


$sql = "
    DELETE FROM appointment
    WHERE
        status != 'Completed'
    AND
        NOW() > DATE_ADD(TIMESTAMP(appointment_date, appointment_time), INTERVAL 3 HOUR)
";


if ($con->query($sql) === TRUE) {
    
    $affected_rows = $con->affected_rows;
    
    echo "Cleanup executed at " . date('Y-m-d H:i:s') . ". Deleted $affected_rows overdue appointments.\n";
} else {
    
    echo "Error during appointment cleanup: " . $con->error . "\n";
}


$con->close();
?>