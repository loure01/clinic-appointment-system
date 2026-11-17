
<?php
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: my_appointments.php");
    exit;
}

$appointment_id = filter_input(INPUT_POST, "appointment_id", FILTER_VALIDATE_INT);
$email = trim($_POST["email"] ?? "");

if (!$appointment_id || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid appointment information.");
}

$stmt = $conn->prepare(
    "UPDATE appointments
     INNER JOIN patients
        ON appointments.patient_id = patients.patient_id
     SET appointments.status = 'Cancelled'
     WHERE appointments.appointment_id = ?
       AND patients.email = ?
       AND appointments.status = 'Booked'"
);

$stmt->bind_param("is", $appointment_id, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: my_appointments.php?cancelled=1");
    exit;
}

header("Location: my_appointments.php?cancel_error=1");
exit;
?>
