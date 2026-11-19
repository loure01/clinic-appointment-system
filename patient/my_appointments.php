<?php
require_once '../config/db.php';

$email = trim($_GET['email'] ?? '');
$cancelled = isset($_GET['cancelled']);

if ($email === '') {
    header("Location: ../index.php");
    exit;
}

$p_stmt = $conn->prepare("SELECT * FROM patients WHERE email = ?");
$p_stmt->bind_param("s", $email);
$p_stmt->execute();
$patient_info = $p_stmt->get_result()->fetch_assoc();

if (!$patient_info) {
    header("Location: ../index.php");
    exit;
}

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

$page_title = "Manage Appointment - Clinic Appointment System";
require_once '../includes/header.php';
?>

<main class="container">
    <?php if ($cancelled): ?>
        <div class="alert success">Your appointment has been cancelled and the time slot is now open again.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Manage Your Visits</h2>
                <small style="color: #64748b;"><?= htmlspecialchars($patient_info['name']) ?> &bull; <?= htmlspecialchars($patient_info['email']) ?></small>
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
                                       onclick="return confirm('Cancel Pass #<?= str_pad($row['appointment_id'], 4, '0', STR_PAD_LEFT) ?>?');">
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
</main>

<?php require_once '../includes/footer.php'; ?>