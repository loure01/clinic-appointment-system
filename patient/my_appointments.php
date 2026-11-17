
<?php
require_once "../config/db.php";

$email = "";
$appointments = [];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["search"])) {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare(
            "SELECT
                appointments.appointment_id,
                appointments.appointment_date,
                appointments.appointment_time,
                appointments.status,
                doctors.name AS doctor_name,
                departments.name AS department_name
             FROM appointments
             INNER JOIN doctors
                ON appointments.doctor_id = doctors.doctor_id
             LEFT JOIN departments
                ON doctors.department_id = departments.department_id
             INNER JOIN patients
                ON appointments.patient_id = patients.patient_id
             WHERE patients.email = ?
             ORDER BY appointments.appointment_date DESC,
                      appointments.appointment_time DESC"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <nav>
        <a href="../index.php">Home</a>
        <a href="my_appointments.php">My Appointments</a>
    </nav>
</header>

<main class="container">
    <h1>My Appointments</h1>
    <p>Enter the email address you used when booking.</p>

    <?php if ($error): ?>
        <p class="error"><?php echo e($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo e($email); ?>"
            required
        >
        <button type="submit" name="search">Search Appointments</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["search"]) && !$error): ?>

        <h2>Appointment Records</h2>

        <?php if (count($appointments) > 0): ?>

            <div class="appointments-list">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-card">

                        <h3><?php echo e($appointment["doctor_name"]); ?></h3>

                        <p>
                            <strong>Department:</strong>
                            <?php echo e($appointment["department_name"] ?? "Not specified"); ?>
                        </p>

                        <p>
                            <strong>Date:</strong>
                            <?php echo e($appointment["appointment_date"]); ?>
                        </p>

                        <p>
                            <strong>Time:</strong>
                            <?php echo e($appointment["appointment_time"]); ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?php echo e($appointment["status"]); ?>
                        </p>

                        <?php if ($appointment["status"] === "Booked"): ?>
                            <form method="POST" action="cancel_appointment.php"
                                  onsubmit="return confirm('Are you sure you want to cancel this appointment?');">

                                <input type="hidden" name="appointment_id"
                                       value="<?php echo e($appointment["appointment_id"]); ?>">

                                <input type="hidden" name="email"
                                       value="<?php echo e($email); ?>">

                                <button type="submit" class="cancel-btn">
                                    Cancel Appointment
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <p>No appointments were found for this email address.</p>
        <?php endif; ?>

    <?php endif; ?>

</main>

</body>
</html>
