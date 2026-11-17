<?php
require_once '../config/db.php';
require_once '../includes/doctors.php';
require_once '../includes/patients.php';
require_once '../includes/appointments.php';

$doctors   = getAllDoctors($conn);
$bookedMap = getUpcomingBookedMap($conn);

// Catch pre-selected values from index.php URL
$selected_doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : ($doctors[0]['doctor_id'] ?? 1);
$selected_date      = $_GET['date'] ?? date('Y-m-d');
$selected_time      = $_GET['time'] ?? '';

$message       = '';
$msg_type      = '';
$booked_ticket = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    $name      = trim($_POST['patient_name']);
    $email     = trim($_POST['patient_email']);
    $phone     = trim($_POST['patient_phone']);
    $doctor_id = (int)$_POST['doctor_id'];
    $date      = $_POST['appointment_date'];
    $time      = trim($_POST['appointment_time']);
    $amount    = 500.00;

    if (empty($time)) {
        $message  = "Please select an available green time slot before confirming.";
        $msg_type = "error";
    } else {
        // Privacy-safe double check so two users can't grab the same slot
        $current_booked = getBookedSlots($conn, $doctor_id, $date);
        if (in_array($time, $current_booked)) {
            $message  = "That time slot was just taken! Please pick another green slot.";
            $msg_type = "error";
        } else {
            // Find existing patient by email or register new patient
            $patient = getPatientByEmail($conn, $email);
            $patient_id = $patient ? $patient['patient_id'] : createPatient($conn, $name, $email, $phone);

            if ($patient_id) {
                $appt_id = createAppointment($conn, $patient_id, $doctor_id, $date, $time);
                if ($appt_id) {
                    // Save payment using $conn (mysqli)
                    $pay_stmt = $conn->prepare("INSERT INTO payments (appointment_id, amount, payment_status) VALUES (?, ?, 'Paid')");
                    $pay_stmt->bind_param("id", $appt_id, $amount);
                    $pay_stmt->execute();

                    $doc_info = getDoctorById($conn, $doctor_id);
                    $booked_ticket = [
                        'id'     => $appt_id,
                        'patient'=> $name,
                        'email'  => $email,
                        'doctor' => $doc_info['name'],
                        'dept'   => $doc_info['dept_name'],
                        'date'   => $date,
                        'time'   => $time,
                        'amount' => $amount
                    ];
                    // Refresh booked map so the newly booked slot immediately turns red
                    $bookedMap = getUpcomingBookedMap($conn);
                } else {
                    $message  = "Could not save appointment. Please try again.";
                    $msg_type = "error";
                }
            }
        }
    }
}

$page_title = "Complete Your Booking";
require_once '../includes/header.php';
?>

<main class="container" 
      id="patientBookingPage"
      data-doctors='<?= htmlspecialchars(json_encode($doctors), ENT_QUOTES, "UTF-8") ?>'
      data-booked='<?= htmlspecialchars(json_encode($bookedMap), ENT_QUOTES, "UTF-8") ?>'>

    <section class="hero">
        <h1>Confirm Your Appointment</h1>
        <p>Review your selected schedule or switch time slots below before issuing your clinic pass.</p>
    </section>

    <?php if ($message): ?>
        <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($booked_ticket): ?>
        <!-- BOARDING PASS STYLE CONFIRMATION TICKET -->
        <div class="panel" style="border-top: 6px solid #10b981; max-width: 650px; margin: 0 auto;">
            <span class="status-booked" style="display:inline-block; margin-bottom:0.75rem;">CONFIRMED & PAID</span>
            <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Clinic Appointment Pass #<?= str_pad($booked_ticket['id'], 4, '0', STR_PAD_LEFT) ?></h2>
            <p style="color:#64748b; margin-bottom: 1.25rem;">Present this booking reference at the clinic reception desk.</p>

            <div style="background:#f8fafc; padding:1.25rem; border-radius:10px; border:1px dashed #cbd5e1; display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <small style="color:#64748b; display:block;">PATIENT NAME</small>
                    <strong><?= htmlspecialchars($booked_ticket['patient']) ?></strong>
                </div>
                <div>
                    <small style="color:#64748b; display:block;">SPECIALIST</small>
                    <strong><?= htmlspecialchars($booked_ticket['doctor']) ?></strong>
                    <span class="badge"><?= htmlspecialchars($booked_ticket['dept']) ?></span>
                </div>
                <div>
                    <small style="color:#64748b; display:block;">DATE & TIME</small>
                    <strong style="color:#0f62fe;"><?= htmlspecialchars($booked_ticket['date']) ?> @ <?= htmlspecialchars($booked_ticket['time']) ?></strong>
                </div>
                <div>
                    <small style="color:#64748b; display:block;">CONSULTATION FEE</small>
                    <strong>₱<?= number_format($booked_ticket['amount'], 2) ?> (Paid)</strong>
                </div>
            </div>

            <div style="display:flex; gap:1rem;">
                <a href="my_appointments.php?email=<?= urlencode($booked_ticket['email']) ?>" class="btn">View My Appointments</a>
                <a href="../index.php" class="filter-pill" style="text-decoration:none; display:inline-flex; align-items:center;">&larr; Back to Dashboard</a>
            </div>
        </div>

    <?php else: ?>
        <!-- INTERACTIVE TWO-STEP BOOKING FORM -->
        <div class="dashboard-layout">
            <!-- LEFT: LIVE SLOT ADJUSTER -->
            <div class="panel">
                <h2>1. Verify Doctor & Time Slot</h2>
                
                <label for="docSelect">Specialist</label>
                <select id="docSelect">
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int)$doc['doctor_id'] ?>" <?= $doc['doctor_id'] == $selected_doctor_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['dept_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="dateInput">Appointment Date</label>
                <input type="date" id="dateInput" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($selected_date) ?>">

                <div class="slot-legend">
                    <span><i class="box open"></i> Available</span>
                    <span><i class="box booked"></i> Booked</span>
                    <span><i class="box selected"></i> Selected</span>
                </div>

                <div class="slots-grid" id="miniSlotsGrid"></div>
            </div>

            <!-- RIGHT: PATIENT DETAILS FORM -->
            <div class="panel">
                <h2>2. Patient Information</h2>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="doctor_id" id="hiddenDoctorId" value="<?= $selected_doctor_id ?>">
                    <input type="hidden" name="appointment_date" id="hiddenDate" value="<?= htmlspecialchars($selected_date) ?>">
                    <input type="hidden" name="appointment_time" id="hiddenTime" value="<?= htmlspecialchars($selected_time) ?>">

                    <div id="selectedSummaryBanner" style="background:#eff6ff; border-left:4px solid #0f62fe; padding:0.85rem 1rem; border-radius:8px; margin-bottom:1rem;">
                        <small style="color:#0f62fe; font-weight:700;">SELECTED SCHEDULE</small><br>
                        <strong id="summaryText">Please pick a green time slot on the left</strong>
                    </div>

                    <label>Full Name</label>
                    <input type="text" name="patient_name" placeholder="e.g., Juan Dela Cruz" required>

                    <label>Email Address (Used to look up & cancel appointments)</label>
                    <input type="email" name="patient_email" placeholder="e.g., juan@email.com" required>

                    <label>Mobile Number</label>
                    <input type="tel" name="patient_phone" placeholder="e.g., 09171234567" required>

                    <div style="background:#f8fafc; padding:0.85rem; border-radius:8px; margin-bottom:1rem; display:flex; justify-content:space-between;">
                        <span>Consultation Fee:</span>
                        <strong>₱500.00</strong>
                    </div>

                    <button type="submit" name="book_now" style="width:100%;">Confirm & Issue Ticket</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('patientBookingPage');
    if (!page || !document.getElementById('miniSlotsGrid')) return;

    const doctors   = JSON.parse(page.dataset.doctors || '[]');
    const bookedMap = JSON.parse(page.dataset.booked || '{}');

    const docSelect    = document.getElementById('docSelect');
    const dateInput    = document.getElementById('dateInput');
    const grid         = document.getElementById('miniSlotsGrid');
    const hiddenDoc    = document.getElementById('hiddenDoctorId');
    const hiddenDate   = document.getElementById('hiddenDate');
    const hiddenTime   = document.getElementById('hiddenTime');
    const summaryText  = document.getElementById('summaryText');

    let preselectedTime = <?= json_encode($selected_time) ?>;

    function toMins(str) {
        const [t, mod] = str.trim().split(' ');
        let [h, m] = t.split(':').map(Number);
        if (mod === 'PM' && h !== 12) h += 12;
        if (mod === 'AM' && h === 12) h = 0;
        return h * 60 + m;
    }

    function toTimeStr(mins) {
        let h = Math.floor(mins / 60);
        const m = mins % 60;
        const mod = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${mod}`;
    }

    function renderMiniSlots() {
        const docId = docSelect.value;
        const date  = dateInput.value;
        const doc   = doctors.find(d => String(d.doctor_id) === String(docId));
        if (!doc) return;

        hiddenDoc.value  = docId;
        hiddenDate.value = date;
        grid.innerHTML   = '';

        const bookedList = (bookedMap[docId] && bookedMap[docId][date]) || [];
        const start = toMins(doc.work_start);
        const end   = toMins(doc.work_end);

        for (let m = start; m < end; m += 60) {
            const label = toTimeStr(m);
            const isBooked = bookedList.includes(label);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `slot-btn ${isBooked ? 'booked' : 'open'}`;

            if (isBooked) {
                btn.disabled = true;
                btn.innerHTML = `${label}<small>BOOKED</small>`;
            } else {
                btn.innerHTML = `${label}<small>AVAILABLE</small>`;
                if (label === preselectedTime) {
                    btn.classList.add('picked');
                    hiddenTime.value = label;
                    summaryText.textContent = `${doc.name} • ${date} at ${label}`;
                }
                btn.onclick = () => {
                    document.querySelectorAll('#miniSlotsGrid .slot-btn').forEach(b => b.classList.remove('picked'));
                    btn.classList.add('picked');
                    preselectedTime = label;
                    hiddenTime.value = label;
                    summaryText.textContent = `${doc.name} • ${date} at ${label}`;
                };
            }
            grid.appendChild(btn);
        }
    }

    docSelect.addEventListener('change', () => { preselectedTime = ''; hiddenTime.value = ''; summaryText.textContent = 'Select a green slot'; renderMiniSlots(); });
    dateInput.addEventListener('change', () => { preselectedTime = ''; hiddenTime.value = ''; summaryText.textContent = 'Select a green slot'; renderMiniSlots(); });

    renderMiniSlots();
});
</script>

<?php require_once '../includes/footer.php'; ?>