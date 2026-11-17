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
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css?v=6">
    <style>
        :root {
            --nav-bg: #064e3b;
            --nav-accent: #6ee7b7;
            --primary: #059669;
            --primary-hover: #047857;
            --page-bg: #f8fafc;
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

        /* --- GREEN & WHITE STICKY NAVBAR --- */
        .navbar {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            color: #ffffff;
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(6, 78, 59, 0.18);
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
        }

        .brand-icon {
            background: #ffffff;
            color: var(--primary);
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .brand span {
            color: var(--nav-accent);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #d1fae5;
            text-decoration: none;
            padding: 0.5rem 0.95rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .nav-links a.active {
            background: #ffffff;
            color: #064e3b;
            font-weight: 700;
        }
        .nav-links a.admin-pill {
            color: rgba(209, 250, 229, 0.45);
            font-size: 0.78rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            margin-left: 0.5rem;
            border: none;
        }

        .nav-links a.admin-pill:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        /* --- MAIN CONTAINER & HERO --- */
        .container {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        .matrix-page-container {
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .hero {
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: 1.8rem;
            color: #064e3b;
            margin-bottom: 0.35rem;
        }

        .hero p {
            color: var(--text-muted);
        }

        /* --- TWO-COLUMN DASHBOARD LAYOUT --- */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 1.5rem;
            align-items: start;
        }

        @media (max-width: 860px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
        }

        .panel, .card, .form-container {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 16px rgba(6, 78, 59, 0.05);
            margin-bottom: 1.5rem;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .panel-header h2, .card h2 {
            font-size: 1.2rem;
            color: #064e3b;
        }

        /* --- INPUTS & FORMS --- */
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="password"], select, textarea {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.92rem;
            margin-top: 0.3rem;
            margin-bottom: 1rem;
            background: #ffffff;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .panel-header input, .panel-header select {
            width: auto;
            margin: 0;
        }

        label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
        }

        .badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 0.2rem 0.65rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* --- SCHEDULE MATRIX --- */
        .matrix-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .date-title-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .today-pill {
            background: var(--primary);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
        }

        .matrix-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-btn-group {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .icon-nav-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            font-weight: 700;
            color: #064e3b;
            cursor: pointer;
            margin: 0;
            position: relative;
        }

        .icon-nav-btn:hover {
            background: #ecfdf5;
            border-color: var(--green-border);
        }

        .calendar-trigger input[type="date"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
            margin: 0;
        }

        .matrix-wrapper {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .schedule-matrix {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            box-shadow: none;
            min-width: 760px;
        }

        .schedule-matrix th {
            background: #f0fdf4;
            color: #064e3b;
            text-transform: none;
            text-align: center;
            padding: 0.6rem 0.5rem;
            border-bottom: 1px solid #bbf7d0;
            border-right: 1px solid #e2e8f0;
            font-size: 0.92rem;
        }

        .schedule-matrix th:first-child {
            text-align: left;
            width: 175px;
        }

        .schedule-matrix th small {
            display: block;
            font-weight: 600;
            color: #059669;
            font-size: 0.78rem;
            margin-top: 2px;
        }

        .shift-divider td {
            background: #ecfdf5 !important;
            color: #065f46;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.4rem 0.85rem;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #d1fae5;
        }

        .schedule-matrix td {
            padding: 0.35rem 0.45rem;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .time-col {
            background: #ffffff !important;
            font-size: 0.84rem;
            color: #334155;
            white-space: nowrap;
        }

        .time-col strong {
            display: block;
            color: var(--primary);
            font-size: 0.82rem;
            margin-top: 1px;
        }

        .matrix-cell-btn {
            width: 100%;
            height: 35px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.82rem;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: 0.15s;
        }

        .matrix-cell-btn.available {
            background: #ffffff;
            border-color: #10b981;
            color: #065f46;
        }

        .matrix-cell-btn.available:hover {
            background: #ecfdf5;
            transform: scale(1.02);
        }

        .matrix-cell-btn.booked {
            background: var(--red-bg);
            border-color: var(--red-border);
            color: var(--red-text);
            cursor: not-allowed;
        }

        .matrix-cell-btn.offduty {
            background: #f8fafc;
            border-color: #f1f5f9;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .matrix-cell-btn.picked {
            background: var(--primary) !important;
            border-color: #064e3b !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
        }

        /* --- SLOT LEGEND --- */
        .slot-legend {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            font-size: 0.85rem;
            color: #475569;
        }

        .slot-legend .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: middle;
        }

        .box.open, .box.available { background: #ffffff; border: 1.5px solid #10b981; }
        .box.booked { background: var(--red-border)}
        .box.selected { background: var(--primary); }
        .box.offduty { background: #f1f5f9; border: 1px solid #e2e8f0; }

        /* --- STICKY BOTTOM BAR --- */
        .sticky-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            border-top: 1px solid #d1fae5;
            box-shadow: 0 -4px 20px rgba(6, 78, 59, 0.12);
            padding: 0.9rem 1rem;
            z-index: 950;
        }

        .sticky-bar-inner {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* --- IN-PAGE BOOKING MODAL POPUP --- */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 2000;
        }
    .modal-card {
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            border-top: 5px solid var(--primary);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.25rem 1.5rem 0.85rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .modal-step-tag {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 0.6px;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.6rem;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
        }

        .modal-progress-track {
            height: 3px;
            background: #e2e8f0;
            width: 100%;
        }

        .modal-progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.25s ease;
        }

        .modal-body {
            padding: 1.25rem 1.5rem 1.5rem 1.5rem;
            max-height: 80vh;
            overflow-y: auto;
        }

        .summary-box {
            border: 1px solid #d1fae5;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem 1.15rem;
        }

        .summary-section-title {
            font-size: 0.72rem;
            font-weight: 800;
            color: #065f46;
            letter-spacing: 0.5px;
            margin-bottom: 0.65rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            font-size: 0.9rem;
            color: #475569;
        }

        .summary-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid #d1fae5;
            font-weight: 700;
            color: #064e3b;
        }

        .duration-pill {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            margin-left: 0.35rem;
        }

        .modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .btn-outline {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f1f5f9;
        }

        .btn-navy {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-navy:hover {
            background: var(--primary-hover);
        }

        /* --- GENERAL BUTTONS --- */
        .btn, .btn-book, button[type="submit"] {
            display: inline-block;
            background: var(--primary);
            color: #ffffff;
            text-decoration: none;
            padding: 0.7rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover, .btn-book:hover, button[type="submit"]:hover {
            background: var(--primary-hover);
        }

        .btn-danger, .btn-cancel {
            background: #ef4444;
            color: #ffffff;
            padding: 0.45rem 0.9rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-danger:hover, .btn-cancel:hover {
            background: #dc2626;
        }

        /* --- STANDARD TABLES & STATUS BADGES (My Appointments & Admin) --- */
        table:not(.schedule-matrix) {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            margin-top: 1rem;
        }

        table:not(.schedule-matrix) th {
            background: #064e3b;
            color: #ffffff;
            text-align: left;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        table:not(.schedule-matrix) td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.92rem;
        }

        table:not(.schedule-matrix) tr:hover td {
            background: #f0fdf4;
        }

        .status-booked { background: #dcfce7; color: #166534; padding: 0.25rem 0.65rem; border-radius: 20px; font-weight: 600; font-size: 0.8rem; }
        .status-completed { background: #d1fae5; color: #065f46; padding: 0.25rem 0.65rem; border-radius: 20px; font-weight: 600; font-size: 0.8rem; }
        .status-cancelled { background: #fee2e2; color: #991b1b; padding: 0.25rem 0.65rem; border-radius: 20px; font-weight: 600; font-size: 0.8rem; }

        /* --- ALERTS --- */
        .alert, .success, .error {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-weight: 600;
        }
        .alert.success, .success { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .alert.error, .error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
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