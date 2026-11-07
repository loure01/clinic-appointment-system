<?php
function completeAppointmentWithRecord($conn, $patient_id, $doctor_id, $appointment_id, $diagnosis, $prescription) {
    $check = $conn->prepare("SELECT record_id FROM medical_records WHERE appointment_id = ?");
    $check->bind_param("i", $appointment_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        return "Record already exists for this appointment.";
    }

    $record_date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, record_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $patient_id, $doctor_id, $appointment_id, $diagnosis, $prescription, $record_date);
    
    if ($stmt->execute()) {
        $update = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
        $update->bind_param("i", $appointment_id);
        $update->execute();
        return true;
    }
    return "Failed to save record.";
}
?>