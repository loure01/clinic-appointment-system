<?php
require_once '../config/db.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appt_id = (int)($_POST['appointment_id'] ?? 0);
    $cancel_email = trim($_POST['email'] ?? '');

    $stmt = $conn->prepare("
        UPDATE appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        SET a.status = 'Cancelled'
        WHERE a.appointment_id = ? AND p.email = ? AND a.status = 'Booked'
    ");
    $stmt->bind_param("is", $appt_id, $cancel_email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "Appointment #" . str_pad($appt_id, 4, '0', STR_PAD_LEFT) . " has been cancelled.";
    } else {
        $error = "Unable to cancel this appointment.";
    }
    $email = $cancel_email;
}

$appointments = [];
if ($email !== '') {
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            p.name AS patient_name,
            d.name AS doctor_name,
            dept.name AS dept_name,
            COALESCE(pay.amount, 500.00) AS amount
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN departments dept ON d.department_id = dept.department_id
        LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
        WHERE p.email = ?
        ORDER BY a.appointment_date DESC, a.appointment_time ASC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "My Appointments - Clinic Appointment System";
require_once '../includes/header.php';
?>

<main class="container">
    <div class="hero">
        <h1>My Appointments</h1>
        <p>Enter your email address to view your clinic passes or cancel an upcoming consultation.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel" style="border-top: 4px solid #059669;">
        <form method="GET" action="my_appointments.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 240px;">
                <label>Patient Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="e.g., juan@email.com" required style="margin-bottom: 0;">
            </div>
            <button type="submit" class="btn-navy" style="padding: 0.65rem 1.5rem;">
                Find Appointments
            </button>
        </form>
    </div>

    <?php if ($email !== ''): ?>
        <div class="panel">
            <div class="panel-header">
                <h2>Records for <?= htmlspecialchars($email) ?></h2>
                <a href="../index.php" class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;">+ Book New</a>
            </div>

            <?php if (empty($appointments)): ?>
                <p style="color: #64748b; padding: 1.5rem 0; text-align: center;">
                    No appointments found under this email address.
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Pass #</th>
                                <th>Patient Name</th>
                                <th>Specialist</th>
                                <th>Date & Time</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($row['appointment_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['doctor_name']) ?></strong><br>
                                        <span class="badge"><?= htmlspecialchars($row['dept_name'] ?? 'General Medicine') ?></span>
                                    </td>
                                    <td>
                                        <strong><?= date('M d, Y', strtotime($row['appointment_date'])) ?></strong><br>
                                        <span style="color: #059669; font-weight: 600;"><?= htmlspecialchars($row['appointment_time']) ?></span>
                                    </td>
                                    <td><strong>₱<?= number_format($row['amount'], 2) ?></strong></td>
                                    <td>
                                        <?php
                                            $badge_class = 'status-booked';
                                            if ($row['status'] === 'Completed') $badge_class = 'status-completed';
                                            if ($row['status'] === 'Cancelled') $badge_class = 'status-cancelled';
                                        ?>
                                        <span class="<?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'Booked'): ?>
                                            <form method="POST" action="my_appointments.php" onsubmit="return confirm('Cancel this appointment?');">
                                                <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                                <button type="submit" name="cancel_appointment" class="btn-danger" style="border: none; cursor: pointer;">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>