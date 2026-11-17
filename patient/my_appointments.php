<?php
require_once '../config/db.php';

$email        = trim($_GET['email'] ?? '');
$appointments = [];
$patient_info = null;

if ($email !== '') {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $patient_info = $stmt->get_result()->fetch_assoc();

    if ($patient_info) {
        $pid = (int)$patient_info['patient_id'];
        $sql = "SELECT a.*, d.name AS doctor_name, dept.name AS dept_name, p.amount, p.payment_status
                FROM appointments a
                INNER JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN departments dept ON d.department_id = dept.department_id
                LEFT JOIN payments p ON a.appointment_id = p.appointment_id
                WHERE a.patient_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time ASC";
        $appt_stmt = $conn->prepare($sql);
        $appt_stmt->bind_param("i", $pid);
        $appt_stmt->execute();
        $res = $appt_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
}

$page_title = "My Appointments";
require_once '../includes/header.php';
?>

<main class="container">
    <section class="hero">
        <h1>My Appointments</h1>
        <p>Enter the email address you used during booking to check your schedule or cancel an upcoming slot.</p>
    </section>

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert success">Your appointment has been cancelled, and the time slot is now open again.</div>
    <?php endif; ?>

    <div class="panel" style="max-width: 600px;">
        <form method="GET" action="my_appointments.php" style="display:flex; gap:0.75rem; align-items:center;">
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Enter your registered email..." required style="margin:0; flex:1;">
            <button type="submit">Find Bookings</button>
        </form>
    </div>

    <?php if ($email !== '' && !$patient_info): ?>
        <div class="alert error">No patient record found for <strong><?= htmlspecialchars($email) ?></strong>.</div>
    <?php elseif ($patient_info): ?>
        <div class="panel">
            <h2>Bookings for <?= htmlspecialchars($patient_info['name']) ?></h2>

            <?php if (empty($appointments)): ?>
                <p style="color:#64748b; margin-top:0.5rem;">You have no appointments yet. <a href="../index.php">Book one now</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Doctor & Dept</th>
                            <th>Date & Time</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><strong>#<?= str_pad($appt['appointment_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($appt['doctor_name']) ?></strong><br>
                                    <span class="badge"><?= htmlspecialchars($appt['dept_name'] ?? 'General') ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($appt['appointment_date']) ?><br>
                                    <strong><?= htmlspecialchars($appt['appointment_time']) ?></strong>
                                </td>
                                <td>
                                    ₱<?= number_format($appt['amount'] ?? 500, 2) ?>
                                    <small style="color:#64748b; display:block;"><?= htmlspecialchars($appt['payment_status'] ?? 'Paid') ?></small>
                                </td>
                                <td>
                                    <?php
                                        $st = strtolower($appt['status']);
                                        $cls = $st === 'booked' ? 'status-booked' : ($st === 'completed' ? 'status-completed' : 'status-cancelled');
                                    ?>
                                    <span class="<?= $cls ?>"><?= htmlspecialchars($appt['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($appt['status'] === 'Booked'): ?>
                                        <a href="cancel_appointment.php?id=<?= (int)$appt['appointment_id'] ?>&email=<?= urlencode($email) ?>"
                                           class="btn-cancel"
                                           onclick="return confirm('Cancel this appointment and release the time slot?');">
                                           Cancel
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>