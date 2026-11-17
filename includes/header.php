<?php
// HEADER
$in_subfolder = in_array(basename(dirname($_SERVER['PHP_SELF'])), ['patient', 'admin']);
$base_path = $in_subfolder ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Clinic Appointment System' ?></title>
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="<?= $base_path ?>index.php" style="color: inherit; text-decoration: none;">
            Clinic <span>Appointment System</span>
        </a>
    </div>

    <div class="nav-links">
        <a href="<?= $base_path ?>index.php">Dashboard</a>
        <a href="<?= $base_path ?>patient/my_appointments.php">My Appointments</a>
        <a href="<?= $base_path ?>admin/login.php">Admin</a>
    </div>
</nav>