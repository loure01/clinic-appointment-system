document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('bookingApp');
    if (!app) return;

    const allDoctors = JSON.parse(app.dataset.doctors || '[]');
    const bookedMap  = JSON.parse(app.dataset.booked || '{}');
    const todayStr   = app.dataset.today;

    function formatLocalISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function parseLocalDate(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function getNextWeekday(iso) {
        const d = parseLocalDate(iso);
        while (d.getDay() === 0 || d.getDay() === 6) {
            d.setDate(d.getDate() + 1);
        }
        return formatLocalISO(d);
    }

    function stepWeekday(iso, delta) {
        const d = parseLocalDate(iso);
        do {
            d.setDate(d.getDate() + delta);
        } while (d.getDay() === 0 || d.getDay() === 6);
        return formatLocalISO(d);
    }

    function timeToMinutes(str) {
        const [t, mod] = str.trim().split(' ');
        let [h, m] = t.split(':').map(Number);
        if (mod === 'PM' && h !== 12) h += 12;
        if (mod === 'AM' && h === 12) h = 0;
        return h * 60 + m;
    }

    const timeBlocks = [
        { shift: 'Morning Clinic',   start: '08:00 AM', end: '09:00 AM', mins: 480 },
        { shift: 'Morning Clinic',   start: '09:00 AM', end: '10:00 AM', mins: 540 },
        { shift: 'Morning Clinic',   start: '10:00 AM', end: '11:00 AM', mins: 600 },
        { shift: 'Morning Clinic',   start: '11:00 AM', end: '12:00 PM', mins: 660 },
        { shift: 'Afternoon Clinic', start: '12:00 PM', end: '01:00 PM', mins: 720 },
        { shift: 'Afternoon Clinic', start: '01:00 PM', end: '02:00 PM', mins: 780 },
        { shift: 'Afternoon Clinic', start: '02:00 PM', end: '03:00 PM', mins: 840 },
        { shift: 'Afternoon Clinic', start: '03:00 PM', end: '04:00 PM', mins: 900 },
        { shift: 'Afternoon Clinic', start: '04:00 PM', end: '05:00 PM', mins: 960 }
    ];

    let selectedDate = getNextWeekday(todayStr);
    let activeDept   = 'all';
    let currentPick  = null;

    const dateHeading   = document.getElementById('matrixDateHeading');
    const todayBadge    = document.getElementById('todayBadge');
    const datePicker    = document.getElementById('matrixDatePicker');
    const deptSelect    = document.getElementById('deptFilterSelect');
    const matrixHead    = document.getElementById('matrixHead');
    const matrixBody    = document.getElementById('matrixBody');
    const ticketBar     = document.getElementById('ticketBar');
    const ticketDetails = document.getElementById('ticketDetails');

    const bookingModal      = document.getElementById('bookingModal');
    const modalStepTag      = document.getElementById('modalStepTag');
    const modalTitle        = document.getElementById('modalTitle');
    const modalSubtitle     = document.getElementById('modalSubtitle');
    const modalProgressFill = document.getElementById('modalProgressFill');
    const modalStep1        = document.getElementById('modalStep1');
    const modalStep2        = document.getElementById('modalStep2');

    function formatFriendlyDate(iso) {
        const d = parseLocalDate(iso);
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
    }

    function renderMatrix() {
        currentPick = null;
        ticketBar.style.display = 'none';
        dateHeading.textContent = formatFriendlyDate(selectedDate).replace(/, \d{4}$/, '');
        datePicker.value        = selectedDate;
        todayBadge.style.display = (selectedDate === getNextWeekday(todayStr)) ? 'inline-block' : 'none';

        const visibleDoctors = activeDept === 'all'
            ? allDoctors
            : allDoctors.filter(d => d.dept_name === activeDept);

        let headHTML = '<tr><th>Consultation Hours</th>';
        visibleDoctors.forEach(doc => {
            headHTML += `<th>${doc.name}<small>${doc.dept_name || 'General Medicine'}</small></th>`;
        });
        headHTML += '</tr>';
        matrixHead.innerHTML = headHTML;

        matrixBody.innerHTML = '';
        let currentShift = '';

        timeBlocks.forEach(block => {
            if (block.shift !== currentShift) {
                currentShift = block.shift;
                const shiftRow = document.createElement('tr');
                shiftRow.className = 'shift-divider';
                shiftRow.innerHTML = `<td colspan="${visibleDoctors.length + 1}">${currentShift}</td>`;
                matrixBody.appendChild(shiftRow);
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="time-col">${block.start} &rarr; ${block.end}<strong>₱500</strong></td>`;

            visibleDoctors.forEach(doc => {
                const td = document.createElement('td');
                const docStart = timeToMinutes(doc.work_start);
                const docEnd   = timeToMinutes(doc.work_end);
                const docBookings = (bookedMap[doc.doctor_id] && bookedMap[doc.doctor_id][selectedDate]) || [];

                const isOnDuty = (block.mins >= docStart && block.mins < docEnd);
                const matchedSlot = isOnDuty
                    ? (block.mins === docStart && doc.work_start.includes(':30') ? doc.work_start : block.start)
                    : null;

                const btn = document.createElement('button');
                btn.type = 'button';

                if (!isOnDuty) {
                    btn.className = 'matrix-cell-btn offduty';
                    btn.disabled = true;
                    btn.textContent = '—';
                } else if (docBookings.includes(matchedSlot)) {
                    btn.className = 'matrix-cell-btn booked';
                    btn.disabled = true;
                    btn.textContent = 'Booked';
                } else {
                    btn.className = 'matrix-cell-btn available';
                    btn.textContent = 'Available';
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.matrix-cell-btn').forEach(b => b.classList.remove('picked'));
                        btn.classList.add('picked');

                        currentPick = {
                            doctorId: doc.doctor_id,
                            doctorName: doc.name,
                            deptName: doc.dept_name || 'General Medicine',
                            date: selectedDate,
                            friendlyDate: formatFriendlyDate(selectedDate),
                            time: matchedSlot,
                            timeRange: `${block.start} → ${block.end}`
                        };

                        ticketDetails.textContent = `${currentPick.doctorName} • ${currentPick.friendlyDate} (${currentPick.time})`;
                        ticketBar.style.display = 'block';
                    });
                }

                td.appendChild(btn);
                tr.appendChild(td);
            });

            matrixBody.appendChild(tr);
        });
    }

    document.getElementById('openModalBtn').addEventListener('click', () => {
        if (!currentPick) return;

        document.getElementById('modalDoctorId').value = currentPick.doctorId;
        document.getElementById('modalDate').value     = currentPick.date;
        document.getElementById('modalTime').value     = currentPick.time;

        document.getElementById('sumDoctor').textContent = currentPick.doctorName;
        document.getElementById('sumDept').textContent   = currentPick.deptName;
        document.getElementById('sumDate').textContent   = currentPick.friendlyDate;
        document.getElementById('sumTime').textContent   = currentPick.timeRange;

        showStep1();
        bookingModal.style.display = 'flex';
    });

    function showStep1() {
        modalStepTag.textContent      = 'STEP 1 OF 2';
        modalTitle.textContent        = 'Appointment Details';
        modalSubtitle.textContent     = 'Review your consultation schedule and enter patient details';
        modalProgressFill.style.width = '50%';
        modalStep1.style.display      = 'block';
        modalStep2.style.display      = 'none';
    }

    function showStep2() {
        const name  = document.getElementById('inputPatientName').value.trim();
        const email = document.getElementById('inputPatientEmail').value.trim();
        const phone = document.getElementById('inputPatientPhone').value.trim();

        if (!name || !email || !phone) {
            alert('Please fill in your Full Name, Email Address, and Mobile Number.');
            return;
        }

        document.getElementById('revName').textContent     = name;
        document.getElementById('revEmail').textContent    = email;
        document.getElementById('revPhone').textContent    = phone;
        document.getElementById('revDoctor').textContent   = `${currentPick.doctorName} (${currentPick.deptName})`;
        document.getElementById('revSchedule').textContent = `${currentPick.friendlyDate} • ${currentPick.time}`;

        modalStepTag.textContent      = 'STEP 2 OF 2';
        modalTitle.textContent        = 'Confirm Appointment';
        modalSubtitle.textContent     = 'Verify your information before issuing your clinic pass';
        modalProgressFill.style.width = '100%';
        modalStep1.style.display      = 'none';
        modalStep2.style.display      = 'block';
    }

    document.getElementById('toStep2Btn').addEventListener('click', showStep2);
    document.getElementById('backToStep1Btn').addEventListener('click', showStep1);
    document.getElementById('closeModalBtn').addEventListener('click', () => bookingModal.style.display = 'none');
    document.getElementById('cancelModalBtn').addEventListener('click', () => bookingModal.style.display = 'none');

    document.getElementById('prevDayBtn').addEventListener('click', () => {
        const minDate = getNextWeekday(todayStr);
        const prev = stepWeekday(selectedDate, -1);
        if (prev >= minDate) {
            selectedDate = prev;
            renderMatrix();
        }
    });

    document.getElementById('nextDayBtn').addEventListener('click', () => {
        selectedDate = stepWeekday(selectedDate, 1);
        renderMatrix();
    });

    datePicker.addEventListener('change', (e) => {
        if (!e.target.value) return;
        const day = parseLocalDate(e.target.value).getDay();
        if (day === 0 || day === 6) {
            alert('Clinic is closed on Saturdays and Sundays. Please select a Monday–Friday date.');
            e.target.value = selectedDate;
            return;
        }
        selectedDate = e.target.value;
        renderMatrix();
    });

    deptSelect.addEventListener('change', (e) => {
        activeDept = e.target.value;
        renderMatrix();
    });

    renderMatrix();
});