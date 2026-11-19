<?php
require_once '../config/db.php';
require_once '../includes/patients.php';
require_once '../includes/appointments.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($email === '') {
    header("Location: ../schedule.php");
    exit;
}

$patient = getPatientByEmail($conn, $email);
$message = '';
$error   = '';

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_id'])) {
    $appt_id = (int)$_POST['cancel_appointment_id'];

    if ($patient && cancelAppointment($conn, $appt_id, $patient['patient_id'])) {
        $message = "Your appointment has been successfully cancelled.";
    } else {
        $error = "Unable to cancel this appointment. It may have already been cancelled or is within 2 hours of the scheduled time.";
    }
}

$appointments = $patient ? getAppointmentsByPatient($conn, $patient['patient_id']) : [];

$page_title = "Manage Appointments - Holy Clinic";
require_once '../includes/header.php';
?>

<header class="w-full bg-white border-b border-slate-200/80 sticky top-0 z-40">
    <div class="w-full max-w-7xl mx-auto px-6 sm:px-10 h-20 flex items-center justify-between">
        <a href="../index.php" class="flex items-center gap-3 text-slate-900 no-underline">
            <img src="../images/holy_clinic.png" alt="Holy Clinic Logo" class="w-11 h-11 rounded-xl object-cover shadow-sm border border-emerald-900/10">
            <div>
                <div class="font-extrabold text-xl leading-none text-slate-900">Holy Clinic</div>
                <div class="text-xs font-medium text-emerald-600 tracking-wide mt-0.5">Medical & Wellness</div>
            </div>
        </a>

        <a href="../index.php" title="Home" class="w-10 h-10 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 flex items-center justify-center transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
        </a>
    </div>
</header>

<main class="w-full max-w-5xl mx-auto px-6 sm:px-10 py-10">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Patient Visit Manager</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">My Appointments</h1>
            <p class="text-sm text-slate-500 mt-1">
                Showing scheduled consultations for <strong><?= htmlspecialchars($email) ?></strong>
            </p>
        </div>

        <a href="../schedule.php" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm transition">
            + Book Another Visit
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$patient || empty($appointments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-2">No Appointments Found</h3>
            <p class="text-sm text-slate-500 mb-5">We couldn't find any booked consultations under this email address.</p>
            <a href="../schedule.php" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition">
                View Live Schedule
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table style="margin-top: 0; box-shadow: none; border-radius: 0;">
                    <thead>
                        <tr>
                            <th>Pass #</th>
                            <th>Specialist</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): 
                            $status = $appt['status'] ?? 'Booked';
                            $passNo = str_pad($appt['appointment_id'], 4, '0', STR_PAD_LEFT);
                            $apptTimestamp = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
                            $canCancel = ($status === 'Booked' && ($apptTimestamp - time()) > 7200);
                        ?>
                            <tr>
                                <td><strong>#<?= $passNo ?></strong></td>
                                <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($appt['dept_name']) ?></span></td>
                                <td><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($appt['appointment_time']) ?></strong></td>
                                <td>
                                    <?php if ($status === 'Booked'): ?>
                                        <span class="status-booked">Booked</span>
                                    <?php elseif ($status === 'Completed'): ?>
                                        <span class="status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="status-cancelled">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($canCancel): ?>
                                        <form method="POST" action="my_appointments.php" onsubmit="return confirm('Are you sure you want to cancel Pass #<?= $passNo ?>?');" style="display: inline;">
                                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                            <input type="hidden" name="cancel_appointment_id" value="<?= (int)$appt['appointment_id'] ?>">
                                            <button type="submit" class="btn-cancel" style="border: none; cursor: pointer;">Cancel Visit</button>
                                        </form>
                                    <?php elseif ($status === 'Booked'): ?>
                                        <span class="text-xs text-slate-400 italic">Locked (&lt; 2 hrs)</span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once '../includes/footer.php'; ?>