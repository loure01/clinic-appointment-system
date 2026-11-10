<?php
// config and modules
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/patients.php';
require_once 'includes/appointments.php';
require_once 'includes/payments.php';
require_once 'includes/medical_records.php';

$message = "";
$message_type = "";

// handles form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_name = trim($_POST['patient_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $doctor_id    = (int)$_POST['doctor_id'];
    $date         = $_POST['appointment_date'];
    $time         = $_POST['appointment_time'];

    // Check date
    $day_of_week = date('w', strtotime($date));

    if ($day_of_week == 0 || $day_of_week == 6) {
        $message = "Booking failed: Appointments are not allowed on weekends (Saturday & Sunday). Please select a weekday.";
        $message_type = "error";
    } elseif (!empty($patient_name) && !empty($email) && !empty($phone) &&
        $doctor_id > 0 && !empty($date) && !empty($time)) {

        // Fetch existing patient by Email
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        if ($patient) {
            $patient_id = $patient['patient_id'];
        } else {
            // Register new patient
            $stmt = $conn->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $patient_name, $email, $phone);
            if ($stmt->execute()) {
                $patient_id = $stmt->insert_id;
            } else {
                $patient_id = false;
            }
        }

        // Create appointment
        if ($patient_id) {
            $appointment_id = createAppointment($conn, $patient_id, $doctor_id, $date, $time);
            if ($appointment_id) {
                $message = "Appointment booked successfully! Your Appointment ID is #" . $appointment_id . " (Save this ID to cancel if needed).";
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

// for cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $cancel_id    = (int)$_POST['cancel_appointment_id'];
    $cancel_email = trim($_POST['cancel_email']);

    if ($cancel_id > 0 && !empty($cancel_email)) {
        // verification for cancellation
        $stmt = $conn->prepare("
            SELECT a.appointment_id 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = ? AND p.email = ? AND a.status != 'Cancelled'
        ");
        $stmt->bind_param("is", $cancel_id, $cancel_email);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();

        if ($record) {
            // Soft cancel (update status to 'Cancelled')
            $update_stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
            $update_stmt->bind_param("i", $cancel_id);
            
            if ($update_stmt->execute()) {
                $message = "Appointment #" . $cancel_id . " has been successfully cancelled.";
                $message_type = "success";
            } else {
                $message = "Failed to cancel appointment. Please try again.";
                $message_type = "error";
            }
        } else {
            $message = "No active appointment found matching ID #" . $cancel_id . " and Email: " . htmlspecialchars($cancel_email);
            $message_type = "error";
        }
    } else {
        $message = "Please provide both Appointment ID and Email to cancel.";
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
        <!-- Column 1: Booking & Cancellation Forms -->
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
                        <?php foreach ($doctors as $doctor): ?>
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

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid var(--border);">

            <!-- Cancellation Section (Completes CRUD) -->
            <h2 style="font-size: 16px; margin-bottom: 12px;">Cancel Existing Booking</h2>
            <form action="index.php" method="POST" id="cancelForm">
                <div class="form-group">
                    <label>Appointment ID</label>
                    <input type="number" name="cancel_appointment_id" required placeholder="e.g. 12">
                </div>
                <div class="form-group">
                    <label>Your Email Address</label>
                    <input type="email" name="cancel_email" required placeholder="Email used when booking">
                </div>
                <button type="submit" name="cancel_appointment" class="btn" style="background: #b63f38;">Cancel Appointment</button>
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
                            <?php foreach ($doctors as $doc): ?>
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
                            <?php foreach ($appointments as $app): ?>
                                <?php 
                                    // Match doctor's department dynamically safely using doctor name or existing keys
                                    $dept = $app['dept_name'] ?? $app['department_name'] ?? $app['specialization'] ?? '';
                                    if (empty($dept)) {
                                        foreach ($doctors as $d) {
                                            $appDocName = $app['doctor_name'] ?? '';
                                            $appDocId   = $app['doctor_id'] ?? null;
                                            
                                            if (($appDocId !== null && $d['doctor_id'] == $appDocId) || 
                                                (!empty($appDocName) && strtolower($d['name']) === strtolower($appDocName))) {
                                                $dept = $d['dept_name'] ?? $d['department_name'] ?? 'General Medicine';
                                                break;
                                            }
                                        }
                                    }
                                    if (empty($dept)) {
                                        $dept = 'General Medicine';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($app['doctor_name'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($dept) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($app['appointment_time'] ?? '') ?></td>
                                    <td>
                                        <?php 
                                            $status = $app['status'] ?? 'Booked';
                                            $badgeStyle = ($status === 'Cancelled') ? 'background: #fff1f0; color: #b63f38;' : '';
                                        ?>
                                        <span class="badge" style="<?= $badgeStyle ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Clinic Hours & Emergency Notice Box -->
            <div class="clinic-notice-box">
                <div class="notice-section">
                    <h3>⏰ Clinic Operating Hours</h3>
                    <p><strong>Monday – Friday:</strong> 8:00 AM – 5:00 PM</p>
                    <p><strong>Saturday & Sunday:</strong> Closed for Routine Consultations</p>
                </div>
                
                <div class="notice-section emergency">
                    <h3>🚨 Medical Emergency Notice</h3>
                    <p>If you are experiencing a life-threatening medical emergency, please do not wait for an online booking confirmation. Visit the nearest Emergency Room or contact local emergency services immediately.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const existingAppointments = <?= json_encode($appointments); ?>;
const doctorSelect = document.getElementById('doctorSelect');
const dateInput = document.getElementById('dateInput');
const timeSelect = document.getElementById('timeSelect');
const noticeBox = document.getElementById('doctorScheduleNotice');
const shiftTime = document.getElementById('shiftTime');
const weekendNotice = document.getElementById('weekendNotice');
const submitBtn = document.getElementById('submitBtn');

function updateAvailableSlots() {
    const selectedDoctorId = doctorSelect.value;
    const selectedDate = dateInput.value;

    // Check for Weekend Selection (Sunday = 0, Saturday = 6)
    if (selectedDate) {
        const dateObj = new Date(selectedDate + 'T00:00:00');
        const dayOfWeek = dateObj.getDay();

        if (dayOfWeek === 0 || dayOfWeek === 6) {
            weekendNotice.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            weekendNotice.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    } else {
        weekendNotice.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }

    const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
    if (selectedDoctorId && selectedOption.dataset.start) {
        shiftTime.textContent = selectedOption.dataset.start + ' - ' + selectedOption.dataset.end;
        noticeBox.style.display = 'block';
    } else {
        noticeBox.style.display = 'none';
    }

    Array.from(timeSelect.options).forEach(option => {
        if (!option.value) return;

        const isBooked = existingAppointments.some(app => 
            (app.doctor_id == selectedDoctorId || (app.doctor_name && selectedOption && selectedOption.textContent.includes(app.doctor_name))) && 
            app.appointment_date === selectedDate && 
            app.appointment_time === option.value &&
            app.status !== 'Cancelled'
        );

        if (selectedDoctorId && selectedDate && isBooked) {
            option.disabled = true;
            option.textContent = option.value + " (BOOKED)";
            option.style.color = "#b63f38";
        } else {
            option.disabled = false;
            option.textContent = option.value;
            option.style.color = "";
        }
    });
}

doctorSelect.addEventListener('change', updateAvailableSlots);
dateInput.addEventListener('change', updateAvailableSlots);
</script>

</body>
</html>