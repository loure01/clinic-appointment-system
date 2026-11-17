<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appt_id = (int)$_POST['appointment_id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['Booked', 'Completed', 'Cancelled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->bind_param("si", $new_status, $appt_id);
        if ($stmt->execute()) {
            $message = "Appointment #" . str_pad($appt_id, 4, '0', STR_PAD_LEFT) . " updated to " . $new_status . ".";
        }
    }
}

$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? '';

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        p.name AS patient_name,
        p.email AS patient_email,
        p.phone AS patient_phone,
        d.name AS doctor_name,
        dept.name AS dept_name,
        COALESCE(pay.amount, 500.00) AS amount,
        COALESCE(pay.payment_status, 'Paid') AS payment_status
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN departments dept ON d.department_id = dept.department_id
    LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($filter_status !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_date !== '') {
    $query .= " AND a.appointment_date = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stats_res = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(status = 'Booked') AS booked,
        SUM(status = 'Completed') AS completed,
        SUM(status = 'Cancelled') AS cancelled
    FROM appointments
");
$stats = $stats_res->fetch_assoc();

$page_title = "Staff Dashboard - Clinic Appointment System";
require_once '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.65rem; color: #064e3b;">Clinic Operations Dashboard</h1>
            <p style="color: #64748b; font-size: 0.92rem;">Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong></p>
        </div>
        <a href="logout.php" class="btn-outline" style="text-decoration: none; border-color: #ef4444; color: #dc2626;">
            Log Out
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="margin: 0; border-left: 4px solid #064e3b; padding: 1.15rem;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Consultations</span>
            <div style="font-size: 1.75rem; font-weight: 800; color: #064e3b; margin-top: 0.25rem;"><?= (int)$stats['total'] ?></div>
        </div>
        <div class="card" style="margin: 0; border-left: 4px solid #10b981; padding: 1.15rem;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Active Bookings</span>
            <div style="font-size: 1.75rem; font-weight: 800; color: #059669; margin-top: 0.25rem;"><?= (int)$stats['booked'] ?></div>
        </div>
        <div class="card" style="margin: 0; border-left: 4px solid #047857; padding: 1.15rem;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Completed</span>
            <div style="font-size: 1.75rem; font-weight: 800; color: #047857; margin-top: 0.25rem;"><?= (int)$stats['completed'] ?></div>
        </div>
        <div class="card" style="margin: 0; border-left: 4px solid #ef4444; padding: 1.15rem;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Cancelled</span>
            <div style="font-size: 1.75rem; font-weight: 800; color: #dc2626; margin-top: 0.25rem;"><?= (int)$stats['cancelled'] ?></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header" style="flex-wrap: wrap;">
            <h2>Patient Appointments</h2>
            <form method="GET" action="dashboard.php" style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;">
                <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" style="padding: 0.45rem 0.75rem;">
                <select name="status" style="padding: 0.45rem 0.75rem;">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Booked" <?= $filter_status === 'Booked' ? 'selected' : '' ?>>Booked</option>
                    <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn" style="padding: 0.48rem 1rem;">Filter</button>
                <?php if ($filter_status !== 'all' || $filter_date !== ''): ?>
                    <a href="dashboard.php" class="btn-outline" style="padding: 0.45rem 0.85rem; text-decoration: none; font-size: 0.85rem;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Pass #</th>
                        <th>Patient Details</th>
                        <th>Specialist</th>
                        <th>Date & Time</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                                No appointments found for the selected filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><strong>#<?= str_pad($row['appointment_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['patient_name']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($row['patient_email']) ?> &bull; <?= htmlspecialchars($row['patient_phone']) ?></small>
                                </td>
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
                                    <form method="POST" action="dashboard.php" style="display: flex; gap: 0.4rem; align-items: center;">
                                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                        <select name="status" style="width: auto; margin: 0; padding: 0.35rem 0.5rem; font-size: 0.82rem;">
                                            <option value="Booked" <?= $row['status'] === 'Booked' ? 'selected' : '' ?>>Booked</option>
                                            <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn" style="padding: 0.38rem 0.75rem; font-size: 0.8rem;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>