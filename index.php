
<?php
require_once 'config/db.php';
require_once 'includes/doctors.php';

$doctors = getAllDoctors($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        Clinic <span>Appointment System</span>
    </div>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="patient/my_appointments.php">My Appointments</a>
        <a href="admin/login.php">Admin</a>
    </div>
</nav>

<main class="container">

    <section class="hero">
        <h1>Book Your Appointment</h1>
        <p>Select a doctor and choose your preferred appointment schedule.</p>
    </section>

    <section class="doctors-section">
        <h2>Available Doctors</h2>

        <div class="grid-cards">

            <?php if (empty($doctors)): ?>

                <p>No doctors are currently available.</p>

            <?php else: ?>

                <?php foreach ($doctors as $doctor): ?>

                    <div class="doctor-card">

                        <div class="card-content">
                            <h3>
                                <?= htmlspecialchars($doctor['name']) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $doctor['dept_name'] ?? 'General Medicine'
                                ) ?>
                            </p>

                            <p>
                                <strong>Schedule:</strong><br>
                                <?= htmlspecialchars($doctor['work_start']) ?>
                                -
                                <?= htmlspecialchars($doctor['work_end']) ?>
                            </p>
                        </div>

                        <div class="card-footer">
                            <a
                                href="patient/dashboard.php?doctor_id=<?= (int)$doctor['doctor_id'] ?>"
                                class="btn-book">
                                Book Appointment
                            </a>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </section>

</main>

</body>
</html>
