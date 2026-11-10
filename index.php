<?php
// config and modules
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/patients.php';
require_once 'includes/appointments.php';

$message = "";
$message_type = "";

// 1. Handle the final booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $patient_name = trim($_POST['patient_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $doctor_id    = (int)$_POST['doctor_id'];
    $date         = $_POST['appointment_date'];
    $time         = $_POST['appointment_time'];

    $day_of_week = date('w', strtotime($date));
    
    if ($day_of_week == 0 || $day_of_week == 6) {
        $message = "Booking failed: Appointments are not allowed on weekends.";
        $message_type = "error";
    } elseif (!empty($patient_name) && !empty($email) && !empty($phone) && $doctor_id > 0 && !empty($date) && !empty($time)) {
        // Check if patient exists
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        if ($patient) {
            $patient_id = $patient['patient_id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $patient_name, $email, $phone);
            $stmt->execute();
            $patient_id = $stmt->insert_id;
        }

        // Save Appointment
        if ($patient_id) {
            $appointment_id = createAppointment($conn, $patient_id, $doctor_id, $date, $time);
            if ($appointment_id) {
                $message = "Appointment booked successfully! Your ID is #" . $appointment_id;
                $message_type = "success";
            } else {
                $message = "Booking failed. The selected time may already be booked.";
                $message_type = "error";
            }
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}

// 2. Handle Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $cancel_id    = (int)$_POST['cancel_appointment_id'];
    $cancel_email = trim($_POST['cancel_email']);

    if ($cancel_id > 0 && !empty($cancel_email)) {
        $stmt = $conn->prepare("SELECT a.appointment_id FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_id = ? AND p.email = ? AND a.status != 'Cancelled'");
        $stmt->bind_param("is", $cancel_id, $cancel_email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $update_stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
            $update_stmt->bind_param("i", $cancel_id);
            $update_stmt->execute();
            $message = "Appointment #" . $cancel_id . " has been cancelled.";
            $message_type = "success";
        } else {
            $message = "No active appointment found.";
            $message_type = "error";
        }
    }
}

// 3. Fetch Data
$selected_doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_doctor = null;

$departments  = getAllDepartments($conn);
$doctors      = getAllDoctors($conn);
$appointments = getAllAppointments($conn);

// Find the selected doctor
if ($selected_doctor_id > 0) {
    foreach ($doctors as $doc) {
        if ($doc['doctor_id'] == $selected_doctor_id) {
            $selected_doctor = $doc;
            break;
        }
    }
}


$booked_slots = [];
if ($selected_doctor) {
    foreach ($appointments as $app) {
        $app_doc_id = $app['doctor_id'] ?? null;
        $app_doc_name = $app['doctor_name'] ?? '';
        $is_same_doctor = ($app_doc_id == $selected_doctor_id) || (empty($app_doc_id) && $app_doc_name === $selected_doctor['name']);

        if ($is_same_doctor && $app['appointment_date'] === $selected_date && $app['status'] !== 'Cancelled') {
            $booked_slots[] = $app['appointment_time'];
        }
    }
}

// 5. GENERATE DYNAMIC SLOTS BASED ON INDIVIDUAL DOCTOR'S SHIFT based sa database
$dynamic_slots = [];
if ($selected_doctor) {
    $start_str = $selected_doctor['work_start'] ?? '09:00 AM';
    $end_str   = $selected_doctor['work_end'] ?? '05:00 PM';

    
    $start = strtotime($start_str);
    $end   = strtotime($end_str);

    // Check if the doctor starts at :30 example 8:30
    $minutes = date('i', $start);
    $interval = ($minutes == '30') ? 1800 : 3600; 

   
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
    <title>Clinic Appointment System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none"  stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Clinic<span>Appointment System</span>
    </div>
    <div class="nav-links">
        <a href="index.php">Home</a>
    </div>
</nav>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($selected_doctor): ?>
        <div class="detail-layout">
            <div class="profile-sidebar">
                <div class="profile-image">
                    <img src="images/logo.jpg">
                </div>
                <div class="profile-header">
                    <h1><?= htmlspecialchars($selected_doctor['name']) ?></h1>
                </div>
                <div class="profile-meta">
                    <p><strong>Specialty:</strong> <?= htmlspecialchars($selected_doctor['dept_name'] ?? 'General Medicine') ?></p>
                    <p><strong>Shift:</strong> <?= htmlspecialchars($selected_doctor['work_start'] ?? '09:00 AM') ?> - <?= htmlspecialchars($selected_doctor['work_end'] ?? '05:00 PM') ?></p>
                </div>
                <a href="index.php" class="btn-back">← Back</a>
            </div>

            <div class="booking-interface">
                <div class="date-header">
                    <h3>📅 Select Date</h3>
                    <form method="GET" action="index.php">
                        <input type="hidden" name="doctor_id" value="<?= $selected_doctor['doctor_id'] ?>">
                        <input type="date" name="date" value="<?= $selected_date ?>" min="<?= date('Y-m-d') ?>" onchange="this.form.submit();">
                    </form>
                </div>

                <form action="index.php?doctor_id=<?= $selected_doctor['doctor_id'] ?>" method="POST">
                    <input type="hidden" name="doctor_id" value="<?= $selected_doctor['doctor_id'] ?>">
                    <input type="hidden" name="appointment_date" value="<?= $selected_date ?>">

                    <div class="time-grid-wrapper">
                        <table class="time-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Availability</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($dynamic_slots)) {
                                    echo "<tr><td colspan='2' style='padding: 20px; text-align: center; color: #6b7280;'>No shifts available for this doctor today.</td></tr>";
                                }
                                
                                foreach ($dynamic_slots as $slot): 
                                    $is_booked = in_array($slot, $booked_slots);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($slot) ?></td>
                                    <td>
                                        <label class="time-slot-label <?= $is_booked ? 'is-disabled' : '' ?>">
                                            <input type="radio" name="appointment_time" value="<?= htmlspecialchars($slot) ?>" <?= $is_booked ? 'disabled' : 'required' ?>>
                                            <span class="time-slot-box <?= $is_booked ? 'booked' : '' ?>">
                                                <?= $is_booked ? 'Booked' : 'Available' ?>
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="patient-details">
                        <h3>Patient Information</h3>
                        <div class="input-row">
                            <input type="text" name="patient_name" required placeholder="Full Name" minlength="5">
                            <input type="email" name="email" required placeholder="Email Address">
                        </div>
                        <div class="input-row">
                            <input type="text" name="phone" required placeholder="Phone Number">
                        </div>
                    </div>

                    <button type="button" class="btn-submit" onclick="event.preventDefault(); if(this.form.reportValidity()) { document.getElementById('confirmationModal').style.display='flex'; }">Confirm Booking</button>
                    
                    <div id="confirmationModal" class="modal-overlay">
                        <div class="modal-card">
                            <h2>Are you sure?</h2>
                            <p>Do you want to confirm this booking or cancel?</p>
                            <div class="modal-buttons">
                                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('confirmationModal').style.display='none'">Cancel</button>
                                <button type="submit" name="submit_booking" class="btn-modal-confirm">Yes, Confirm</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="section-title">Recommended Doctors</div>
        <div class="grid-cards">
            <?php foreach ($doctors as $doc): ?>
                <div class="doctor-card">
                    <div class="card-content">
                        <div class="doctor-name"><?= htmlspecialchars($doc['name']) ?></div>
                        <div class="doctor-specialty"><?= htmlspecialchars($doc['dept_name'] ?? 'General Medicine') ?></div>
                    </div>
                    <div class="card-footer">
                        <a href="index.php?doctor_id=<?= $doc['doctor_id'] ?>" class="btn-book">Book Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        
    <?php endif; ?>
</div>

</body>
</html>