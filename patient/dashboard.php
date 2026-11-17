
<?php

require_once '../config/db.php';
require_once '../includes/doctors.php';
require_once '../includes/patients.php';
require_once '../includes/appointments.php';

$message = "";
$message_type = "";

$doctor_id = isset($_GET['doctor_id'])
    ? (int)$_GET['doctor_id']
    : (int)($_POST['doctor_id'] ?? 0);

$selected_date = $_GET['date']
    ?? $_POST['appointment_date']
    ?? date('Y-m-d');

$doctor = getDoctorById($conn, $doctor_id);

// Validate selected date
$date_object = DateTime::createFromFormat('Y-m-d', $selected_date);

if (!$date_object || $date_object->format('Y-m-d') !== $selected_date) {
    $selected_date = date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {

    $patient_name = trim($_POST['patient_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');

    $day = date('w', strtotime($selected_date));

    if (!$doctor) {
        $message = "Doctor not found.";
        $message_type = "error";

    } elseif ($selected_date < date('Y-m-d')) {
        $message = "Please select a future date.";
        $message_type = "error";

    } elseif ($day == 0 || $day == 6) {
        $message = "Appointments are not allowed on weekends.";
        $message_type = "error";

    } elseif (
        empty($patient_name) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        empty($phone) ||
        empty($appointment_time)
    ) {
        $message = "Please complete all required fields.";
        $message_type = "error";

    } else {

        // Generate valid slots for this doctor's schedule
        $start = strtotime($doctor['work_start']);
        $end = strtotime($doctor['work_end']);

        $interval = (date('i', $start) == '30') ? 1800 : 3600;

        $valid_slots = [];

        while ($start < $end) {
            $valid_slots[] = date('h:i A', $start);
            $start += $interval;
        }

        if (!in_array($appointment_time, $valid_slots, true)) {

            $message = "Invalid appointment time.";
            $message_type = "error";

        } else {

            // Check whether the selected slot is already booked
            $booked_slots = getBookedSlots(
                $conn,
                $doctor_id,
                $selected_date
            );

            if (in_array($appointment_time, $booked_slots, true)) {

                $message = "Sorry, this time slot is already booked.";
                $message_type = "error";

            } else {

                // Find existing patient by email
                $patient = getPatientByEmail($conn, $email);

                if ($patient) {
                    $patient_id = $patient['patient_id'];
                } else {
                    $patient_id = createPatient(
                        $conn,
                        $patient_name,
                        $email,
                        $phone
                    );
                }

                if ($patient_id) {

                    $appointment_id = createAppointment(
                        $conn,
                        $patient_id,
                        $doctor_id,
                        $selected_date,
                        $appointment_time
                    );

                    if ($appointment_id) {
                        $message = "Appointment booked successfully! Your appointment ID is #" . $appointment_id;
                        $message_type = "success";
                    } else {
                        $message = "Booking failed. The selected time may already be booked.";
                        $message_type = "error";
                    }

                } else {
                    $message = "Unable to save patient information.";
                    $message_type = "error";
                }
            }
        }
    }
}

// Get booked slots for the selected doctor and date
$booked_slots = [];

if ($doctor) {
    $booked_slots = getBookedSlots(
        $conn,
        $doctor_id,
        $selected_date
    );
}

// Generate doctor's available time slots
$dynamic_slots = [];

if ($doctor) {

    $start = strtotime($doctor['work_start']);
    $end = strtotime($doctor['work_end']);

    $interval = (date('i', $start) == '30') ? 1800 : 3600;

    while ($start < $end) {
        $dynamic_slots[] = date('h:i A', $start);
        $start += $interval;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        Clinic <span>Appointment System</span>
    </div>

    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="my_appointments.php">My Appointments</a>
    </div>
</nav>

<main class="container">

    <?php if ($message): ?>
        <div class="alert <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$doctor): ?>

        <h2>Doctor not found.</h2>
        <a href="../index.php">Return to Homepage</a>

    <?php else: ?>

        <section class="booking-section">

            <div class="doctor-information">

                <h1><?= htmlspecialchars($doctor['name']) ?></h1>

                <p>
                    <strong>Specialty:</strong>
                    <?= htmlspecialchars($doctor['dept_name'] ?? 'General Medicine') ?>
                </p>

                <p>
                    <strong>Working Hours:</strong>
                    <?= htmlspecialchars($doctor['work_start']) ?>
                    -
                    <?= htmlspecialchars($doctor['work_end']) ?>
                </p>

                <a href="../index.php" class="btn-back">
                    Back to Doctors
                </a>

            </div>

            <div class="booking-interface">

                <h2>Select Appointment Date</h2>

                <form method="GET" action="dashboard.php">

                    <input
                        type="hidden"
                        name="doctor_id"
                        value="<?= $doctor_id ?>">

                    <input
                        type="date"
                        name="date"
                        value="<?= htmlspecialchars($selected_date) ?>"
                        min="<?= date('Y-m-d') ?>"
                        required>

                    <button type="submit" class="btn-book">
                        Check Availability
                    </button>

                </form>

                <h2>Available Time Slots</h2>

                <form method="POST" action="dashboard.php?doctor_id=<?= $doctor_id ?>&date=<?= urlencode($selected_date) ?>">

                    <input
                        type="hidden"
                        name="doctor_id"
                        value="<?= $doctor_id ?>">

                    <input
                        type="hidden"
                        name="appointment_date"
                        value="<?= htmlspecialchars($selected_date) ?>">

                    <div class="time-grid">

                        <?php if (empty($dynamic_slots)): ?>

                            <p>No available schedule for this doctor.</p>

                        <?php else: ?>

                            <?php foreach ($dynamic_slots as $slot): ?>

                                <?php
                                $is_booked = in_array($slot, $booked_slots, true);
                                ?>

                                <label class="time-slot-label <?= $is_booked ? 'is-disabled' : '' ?>">

                                    <input
                                        type="radio"
                                        name="appointment_time"
                                        value="<?= htmlspecialchars($slot) ?>"
                                        <?= $is_booked ? 'disabled' : '' ?>
                                        required>

                                    <span class="time-slot-box <?= $is_booked ? 'booked' : 'available' ?>">
                                        <?= htmlspecialchars($slot) ?>
                                        <br>
                                        <?= $is_booked ? 'Booked' : 'Available' ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                    <h2>Patient Information</h2>

                    <div class="patient-details">

                        <input
                            type="text"
                            name="patient_name"
                            placeholder="Full Name"
                            minlength="2"
                            required>

                        <input
                            type="email"
                            name="email"
                            placeholder="Email Address"
                            required>

                        <input
                            type="tel"
                            name="phone"
                            placeholder="Phone Number"
                            required>

                    </div>

                    <button
                        type="submit"
                        name="submit_booking"
                        class="btn-submit">
                        Confirm Appointment
                    </button>

                </form>

            </div>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
