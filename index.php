<?php
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/patients.php';
require_once 'includes/appointments.php';
require_once 'includes/mailer.php';

$booking_error = '';
$booked_ticket = null;
$email_sent    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_modal_booking'])) {
    $name      = trim($_POST['patient_name'] ?? '');
    $email     = trim($_POST['patient_email'] ?? '');
    $phone     = trim($_POST['patient_phone'] ?? '');
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $date      = $_POST['appointment_date'] ?? '';
    $time      = trim($_POST['appointment_time'] ?? '');
    $amount    = 500.00;

    $day_of_week = date('N', strtotime($date));
    if ($day_of_week >= 6) {
        $booking_error = "Clinic is closed on Saturdays and Sundays.";
    } elseif ($name === '' || $email === '' || $phone === '' || $time === '') {
        $booking_error = "Please complete all patient information fields.";
    } else {
        $current_booked = getBookedSlots($conn, $doctor_id, $date);
        if (in_array($time, $current_booked)) {
            $booking_error = "Sorry, that time slot was just booked by someone else.";
        } else {
            $patient = getPatientByEmail($conn, $email);
            $patient_id = $patient ? $patient['patient_id'] : createPatient($conn, $name, $email, $phone);

            if ($patient_id) {
                $appt_id = createAppointment($conn, $patient_id, $doctor_id, $date, $time);
                if ($appt_id) {
                    $pay_stmt = $conn->prepare("INSERT INTO payments (appointment_id, amount, payment_status) VALUES (?, ?, 'Paid')");
                    $pay_stmt->bind_param("id", $appt_id, $amount);
                    $pay_stmt->execute();

                    $doc_info = getDoctorById($conn, $doctor_id);
                    $booked_ticket = [
                        'id'      => $appt_id,
                        'patient' => $name,
                        'email'   => $email,
                        'doctor'  => $doc_info['name'],
                        'dept'    => $doc_info['dept_name'],
                        'date'    => $date,
                        'time'    => $time,
                        'amount'  => $amount
                    ];

                    $email_sent = sendClinicPassEmail($booked_ticket);
                } else {
                    $booking_error = "Unable to complete booking. Please try again.";
                }
            }
        }
    }
}

$departments = getAllDepartments($conn);
$doctors     = getAllDoctors($conn);
$bookedMap   = getUpcomingBookedMap($conn);

$page_title      = "Clinic Appointment System - Live Schedule";
$load_booking_js = true;

require_once 'includes/header.php';
?>

<main class="container matrix-page-container" 
      id="bookingApp"
      data-doctors='<?= htmlspecialchars(json_encode($doctors), ENT_QUOTES, "UTF-8") ?>'
      data-booked='<?= htmlspecialchars(json_encode($bookedMap), ENT_QUOTES, "UTF-8") ?>'
      data-today="<?= date('Y-m-d') ?>">

    <?php if ($booking_error): ?>
        <div class="alert error"><?= htmlspecialchars($booking_error) ?></div>
    <?php endif; ?>

    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-50 via-emerald-50/40 to-teal-50/70 border border-slate-200/80 shadow-sm mb-6 p-6 md:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-100/80 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    Trusted Care. Better Health.
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                    Your Health<br>
                    <span class="text-emerald-600">Our Priority</span>
                </h1>

                <p class="text-slate-600 text-sm sm:text-base max-w-lg leading-relaxed">
                    Compassionate care, advanced technology, and experienced specialists — all for a healthier you.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <a href="#live-schedule-section" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition">
                        Book Appointment
                    </a>
                    <a href="#live-schedule-section" onclick="document.getElementById('deptFilterSelect').focus();" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm border border-slate-300 transition">
                        Our Services
                    </a>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-200/70 max-w-md">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm shrink-0">
                            🩺
                        </div>
                        <div>
                            <div class="text-base font-extrabold text-slate-900 leading-none">15+</div>
                            <div class="text-xs text-slate-500 mt-0.5">Expert Doctors</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm shrink-0">
                            ❤️
                        </div>
                        <div>
                            <div class="text-base font-extrabold text-slate-900 leading-none">10k+</div>
                            <div class="text-xs text-slate-500 mt-0.5">Happy Patients</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm shrink-0">
                            🏥
                        </div>
                        <div>
                            <div class="text-base font-extrabold text-slate-900 leading-none">25+</div>
                            <div class="text-xs text-slate-500 mt-0.5">Specialties</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 relative flex items-center justify-center">
                <div class="relative w-full max-w-sm mx-auto">
                    <div class="absolute right-0 bottom-0 w-4/5 h-4/5 bg-emerald-600 rounded-tl-3xl rounded-br-2xl -z-0"></div>
                    <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=600&q=80" 
                         alt="Clinic Specialist" 
                         class="relative z-10 w-full h-72 sm:h-80 object-cover object-top rounded-2xl shadow-lg border-4 border-white">

                    <div class="absolute -bottom-3 -right-2 sm:-right-4 z-20 bg-white rounded-xl shadow-lg border border-slate-100 px-4 py-3 text-center">
                        <div class="w-8 h-8 mx-auto mb-1 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            🎧
                        </div>
                        <div class="text-base font-extrabold text-slate-900 leading-tight">24/7</div>
                        <div class="text-[11px] text-slate-500 leading-tight">Emergency<br>Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="live-schedule-section" class="panel" style="padding: 1.15rem 1.35rem; margin-bottom: 4.5rem;">
        <div class="matrix-topbar">
            <div class="date-title-group">
                <span style="font-size: 1.2rem;">📅</span>
                <h1 id="matrixDateHeading" style="font-size: 1.3rem; color: #0f172a; margin: 0;">Tue, Nov 17</h1>
                <span id="todayBadge" class="today-pill">Today</span>
            </div>

            <div class="slot-legend" style="margin: 0;">
                <span><i class="box open"></i> Available</span>
                <span><i class="box booked"></i> Booked</span>
                <span><i class="box selected"></i> Selected</span>
                <span><i class="box offduty"></i> Off Duty</span>
            </div>

            <div class="matrix-controls">
                <select id="deptFilterSelect" style="margin:0; width:auto; padding:0.4rem 0.75rem;">
                    <option value="all">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="nav-btn-group">
                    <button type="button" id="prevDayBtn" class="icon-nav-btn" title="Previous Weekday">&lsaquo;</button>
                    <label class="icon-nav-btn calendar-trigger" title="Select Date">
                        📆
                        <input type="date" id="matrixDatePicker" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                    </label>
                    <button type="button" id="nextDayBtn" class="icon-nav-btn" title="Next Weekday">&rsaquo;</button>
                </div>
            </div>
        </div>

        <div class="matrix-wrapper" style="margin-top: 0.85rem;">
            <table class="schedule-matrix" id="scheduleMatrix">
                <thead id="matrixHead"></thead>
                <tbody id="matrixBody"></tbody>
            </table>
        </div>
    </div>

    <div class="sticky-bottom-bar" id="ticketBar" style="display: none;">
        <div class="sticky-bar-inner">
            <span id="ticketDetails" style="display: none;"></span>
            <button type="button" id="openModalBtn" class="btn-book" style="border-radius: 999px; padding: 1.1rem 4.5rem; font-size: 1.35rem; font-weight: 800; letter-spacing: 1px; box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);">
                BOOK NOW
            </button>
        </div>
    </div>

    <div class="modal-backdrop" id="bookingModal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <span class="modal-step-tag" id="modalStepTag">STEP 1 OF 2</span>
                    <h2 id="modalTitle" style="font-size: 1.35rem; color: #0f172a;">Appointment Details</h2>
                    <p id="modalSubtitle" style="font-size: 0.85rem; color: #64748b;">Review your consultation schedule and enter patient details</p>
                </div>
                <button type="button" class="modal-close-btn" id="closeModalBtn">&times;</button>
            </div>

            <div class="modal-progress-track">
                <div class="modal-progress-fill" id="modalProgressFill" style="width: 50%;"></div>
            </div>

            <form method="POST" action="index.php" id="modalBookingForm">
                <input type="hidden" name="doctor_id" id="modalDoctorId">
                <input type="hidden" name="appointment_date" id="modalDate">
                <input type="hidden" name="appointment_time" id="modalTime">

                <div id="modalStep1" class="modal-body">
                    <div class="summary-box">
                        <div class="summary-section-title">CONSULTATION SUMMARY</div>
                        <div class="summary-row">
                            <span>Specialist</span>
                            <strong id="sumDoctor">—</strong>
                        </div>
                        <div class="summary-row">
                            <span>Department</span>
                            <span class="badge" id="sumDept">—</span>
                        </div>
                        <div class="summary-row">
                            <span>Date</span>
                            <strong id="sumDate">—</strong>
                        </div>
                        <div class="summary-row">
                            <span>Time Slot</span>
                            <div>
                                <strong id="sumTime">—</strong>
                                <span class="duration-pill">1 hr</span>
                            </div>
                        </div>
                        <div class="summary-total-row">
                            <span>Consultation Fee</span>
                            <strong style="font-size: 1.2rem; color: #0f172a;">₱500</strong>
                        </div>
                    </div>

                    <div class="summary-box" style="margin-top: 1rem;">
                        <div class="summary-section-title">PATIENT INFORMATION</div>
                        <label>Full Name</label>
                        <input type="text" name="patient_name" id="inputPatientName" placeholder="e.g., Juan Dela Cruz" required>

                        <label>Email Address</label>
                        <input type="email" name="patient_email" id="inputPatientEmail" placeholder="e.g., juan@email.com" required>

                        <label>Mobile Number</label>
                        <input type="tel" name="patient_phone" id="inputPatientPhone" placeholder="e.g., 09171234567" required style="margin-bottom: 0;">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-outline" id="cancelModalBtn">Cancel</button>
                        <button type="button" class="btn-navy" id="toStep2Btn">Next &rsaquo;</button>
                    </div>
                </div>

                <div id="modalStep2" class="modal-body" style="display: none;">
                    <div class="summary-box" style="background: #f8fafc;">
                        <div class="summary-section-title">CONFIRMATION</div>
                        <div class="summary-row"><span>Patient</span><strong id="revName">—</strong></div>
                        <div class="summary-row"><span>Email</span><strong id="revEmail">—</strong></div>
                        <div class="summary-row"><span>Phone</span><strong id="revPhone">—</strong></div>
                        <div class="summary-row"><span>Specialist</span><strong id="revDoctor">—</strong></div>
                        <div class="summary-row"><span>Schedule</span><strong id="revSchedule" style="color: #0f62fe;">—</strong></div>
                        <div class="summary-total-row"><span>Consultation Fee</span><strong>₱500.00</strong></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-outline" id="backToStep1Btn">&lsaquo; Back</button>
                        <button type="submit" name="confirm_modal_booking" class="btn-navy">Confirm Appointment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($booked_ticket): ?>
    <div class="modal-backdrop" id="successTicketModal" style="display: flex;">
        <div class="modal-card" style="border-top: 6px solid #10b981;">
            <div class="modal-header">
                <div>
                    <span class="status-booked">CONFIRMED</span>
                    <h2 style="font-size: 1.35rem; margin-top: 0.35rem;">Clinic Pass #<?= str_pad($booked_ticket['id'], 4, '0', STR_PAD_LEFT) ?></h2>
                    <?php if ($email_sent): ?>
                        <p style="font-size: 0.82rem; color: #059669; font-weight: 600; margin-top: 0.25rem;">
                            ✉️ Clinic Pass sent to <?= htmlspecialchars($booked_ticket['email']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('successTicketModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <div class="summary-box" style="background: #f8fafc; border-style: dashed;">
                    <div class="summary-row"><span>Patient</span><strong><?= htmlspecialchars($booked_ticket['patient']) ?></strong></div>
                    <div class="summary-row"><span>Specialist</span><strong><?= htmlspecialchars($booked_ticket['doctor']) ?> (<?= htmlspecialchars($booked_ticket['dept']) ?>)</strong></div>
                    <div class="summary-row"><span>Date & Time</span><strong style="color:#0f62fe;"><?= htmlspecialchars($booked_ticket['date']) ?> @ <?= htmlspecialchars($booked_ticket['time']) ?></strong></div>
                    <div class="summary-total-row"><span>Consultation Fee</span><strong>₱<?= number_format($booked_ticket['amount'], 2) ?></strong></div>
                </div>
                <p style="font-size: 0.85rem; color: #475569; text-align: center; margin: 1rem 0 0 0;">
                    Please check your Gmail (<strong><?= htmlspecialchars($booked_ticket['email']) ?></strong>) for your official Clinic Pass, Google Calendar link, and cancellation link.
                </p>
                <div class="modal-footer" style="grid-template-columns: 1fr;">
                    <button type="button" class="btn-navy" style="width: 100%;" onclick="document.getElementById('successTicketModal').style.display='none'">Done</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php require_once 'includes/footer.php'; ?>