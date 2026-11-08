<?php
// 1. Include Config & Backend Modules
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/patients.php';
require_once 'includes/appointments.php';
require_once 'includes/payments.php';
require_once 'includes/medical_records.php';

$message = "";
$message_type = "";

// 2. Handle Form Submission (Appointment Booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_name = trim($_POST['patient_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $doctor_id    = (int)$_POST['doctor_id'];
    $date         = $_POST['appointment_date'];
    $time         = $_POST['appointment_time'];


    if (!empty($patient_name) && !empty($email) && !empty($phone) &&
        $doctor_id > 0 && !empty($date) && !empty($time)) {

        // Check if patient already exists
        $stmt = $conn->prepare(
            "SELECT patient_id FROM patients WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $patient = $stmt->get_result()->fetch_assoc();

        if ($patient) {

            // Get existing patient ID
            $patient_id = $patient['patient_id'];

        } else {

            // Register new patient
            $stmt = $conn->prepare(
                "INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $patient_name, $email, $phone);

            if ($stmt->execute()) {
                $patient_id = $stmt->insert_id;
            } else {
                $patient_id = false;
            }
        }

        // Create appointment
        if ($patient_id) {

            $appointment_id = createAppointment(
                $conn,
                $patient_id,
                $doctor_id,
                $date,
                $time
            );

            if ($appointment_id) {
                $message = "Appointment booked successfully! Appointment ID: #" . $appointment_id;
                $message_type = "success";
            } else {
                $message = "Booking failed. The selected time may already be booked.";
                $message_type = "error";
            }

        } else {
            $message = "Patient registration failed.";
            $message_type = "error";
        }

    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}


// 3. Fetch Data for Page Display
$departments  = getAllDepartments($conn);
$doctors = getAllDoctors($conn);
$appointments = getAllAppointments($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment System</title>
 <link rel="stylesheet" href="style.css">

</head>
<body>

<div class="container">
    <header>
        <h1>Clinic Appointment System</h1>
        <p>Book medical consultations and view scheduled appointments</p>
    </header>

    <?php if (!empty($message)): ?>
        <div class="alert <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Section 1: Appointment Booking Form -->
        <div class="card">
            <h2>Book Appointment</h2>
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label>Patient Name</label>
                    <input type="text" name="patient_name" required placeholder="Full Name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Email">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="Enter Phone Number">
                </div>
                <div class="form-group">
                    <label>Select Doctor</label>
                    <select name="doctor_id" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['doctor_id'] ?>">
                                <?= htmlspecialchars($doctor['name']) ?> (<?= htmlspecialchars($doctor['dept_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Time Slot</label>
                    <select name="appointment_time" required>
                        <option value="">-- Choose Time --</option>
                        <option value="09:00 AM">09:00 AM</option>
                        <option value="10:00 AM">10:00 AM</option>
                        <option value="11:00 AM">11:00 AM</option>
                        <option value="01:00 PM">01:00 PM</option>
                        <option value="02:00 PM">02:00 PM</option>
                        <option value="03:00 PM">03:00 PM</option>
                        <option value="04:00 PM">04:00 PM</option>
                    </select>
                </div>
                <button type="submit" name="book_appointment" class="btn">Confirm Booking</button>
            </form>
        </div>

        <!-- Section 2: Appointments Schedule Table -->
        <div class="card">
            <h2>Scheduled Appointments</h2>
            <?php if (empty($appointments)): ?>
                <p>No appointments booked yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr>
                                <td>#<?= $app['appointment_id'] ?></td>
                                <td><?= htmlspecialchars($app['patient_name']) ?></td>
                                <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($app['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($app['appointment_time']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($app['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>