<?php
function isSlotBooked($conn, $doctor_id, $date, $time) {
    $stmt = $conn->prepare
    ("SELECT appointment_id
     FROM appointments WHERE doctor_id = ? 
     AND appointment_date = ? 
     AND appointment_time = ? 
     AND status = 'Booked'
     ");
    $stmt->bind_param("iss", $doctor_id, $date, $time);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
//Manage appointment creation, checks schedule, and naga help para ma prevent ang duplicate bookings
function createAppointment($conn, $patient_id, $doctor_id, $date, $time) {

    if (isSlotBooked($conn, $doctor_id, $date, $time)) {
        return false;
    }

    $stmt = $conn->prepare
    ("INSERT INTO appointments
    (patient_id, doctor_id, appointment_date, appointment_time)
    VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);

    return $stmt->execute() ? $stmt->insert_id : false;
}

// It fetch all the appointments with patients id and doctors id
function getAllAppointments($conn) {
    $sql = "
        SELECT
            a.appointment_id,
            p.name AS patient_name,
            d.name AS doctor_name,
            a.appointment_date,
            a.appointment_time,
            a.status
        FROM appointments a
        INNER JOIN patients p ON a.patient_id = p.patient_id
        INNER JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.appointment_date DESC
    ";

    return $conn->query($sql);
}

?>