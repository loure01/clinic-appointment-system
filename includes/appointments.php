<?php
function isSlotBooked($conn, $doctor_id, $date, $time) {
    $stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'Booked'");
    $stmt->bind_param("iss", $doctor_id, $date, $time);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function createAppointment($conn, $patient_id, $doctor_id, $date, $time) {
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);
    return $stmt->execute() ? $stmt->insert_id : false;
}
?>