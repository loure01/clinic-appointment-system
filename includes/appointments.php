// Add this function to the bottom of includes/appointments.php
function getUpcomingBookedMap($conn) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT doctor_id, appointment_date, appointment_time 
         FROM appointments 
         WHERE appointment_date >= ? AND status != 'Cancelled'"
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