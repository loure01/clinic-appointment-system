<?php
$in_subfolder = in_array(basename(dirname($_SERVER['PHP_SELF'])), ['patient', 'admin']);
$base_path = $in_subfolder ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Clinic Appointment System' ?></title>
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
    <style>
        :root {
            --nav-bg: #0f172a;
            --nav-accent: #38bdf8;
            --primary: #0f62fe;
            --primary-hover: #0043ce;
            --page-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --green-bg: #ecfdf5;
            --green-border: #10b981;
            --green-text: #065f46;
            --red-bg: #fef2f2;
            --red-border: #ef4444;
            --red-text: #991b1b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--page-bg);
            color: var(--text-main);
            line-height: 1.5;
        }

        /* --- MODERN STICKY HEADER --- */
        .navbar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .brand-icon {
            background: var(--primary);
            color: #ffffff;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 2px 6px rgba(15, 98, 254, 0.4);
        }

        .brand span {
            color: var(--nav-accent);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.5rem 0.95rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .nav-links a.active {
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
        }

        .nav-links a.admin-pill {
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: var(--nav-accent);
            margin-left: 0.5rem;
        }

        .nav-links a.admin-pill:hover {
            background: rgba(56, 189, 248, 0.15);
            color: #ffffff;
        }
    </style>
</head>
<body>

<header class="navbar">
    <a href="<?= $base_path ?>index.php" class="brand">
        <span class="brand-icon">+</span>
        <div>Clinic <span>Appointment System</span></div>
    </a>

    <nav class="nav-links">
        <a href="<?= $base_path ?>index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
            Dashboard
        </a>
        <a href="<?= $base_path ?>patient/my_appointments.php" class="<?= $current_page === 'my_appointments.php' ? 'active' : '' ?>">
            My Appointments
        </a>
        <a href="<?= $base_path ?>admin/login.php" class="admin-pill <?= $current_page === 'login.php' ? 'active' : '' ?>">
            Admin Portal
        </a>
    </nav>
</header>