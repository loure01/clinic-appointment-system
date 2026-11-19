<?php
require_once '../config/db.php';

$email = trim($_GET['email'] ?? '');
$cancelled = isset($_GET['cancelled']);
$appointments = [];
$patient_info = null;

if ($email !== '') {
    $p_stmt = $conn->prepare("SELECT * FROM patients WHERE email = ?");
    $p_stmt->bind_param("s", $email);
    $p_stmt->execute();
    $patient_info = $p_stmt->get_result()->fetch_assoc();

    if ($patient_info) {
        $stmt = $conn->prepare("
            SELECT 
                a.appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                d.name AS doctor_name,
                dept.name AS dept_name,
                COALESCE(pay.amount, 500.00) AS amount,
                mr.diagnosis,
                mr.prescription
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            LEFT JOIN departments dept ON d.department_id = dept.department_id
            LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
            LEFT JOIN medical_records mr ON a.appointment_id = mr.appointment_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time ASC
        ");
        $stmt->bind_param("i", $patient_info['patient_id']);
        $stmt->execute();
        $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$page_title = "My Appointments - Clinic Appointment System";
require_once '../includes/header.php';
?>

<main class="container">
    <div class="hero">
        <h1>My Clinic Passes</h1>
        <p>Your appointment confirmations are automatically sent to your Gmail. Open your magic link or look up your passes below.</p>
    </div>

    <?php if ($cancelled): ?>
        <div class="alert success">Your appointment has been cancelled and the time slot has been released.</div>
    <?php endif; ?>

    <div class="panel" style="padding: 1.15rem 1.35rem;">
        <form method="GET" action="my_appointments.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Enter the Gmail address used during booking..." required style="flex: 1; min-width: 240px; margin: 0;">
            <button type="submit" class="btn">Load Passes</button>
        </form>
    </div>

    <?php if ($email !== ''): ?>
        <?php if (!$patient_info): ?>
            <div class="alert error">No appointments found for <strong><?= htmlspecialchars($email) ?></strong>.</div>
        <?php else: ?>
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Passes for <?= htmlspecialchars($patient_info['name']) ?></h2>
                        <small style="color: #64748b;"><?= htmlspecialchars($patient_info['email']) ?> &bull; <?= htmlspecialchars($patient_info['phone']) ?></small>
                    </div>
                    <a href="../index.php" class="btn-outline" style="text-decoration: none; font-size: 0.85rem; padding: 0.5rem 0.9rem;">+ Book New Visit</a>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Pass #</th>
                                <th>Specialist</th>
                                <th>Schedule</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Action / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($row['appointment_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
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
                                            <a href="cancel_appointment.php?id=<?= $row['appointment_id'] ?>&email=<?= urlencode($email) ?>" 
                                               class="btn-cancel"
                                               onclick="return confirm('Are you sure you want to cancel Pass #<?= str_pad($row['appointment_id'], 4, '0', STR_PAD_LEFT) ?>?');">
                                                Cancel Visit
                                            </a>
                                        <?php elseif ($row['status'] === 'Completed' && !empty($row['prescription'])): ?>
                                            <small style="color: #064e3b;">
                                                <strong>Dx:</strong> <?= htmlspecialchars($row['diagnosis']) ?><br>
                                                <strong>Rx:</strong> <?= htmlspecialchars($row['prescription']) ?>
                                            </small>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>