<?php

function getAllAppointments($conn) {

    $sql = "SELECT
                appointments.*,
                doctors.name AS doctor_name,
                patients.name AS patient_name
            FROM appointments
            INNER JOIN doctors
                ON appointments.doctor_id = doctors.doctor_id
            INNER JOIN patients
                ON appointments.patient_id = patients.patient_id
            ORDER BY appointments.appointment_date DESC";

    $result = $conn->query($sql);

    $appointments = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }

    return $appointments;
}


function createAppointment($conn, $patient_id, $doctor_id, $date, $time) {

    $stmt = $conn->prepare(
        "INSERT INTO appointments
            (patient_id, doctor_id, appointment_date, appointment_time)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "iiss",
        $patient_id,
        $doctor_id,
        $date,
        $time
    );

    if ($stmt->execute()) {
        return $stmt->insert_id;
    }

    return false;
}


function getBookedSlots($conn, $doctor_id, $date) {

    $stmt = $conn->prepare(
        "SELECT appointment_time
         FROM appointments
         WHERE doctor_id = ?
         AND appointment_date = ?
         AND status != 'Cancelled'"
    );

    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();

    $result = $stmt->get_result();

    $slots = [];

    while ($row = $result->fetch_assoc()) {
        $slots[] = $row['appointment_time'];
    }

    return $slots;
}

?>