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

        //Pag Fetch sa existing patient gamit Email Address
        $stmt = $conn->prepare(
            "SELECT patient_id FROM patients WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $patient = $stmt->get_result()->fetch_assoc();

        if ($patient) {

            //Pag fetch sa existing patient gamit patient_id if wala mag register og bag-o
            $patient_id = $patient['patient_id'];

        } else {

            //Register new patient
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
        // Checking Booking result if success ba or error
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
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['doctor_id'] ?>" 
                                data-start="<?= htmlspecialchars($doctor['work_start'] ?? '') ?>" 
                                data-end="<?= htmlspecialchars($doctor['work_end'] ?? '') ?>">
                            <?= htmlspecialchars($doctor['name']) ?> (<?= htmlspecialchars($doctor['dept_name'] ?? $doctor['department_name'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="doctorScheduleNotice" style="display:none; background:#e8f4fd; color:#1a5276; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px;">
                <strong>Doctor Shift:</strong> <span id="shiftTime"></span>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="appointment_date" id="dateInput" required min="<?= date('Y-m-d') ?>">
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
            <button type="submit" name="book_appointment" class="btn">Confirm Booking</button>
        </form>
    </div>

    <!-- Column 2: Calendar & Upcoming Card -->
    <div class="card calendar-card">
        <div class="calendar-header">
            <h2 id="calendarMonthYear">Calendar</h2>
            <div class="calendar-nav">
                <button type="button" id="prevMonth">&lt;</button>
                <button type="button" id="nextMonth">&gt;</button>
            </div>
        </div>

        <div class="calendar-weekdays">
            <div>SUN</div><div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div>
        </div>
        <div class="calendar-days" id="calendarDays"></div>

        <div class="upcoming-section">
            <div class="upcoming-header">
                <h3>Upcoming</h3>
            </div>
            <div id="upcomingList" class="upcoming-list">
                <!-- Populated dynamically by JS -->
            </div>
        </div>
    </div>

    <!-- Column 3: Scheduled Appointments Table -->
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

<script>
const existingAppointments = <?= json_encode($appointments); ?>;

const doctorSelect = document.getElementById('doctorSelect');
const dateInput = document.getElementById('dateInput');
const timeSelect = document.getElementById('timeSelect');
const noticeBox = document.getElementById('doctorScheduleNotice');
const shiftTime = document.getElementById('shiftTime');

let currentDate = new Date();

// Render Calendar Widget
function renderCalendar() {
    const monthYearText = document.getElementById('calendarMonthYear');
    const calendarDays = document.getElementById('calendarDays');

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];

    monthYearText.textContent = `${monthNames[month]} ${year}`;
    calendarDays.innerHTML = "";

    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    // Fill blank padding days
    for (let i = 0; i < firstDayIndex; i++) {
        const emptyDiv = document.createElement('div');
        emptyDiv.classList.add('calendar-day', 'empty');
        calendarDays.appendChild(emptyDiv);
    }

    // Fill-up sa month
    for (let day = 1; day <= totalDays; day++) {
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('calendar-day');
        dayDiv.textContent = day;

        // gi format into string ang YYYY-MM-DD
        const formattedMonth = String(month + 1).padStart(2, '0');
        const formattedDay = String(day).padStart(2, '0');
        const dateStr = `${year}-${formattedMonth}-${formattedDay}`;

        // Add red dot if any appointment exists on this day
        const hasBooking = existingAppointments.some(app => app.appointment_date === dateStr && app.status !== 'Cancelled');
        if (hasBooking) {
            dayDiv.classList.add('has-booking');
        }

        // Highlight selected date matching dateInput
        if (dateInput.value === dateStr) {
            dayDiv.classList.add('selected');
        }

        // clicker sa day
        dayDiv.addEventListener('click', () => {
            dateInput.value = dateStr;
            updateAvailableSlots();
            renderCalendar();
        });

        calendarDays.appendChild(dayDiv);
    }
}

// ma render ang upcoming list
function renderUpcoming() {
    const upcomingList = document.getElementById('upcomingList');
    upcomingList.innerHTML = "";

    if (existingAppointments.length === 0) {
        upcomingList.innerHTML = `<p style="font-size:12px; color:#888;">No upcoming appointments.</p>`;
        return;
    }

    // appointment
    const upcoming = existingAppointments.slice(0, 3);
    upcoming.forEach(app => {
        const initial = app.patient_name ? app.patient_name.charAt(0).toUpperCase() : 'A';
        const item = document.createElement('div');
        item.classList.add('upcoming-item');
        item.innerHTML = `
            <div class="upcoming-avatar">${initial}</div>
            <div class="upcoming-info">
                <div class="upcoming-title">${app.patient_name} with ${app.doctor_name}</div>
                <div class="upcoming-time">${app.appointment_date} | ${app.appointment_time}</div>
            </div>
        `;
        upcomingList.appendChild(item);
    });
}

function updateAvailableSlots() {
    const selectedDoctorId = doctorSelect.value;
    const selectedDate = dateInput.value;

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
            app.doctor_id == selectedDoctorId && 
            app.appointment_date === selectedDate && 
            app.appointment_time === option.value &&
            app.status !== 'Cancelled'
        );

        if (selectedDoctorId && selectedDate && isBooked) {
            option.disabled = true;
            option.textContent = option.value + " (BOOKED)";
            option.style.color = "#a00";
        } else {
            option.disabled = false;
            option.textContent = option.value;
            option.style.color = "";
        }
    });
}

// diri ma control ang calendar
document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
});
document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
});

doctorSelect.addEventListener('change', updateAvailableSlots);
dateInput.addEventListener('change', () => {
    updateAvailableSlots();
    renderCalendar();
});

// Initial Setup
renderCalendar();
renderUpcoming();
</script>

</body>
</html>
</body>
</html>