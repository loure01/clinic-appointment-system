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

    $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);

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

function getUpcomingBookedMap($conn) {
    $today = date('Y-m-d');

    $stmt = $conn->prepare(
        "SELECT doctor_id, appointment_date, appointment_time
         FROM appointments
         WHERE appointment_date >= ?
         AND status != 'Cancelled'"
    );

    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $map = [];

    while ($row = $result->fetch_assoc()) {
        $docId = $row['doctor_id'];
        $date  = $row['appointment_date'];
        $map[$docId][$date][] = $row['appointment_time'];
    }
    return $map;
}

// ==========================================
// NEW FUNCTIONS FOR PATIENT DASHBOARD
// ==========================================

// 1. Fetch appointments for a specific patient
function getAppointmentsByPatient($conn, $patient_id) {
    // We join doctors and departments to get the names
    // Note: If your doctors table uses a different column name for the department link, 
    // change 'doctors.department_id' to match your database.
    $stmt = $conn->prepare(
        "SELECT 
            appointments.*, 
            doctors.name AS doctor_name, 
            departments.name AS dept_name,
            payments.payment_status
         FROM appointments
         INNER JOIN doctors ON appointments.doctor_id = doctors.doctor_id
         LEFT JOIN departments ON doctors.department_id = departments.department_id
         LEFT JOIN payments ON appointments.appointment_id = payments.appointment_id
         WHERE appointments.patient_id = ?
         ORDER BY appointments.appointment_date DESC, appointments.appointment_time DESC"
    );
    
    if (!$stmt) {
        // If there is a database error, this will print it out so you can debug it
        die("Database Error: " . $conn->error);
    }

    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments = [];

    while ($row = $result->fetch_assoc()) {
        // Fallback if the department name is empty
        if (empty($row['dept_name'])) {
            $row['dept_name'] = 'General';
        }
        $appointments[] = $row;
    }
    return $appointments;
}

// 2. Cancel an appointment safely
function cancelAppointment($conn, $appt_id, $patient_id) {
    // Check if the appointment exists, belongs to the patient, and is currently 'Booked'
    $stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE appointment_id = ? AND patient_id = ? AND status = 'Booked'");
    $stmt->bind_param("ii", $appt_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $apptTimestamp = strtotime($row['appointment_date'] . ' ' . $row['appointment_time']);
        
        // Only cancel if it's more than 2 hours (7200 seconds) away
        if (($apptTimestamp - time()) > 7200) {
            $updateStmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
            $updateStmt->bind_param("i", $appt_id);
            return $updateStmt->execute();
        }
    }
    return false;
}
?>