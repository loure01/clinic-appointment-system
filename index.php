<?php
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';
require_once 'includes/appointments.php';

$departments = getAllDepartments($conn);
$doctors     = getAllDoctors($conn);
$bookedMap   = getUpcomingBookedMap($conn);

// Page settings for header.php and footer.php
$page_title      = "Clinic Appointment System - Dashboard";
$load_booking_js = true;

require_once 'includes/header.php';
?>

<main class="container" 
      id="bookingApp"
      data-doctors='<?= htmlspecialchars(json_encode($doctors), ENT_QUOTES, "UTF-8") ?>'
      data-booked='<?= htmlspecialchars(json_encode($bookedMap), ENT_QUOTES, "UTF-8") ?>'
      data-today="<?= date('Y-m-d') ?>">

    <section class="hero">
        <h1>Real-Time Doctor Availability</h1>
        <p>Select a specialist and date below—just like booking a flight—to see open and booked time slots.</p>
    </section>

    <div class="dashboard-layout">
        <!-- LEFT COLUMN: DOCTOR DIRECTORY & LIVE FILTERS -->
        <section class="panel">
            <div class="panel-header">
                <h2>1. Choose a Doctor</h2>
                <input type="text" id="searchDoctor" class="search-input" placeholder="Search doctor name...">
            </div>

            <div class="dept-filters">
                <button type="button" class="filter-pill active" data-dept="all">All</button>
                <?php foreach ($departments as $dept): ?>
                    <button type="button" class="filter-pill" data-dept="<?= htmlspecialchars($dept['name']) ?>">
                        <?= htmlspecialchars($dept['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="doctor-cards-list" id="doctorList">
                <?php foreach ($doctors as $i => $doc): ?>
                    <div class="doctor-item <?= $i === 0 ? 'selected' : '' ?>" 
                         data-doctor-id="<?= (int)$doc['doctor_id'] ?>"
                         data-dept="<?= htmlspecialchars($doc['dept_name'] ?? '') ?>"
                         data-name="<?= htmlspecialchars(strtolower($doc['name'])) ?>">
                        <div>
                            <h3><?= htmlspecialchars($doc['name']) ?></h3>
                            <span class="badge"><?= htmlspecialchars($doc['dept_name'] ?? 'General') ?></span>
                        </div>
                        <div class="hours-tag">
                            <small>Clinic Hours</small>
                            <strong><?= htmlspecialchars($doc['work_start']) ?> – <?= htmlspecialchars($doc['work_end']) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- RIGHT COLUMN: FLIGHT-STYLE DATE STRIP & SEAT/SLOT GRID -->
        <section class="panel">
            <div class="panel-header">
                <h2 id="activeDoctorHeading">2. Available Schedule</h2>
                <input type="date" id="datePicker" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="date-strip" id="dateStrip"></div>

            <div class="slot-legend">
                <span><i class="box open"></i> Available</span>
                <span><i class="box booked"></i> Booked</span>
                <span><i class="box selected"></i> Your Pick</span>
            </div>

            <div class="slots-grid" id="slotsGrid"></div>

            <div class="ticket-bar" id="ticketBar" style="display: none;">
                <div class="ticket-info">
                    <span class="ticket-label">SELECTED SCHEDULE</span>
                    <strong id="ticketDetails"></strong>
                </div>
                <a href="#" id="proceedBtn" class="btn-book">Proceed to Book &rarr;</a>
            </div>
        </section>
    </div>

</main>

<?php require_once 'includes/footer.php'; ?>