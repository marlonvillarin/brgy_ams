<?php
require_once 'includes/db.php';

$date = $_GET['date'] ?? '';
$time_slots = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM"];

$full = [];

foreach ($time_slots as $t) {
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM appointments 
    WHERE appt_date=? 
      AND appt_time=? 
      AND status!='rejected' 
      AND deleted_at IS NULL
  ");
    $stmt->bind_param("ss", $date, $t);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];

    if ($count >= 10) {
        $full[] = $t;
    }
}

echo json_encode(["full" => $full]);