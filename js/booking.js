document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('bookingApp');
    if (!app) return;

    const doctors   = JSON.parse(app.dataset.doctors || '[]');
    const bookedMap = JSON.parse(app.dataset.booked || '{}');
    const todayStr  = app.dataset.today;

    let selectedDoctor = doctors[0] || null;
    let selectedDate   = todayStr;
    let selectedTime   = null;
    let activeDept     = 'all';

    const datePicker    = document.getElementById('datePicker');
    const dateStrip     = document.getElementById('dateStrip');
    const slotsGrid     = document.getElementById('slotsGrid');
    const heading       = document.getElementById('activeDoctorHeading');
    const ticketBar     = document.getElementById('ticketBar');
    const ticketDetails = document.getElementById('ticketDetails');
    const proceedBtn    = document.getElementById('proceedBtn');

    // Convert "08:30 AM" into minutes from midnight
    function timeToMinutes(timeStr) {
        const [time, modifier] = timeStr.trim().split(' ');
        let [hours, minutes] = time.split(':').map(Number);
        if (modifier === 'PM' && hours !== 12) hours += 12;
        if (modifier === 'AM' && hours === 12) hours = 0;
        return hours * 60 + minutes;
    }

    // Convert minutes back to "08:30 AM"
    function minutesToTime(totalMins) {
        let hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        const modifier = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')} ${modifier}`;
    }

    // Build 7-day quick date tabs
    function renderDateStrip() {
        dateStrip.innerHTML = '';
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        for (let i = 0; i < 7; i++) {
            const d = new Date(todayStr + 'T00:00:00');
            d.setDate(d.getDate() + i);
            const iso = d.toISOString().split('T')[0];

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'date-tab' + (iso === selectedDate ? ' active' : '');
            btn.innerHTML = `<span>${dayNames[d.getDay()]}</span><strong>${d.getDate()}</strong>`;
            btn.addEventListener('click', () => {
                selectedDate = iso;
                datePicker.value = iso;
                renderDateStrip();
                renderSlots();
            });
            dateStrip.appendChild(btn);
        }
    }

    // Generate flight-style time slots for the active doctor & date
    function renderSlots() {
        if (!selectedDoctor) return;

        selectedTime = null;
        ticketBar.style.display = 'none';
        heading.textContent = `2. ${selectedDoctor.name} (${selectedDate})`;
        slotsGrid.innerHTML = '';

        const startMins   = timeToMinutes(selectedDoctor.work_start);
        const endMins     = timeToMinutes(selectedDoctor.work_end);
        const docBookings = (bookedMap[selectedDoctor.doctor_id] && bookedMap[selectedDoctor.doctor_id][selectedDate]) || [];

        for (let m = startMins; m < endMins; m += 60) {
            const slotLabel = minutesToTime(m);
            const isBooked  = docBookings.includes(slotLabel);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `slot-btn ${isBooked ? 'booked' : 'open'}`;

            if (isBooked) {
                btn.disabled = true;
                btn.innerHTML = `${slotLabel}<small>BOOKED</small>`;
            } else {
                btn.innerHTML = `${slotLabel}<small>AVAILABLE</small>`;
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('picked'));
                    btn.classList.add('picked');
                    selectedTime = slotLabel;

                    ticketDetails.textContent = `${selectedDoctor.name} • ${selectedDate} at ${selectedTime}`;
                    proceedBtn.href = `patient/dashboard.php?doctor_id=${selectedDoctor.doctor_id}&date=${encodeURIComponent(selectedDate)}&time=${encodeURIComponent(selectedTime)}`;
                    ticketBar.style.display = 'flex';
                });
            }
            slotsGrid.appendChild(btn);
        }
    }

    // Doctor card click selection
    document.querySelectorAll('.doctor-item').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.doctor-item').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const id = card.dataset.doctorId;
            selectedDoctor = doctors.find(d => String(d.doctor_id) === String(id));
            renderSlots();
        });
    });

    // Calendar input change
    datePicker.addEventListener('change', (e) => {
        selectedDate = e.target.value;
        renderDateStrip();
        renderSlots();
    });

    // Live search & department filter
    const searchInput = document.getElementById('searchDoctor');
    function filterDoctors() {
        const query = searchInput.value.toLowerCase();
        document.querySelectorAll('.doctor-item').forEach(card => {
            const matchName = card.dataset.name.includes(query);
            const matchDept = activeDept === 'all' || card.dataset.dept === activeDept;
            card.style.display = (matchName && matchDept) ? 'flex' : 'none';
        });
    }

    searchInput.addEventListener('input', filterDoctors);
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeDept = pill.dataset.dept;
            filterDoctors();
        });
    });

    // Initial render
    renderDateStrip();
    renderSlots();
});