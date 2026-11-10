<?php
// backend modules
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/patients.php';
require_once 'includes/appointments.php';
require_once 'includes/payments.php';
require_once 'includes/medical_records.php';

$message = "";
$message_type = "";

// form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_name = trim($_POST['patient_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $doctor_id    = (int)$_POST['doctor_id'];
    $date         =$_POST['appointment_date'];
    $time         =$_POST['appointment_time'];

    
    $day_of_week = date('w', strtotime($date));

    if ($day_of_week == 0 || $day_of_week == 6) {$message = "Booking failed: Appointments are not allowed on weekends (Saturday & Sunday). Please select a weekday.";
        $message_type = "error";
    } elseif (!empty($patient_name) && !empty($email) && !empty($phone) &&$doctor_id > 0 && !empty($date) && !empty($time)) {

        // Fetch existing patient by Email
        $stmt =$conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);$stmt->execute();
        $patient =$stmt->get_result()->fetch_assoc();

        if ($patient) {
            $patient_id =$patient['patient_id'];
        } else {
            // Register new patient
            $stmt =$conn->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $patient_name, $email,$phone);
            if ($stmt->execute()) {
                $patient_id =$stmt->insert_id;
            } else {
                $patient_id = false;
            }
        }

        // Create appointment
        if ($patient_id) {
            $appointment_id = createAppointment($conn, $patient_id,$doctor_id, $date,$time);
            if ($appointment_id) {$message = "Appointment booked successfully! Appointment ID: #" . $appointment_id;
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

// 2B. Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $cancel_id    = (int)$_POST['cancel_appointment_id']; // Cancellation
    $cancel_email = trim($_POST['cancel_email']); // Cancellation

    if ($cancel_id > 0 && !empty($cancel_email)) {
        // Cancellation
        $stmt =$conn->prepare("
            SELECT a.appointment_id 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = ? AND p.email = ? AND a.status != 'Cancelled'
        ");
        $stmt->bind_param("is", $cancel_id, $cancel_email);$stmt->execute();
        $record =$stmt->get_result()->fetch_assoc();

        if ($record) {
            // Cancellation
            $update_stmt =$conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
            $update_stmt->bind_param("i", $cancel_id);
            
            if ($update_stmt->execute()) {
                $message = "Appointment #" . $cancel_id . " has been successfully cancelled.";
                $message_type = "success";
            } else {
                $message = "Failed to cancel appointment. Please try again.";
                $message_type = "error";
            }
        } else {
            $message = "Security Verification Failed: Email does not match the booking record.";
            $message_type = "error";
        }
    } else {
        $message = "Please provide your email to confirm cancellation.";
        $message_type = "error";
    }
}

// 3. Fetch Data for Page Display
$departments  = getAllDepartments($conn);
$doctors      = getAllDoctors($conn);
$appointments = getAllAppointments($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel-sm {
            background: #fff1f0;
            color: #b63f38;
            border: 1px solid #f2d8d5;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-cancel-sm:hover {
            background: #b63f38;
            color: #ffffff;
        }
        /* Modal Popup Styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-box {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .modal-box h3 {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        .modal-box p {
            font-size: 12px;
            color: #555;
            margin-bottom: 14px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }
    </style>
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
        <!-- Column 1: Booking Form -->
        <div class="card">
            <h2>Book Appointment</h2>
            <form action="index.php" method="POST" id="bookingForm">
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
                    <select name="doctor_id" id="doctorSelect" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as$doctor): ?>
                            <option value="<?= $doctor['doctor_id'] ?>" 
                                    data-start="<?= htmlspecialchars($doctor['work_start'] ?? '') ?>" 
                                    data-end="<?= htmlspecialchars($doctor['work_end'] ?? '') ?>">
                                <?= htmlspecialchars($doctor['name']) ?> (<?= htmlspecialchars($doctor['dept_name'] ?? $doctor['department_name'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="doctorScheduleNotice" style="display:none; background:#eaf4ed; color:#1b5438; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; border:1px solid #d5ead9;">
                    <strong>Doctor Shift:</strong> <span id="shiftTime"></span>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="appointment_date" id="dateInput" required min="<?= date('Y-m-d') ?>">
                </div>

                <!-- Instant Weekend Warning Notice -->
                <div id="weekendNotice" style="display:none; background:#fff1f0; color:#b63f38; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; border:1px solid #f2d8d5;">
                    <strong>⚠️ Notice:</strong> Bookings are not allowed on weekends (Saturday & Sunday). Please choose a weekday.
                </div>

                <div class="form-group">
                    <label>Time Slot</label>
                    <select name="appointment_time" id="timeSelect" required>
                        <option value="">-- Choose Time --</option>
                        <option value="08:00 AM">08:00 AM</option>
                        <option value="08:30 AM">08:30 AM</option>
                        <option value="09:00 AM">09:00 AM</option>
                        <option value="10:00 AM">10:00 AM</option>
                        <option value="11:00 AM">11:00 AM</option>
                        <option value="01:00 PM">01:00 PM</option>
                        <option value="02:00 PM">02:00 PM</option>
                        <option value="03:00 PM">03:00 PM</option>
                        <option value="04:00 PM">04:00 PM</option>
                        <option value="04:30 PM">04:30 PM</option>
                        <option value="05:00 PM">05:00 PM</option>
                    </select>
                </div>
                <button type="submit" name="book_appointment" id="submitBtn" class="btn">Confirm Booking</button>
            </form>
        </div>

        <!-- Column 2: Doctor Availability Schedule -->
        <div class="card">
            <h2>Doctor Availability</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Shift Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doctors)): ?>
                            <tr><td colspan="3">No doctors registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($doctors as$doc): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($doc['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($doc['dept_name'] ?? $doc['department_name'] ?? 'General') ?></td>
                                    <td>
                                        <span class="badge">
                                            <?= htmlspecialchars($doc['work_start'] ?? '08:00 AM') ?> - <?= htmlspecialchars($doc['work_end'] ?? '05:00 PM') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Column 3: Scheduled Appointments Table & Patient Info -->
        <div class="card">
            <h2>Scheduled Appointments</h2>
            <div class="table-responsive">
                <?php if (empty($appointments)): ?>
                    <p>No appointments booked yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialist / Department</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as$app): ?>
                                <?php 
                                    // Match doctor's department dynamically safely using doctor name or existing keys
                                    $dept =$app['dept_name'] ?? $app['department_name'] ?? $app['specialization'] ?? '';
                                    if (empty($dept)) {
                                        foreach ($doctors as$d) {
                                            $appDocName =$app['doctor_name'] ?? '';
                                            $appDocId   =$app['doctor_id'] ?? null;
                                            
                                            if (($appDocId !== null && $d['doctor_id'] ==$appDocId) || 
                                                (!empty($appDocName) && strtolower($d['name']) === strtolower($appDocName))) {$dept = $d['dept_name'] ?? $d['department_name'] ?? 'General Medicine';
                                                break;
                                            }
                                        }
                                    }
                                    if (empty($dept)) {$dept = 'General Medicine';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($app['doctor_name'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($dept) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($app['appointment_time'] ?? '') ?></td>
                                    <td>
                                        <div class="status-cell">
                                            <?php 
                                                $status =$app['status'] ?? 'Booked';
                                                $badgeStyle = ($status === 'Cancelled') ? 'background: #fff1f0; color: #b63f38;' : ''; // Cancellation
                                            ?>
                                            <span class="badge" style="<?= $badgeStyle ?>"><?= htmlspecialchars($status) ?></span>
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

// 2A. Handle Form Submission (Appointment Booking - CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_name = trim($_POST['patient_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $doctor_id    = (int)$_POST['doctor_id'];
    $date         =$_POST['appointment_date'];
    $time         =$_POST['appointment_time'];

    // Check if selected date falls on a weekend (Sunday = 0, Saturday = 6)
    $day_of_week = date('w', strtotime($date));

    if ($day_of_week == 0 || $day_of_week == 6) {$message = "Booking failed: Appointments are not allowed on weekends (Saturday & Sunday). Please select a weekday.";
        $message_type = "error";
    } elseif (!empty($patient_name) && !empty($email) && !empty($phone) &&$doctor_id > 0 && !empty($date) && !empty($time)) {

        // Fetch existing patient by Email
        $stmt =$conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);$stmt->execute();
        $patient =$stmt->get_result()->fetch_assoc();

        if ($patient) {
            $patient_id =$patient['patient_id'];
        } else {
            // Register new patient
            $stmt =$conn->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $patient_name, $email,$phone);
            if ($stmt->execute()) {
                $patient_id =$stmt->insert_id;
            } else {
                $patient_id = false;
            }
        }

        // Create appointment
        if ($patient_id) {
            $appointment_id = createAppointment($conn, $patient_id,$doctor_id, $date,$time);
            if ($appointment_id) {$message = "Appointment booked successfully! Appointment ID: #" . $appointment_id;
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

// 2B. Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $cancel_id    = (int)$_POST['cancel_appointment_id']; // Cancellation
    $cancel_email = trim($_POST['cancel_email']); // Cancellation

    if ($cancel_id > 0 && !empty($cancel_email)) {
        // Cancellation
        $stmt =$conn->prepare("
            SELECT a.appointment_id 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = ? AND p.email = ? AND a.status != 'Cancelled'
        ");
        $stmt->bind_param("is", $cancel_id, $cancel_email);$stmt->execute();
        $record =$stmt->get_result()->fetch_assoc();

        if ($record) {
            // Cancellation
            $update_stmt =$conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
            $update_stmt->bind_param("i", $cancel_id);
            
            if ($update_stmt->execute()) {
                $message = "Appointment #" . $cancel_id . " has been successfully cancelled.";
                $message_type = "success";
            } else {
                $message = "Failed to cancel appointment. Please try again.";
                $message_type = "error";
            }
        } else {
            $message = "Security Verification Failed: Email does not match the booking record.";
            $message_type = "error";
        }
    } else {
        $message = "Please provide your email to confirm cancellation.";
        $message_type = "error";
    }
}

// 3. Fetch Data for Page Display
$departments  = getAllDepartments($conn);
$doctors      = getAllDoctors($conn);
$appointments = getAllAppointments($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel-sm {
            background: #fff1f0;
            color: #b63f38;
            border: 1px solid #f2d8d5;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-cancel-sm:hover {
            background: #b63f38;
            color: #ffffff;
        }
        /* Modal Popup Styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-box {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .modal-box h3 {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        .modal-box p {
            font-size: 12px;
            color: #555;
            margin-bottom: 14px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }
    </style>
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
        <!-- Column 1: Booking Form -->
        <div class="card">
            <h2>Book Appointment</h2>
            <form action="index.php" method="POST" id="bookingForm">
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
                    <select name="doctor_id" id="doctorSelect" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as$doctor): ?>
                            <option value="<?= $doctor['doctor_id'] ?>" 
                                    data-start="<?= htmlspecialchars($doctor['work_start'] ?? '') ?>" 
                                    data-end="<?= htmlspecialchars($doctor['work_end'] ?? '') ?>">
                                <?= htmlspecialchars($doctor['name']) ?> (<?= htmlspecialchars($doctor['dept_name'] ?? $doctor['department_name'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="doctorScheduleNotice" style="display:none; background:#eaf4ed; color:#1b5438; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; border:1px solid #d5ead9;">
                    <strong>Doctor Shift:</strong> <span id="shiftTime"></span>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="appointment_date" id="dateInput" required min="<?= date('Y-m-d') ?>">
                </div>

                <!-- Instant Weekend Warning Notice -->
                <div id="weekendNotice" style="display:none; background:#fff1f0; color:#b63f38; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; border:1px solid #f2d8d5;">
                    <strong>⚠️ Notice:</strong> Bookings are not allowed on weekends (Saturday & Sunday). Please choose a weekday.
                </div>

                <div class="form-group">
                    <label>Time Slot</label>
                    <select name="appointment_time" id="timeSelect" required>
                        <option value="">-- Choose Time --</option>
                        <option value="08:00 AM">08:00 AM</option>
                        <option value="08:30 AM">08:30 AM</option>
                        <option value="09:00 AM">09:00 AM</option>
                        <option value="10:00 AM">10:00 AM</option>
                        <option value="11:00 AM">11:00 AM</option>
                        <option value="01:00 PM">01:00 PM</option>
                        <option value="02:00 PM">02:00 PM</option>
                        <option value="03:00 PM">03:00 PM</option>
                        <option value="04:00 PM">04:00 PM</option>
                        <option value="04:30 PM">04:30 PM</option>
                        <option value="05:00 PM">05:00 PM</option>
                    </select>
                </div>
                <button type="submit" name="book_appointment" id="submitBtn" class="btn">Confirm Booking</button>
            </form>
        </div>

        <!-- Column 2: Doctor Availability Schedule -->
        <div class="card">
            <h2>Doctor Availability</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Shift Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doctors)): ?>
                            <tr><td colspan="3">No doctors registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($doctors as$doc): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($doc['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($doc['dept_name'] ?? $doc['department_name'] ?? 'General') ?></td>
                                    <td>
                                        <span class="badge">
                                            <?= htmlspecialchars($doc['work_start'] ?? '08:00 AM') ?> - <?= htmlspecialchars($doc['work_end'] ?? '05:00 PM') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Column 3: Scheduled Appointments Table & Patient Info -->
        <div class="card">
            <h2>Scheduled Appointments</h2>
            <div class="table-responsive">
                <?php if (empty($appointments)): ?>
                    <p>No appointments booked yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialist / Department</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as$app): ?>
                                <?php 
                                    // Match doctor's department dynamically safely using doctor name or existing keys
                                    $dept =$app['dept_name'] ?? $app['department_name'] ?? $app['specialization'] ?? '';
                                    if (empty($dept)) {
                                        foreach ($doctors as$d) {
                                            $appDocName =$app['doctor_name'] ?? '';
                                            $appDocId   =$app['doctor_id'] ?? null;
                                            
                                            if (($appDocId !== null && $d['doctor_id'] ==$appDocId) || 
                                                (!empty($appDocName) && strtolower($d['name']) === strtolower($appDocName))) {$dept = $d['dept_name'] ?? $d['department_name'] ?? 'General Medicine';
                                                break;
                                            }
                                        }
                                    }
                                    if (empty($dept)) {$dept = 'General Medicine';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($app['doctor_name'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($dept) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($app['appointment_time'] ?? '') ?></td>
                                    <td>
                                        <div class="status-cell">
                                            <?php 
                                                $status =$app['status'] ?? 'Booked';
                                                $badgeStyle = ($status === 'Cancelled') ? 'background: #fff1f0; color: #b63f38;' : ''; // Cancellation
                                            ?>
                                            <span class="badge" style="<?= $badgeStyle ?>"><?= htmlspecialchars($status) ?></span>
                                            
                                            <?php if ($status !== 'Cancelled' && isset($app['appointment_id'])): ?>
                                                <button type
                                            <?php if ($status !== 'Cancelled' && isset($app['appointment_id'])): ?>
                                                <button type