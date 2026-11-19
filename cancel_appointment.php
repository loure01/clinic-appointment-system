<?php
// Adjust the path to db.php based on where this file is located
require_once 'config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appt_id = (int)$_POST['appointment_id'];
    $email   = trim($_POST['patient_email']);

    // Verify the appointment belongs to this email before cancelling
    // Make sure your appointments table has a 'status' column. If not, run: 
    // ALTER TABLE appointments ADD COLUMN status VARCHAR(50) DEFAULT 'Confirmed';
    $stmt = $conn->prepare("
        UPDATE appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        SET a.status = 'Cancelled' 
        WHERE a.appointment_id = ? AND p.email = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param("is", $appt_id, $email);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Success! Redirect back to the manage page with a success message
            header("Location: patient/my_appointments.php?email=" . urlencode($email) . "&cancelled=1");
            exit;
        } else {
            // Failed (either wrong email or already cancelled)
            header("Location: patient/my_appointments.php?email=" . urlencode($email) . "&error=1");
            exit;
        }
    } else {
        // SQL Error
        die("Database error: " . $conn->error);
    }
}

// Fallback if someone tries to access this file directly without POST
header("Location: index.php");
exit;
?>