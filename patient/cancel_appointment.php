<?php
require_once '../config/db.php';

$appt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$email   = trim($_GET['email'] ?? '');

if ($appt_id > 0) {
    $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND status = 'Booked'");
    $stmt->bind_param("i", $appt_id);
    $stmt->execute();
}

header("Location: my_appointments.php?email=" . urlencode($email) . "&cancelled=1");
exit;