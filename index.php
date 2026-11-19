<?php
require_once 'config/db.php';
require_once 'includes/departments.php';
require_once 'includes/doctors.php';

$departments = getAllDepartments($conn);
$doctors     = getAllDoctors($conn);

$doctorsByDept = [];
foreach ($doctors as $doc) {
    $deptName = $doc['dept_name'] ?? 'General Medicine';
    $doctorsByDept[$deptName][] = $doc;
}

$specialtyDetails = [
    'Cardiology' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11.25h2.25l1.5-2.25 1.5 4.5 1.5-2.25h2.25"/></svg>',
        'desc' => 'Comprehensive heart care, ECG diagnostics, blood pressure management, and cardiovascular consultations.'
    ],
    'General Medicine' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m-7.5 3.75h15A2.25 2.25 0 0021.75 16.5v-9A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z"/></svg>',
        'desc' => 'Primary care for everyday health concerns, annual checkups, preventive screenings, and chronic condition care.'
    ],
    'Dental Care' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>',
        'desc' => 'Complete oral health services including routine cleaning, restorative treatments, and preventive dental care.'
    ],
    'Dentistry' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>',
        'desc' => 'Complete oral health services including routine cleaning, restorative treatments, and preventive dental care.'
    ],
    'Pediatrics' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>',
        'desc' => 'Dedicated medical care for infants, children, and adolescents including wellness checks and immunizations.'
    ],
    'Dermatology' => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
        'desc' => 'Specialized diagnosis and treatment for skin, hair, and nail conditions with personalized care plans.'
    ],
    "Women's Health" => [
        'svg'  => '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>',
        'desc' => 'Compassionate OB-GYN consultations, prenatal guidance, and preventive wellness care at every stage of life.'
    ]
];

$defaultSvg = '<svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>';

$page_title = "Holy Clinic - Appointment System";
require_once 'includes/header.php';
?>

<header class="w-full bg-white border-b border-slate-200/80 sticky top-0 z-40">
    <div class="w-full max-w-7xl mx-auto px-6 sm:px-10 h-20 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-3 text-slate-900 no-underline">
            <img src="images/holy_clinic.png" alt="Holy Clinic Logo" class="w-11 h-11 rounded-xl object-cover shadow-sm border border-emerald-900/10">
            <div>
                <div class="font-extrabold text-xl leading-none text-slate-900">Holy Clinic</div>
                <div class="text-xs font-medium text-emerald-600 tracking-wide mt-0.5">Medical & Wellness</div>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-9 text-sm font-semibold text-slate-600">
            <a href="#ourServicesSection" class="hover:text-emerald-600 transition">Our Services</a>
            <a href="#howItWorksSection" class="hover:text-emerald-600 transition">How It Works</a>
            <a href="#whyChooseUsSection" class="hover:text-emerald-600 transition">About Clinic</a>
        </nav>

        <a href="index.php" title="Home" class="w-10 h-10 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 flex items-center justify-center transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
        </a>
    </div>
</header>

<section class="w-full bg-gradient-to-r from-slate-50 via-emerald-50/50 to-teal-100/60 border-b border-slate-200/80">
    <div class="w-full max-w-7xl mx-auto px-6 sm:px-10 py-14 md:py-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    Trusted Care. Better Health.
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.1]">
                    Your Health<br>
                    <span class="text-emerald-600">Our Priority</span>
                </h1>

                <p class="text-slate-600 text-base sm:text-lg max-w-xl leading-relaxed">
                    Compassionate care, advanced technology, and experienced specialists — all for a healthier you. View real-time doctor availability and receive your Clinic Pass straight to your Gmail.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="schedule.php" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm sm:text-base shadow-md shadow-emerald-600/20 transition">
                        Book Appointment
                    </a>
                    <a href="#ourServicesSection" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm sm:text-base border border-slate-300 transition">
                        Our Services
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5 relative flex items-center justify-center lg:justify-end">
                <div class="relative w-full max-w-md">
                    <div class="absolute -right-3 -bottom-3 w-4/5 h-4/5 bg-emerald-700 rounded-tl-3xl rounded-br-3xl -z-0"></div>
                    <div class="relative z-10 w-full h-80 sm:h-96 rounded-2xl shadow-lg border-4 border-white overflow-hidden flex items-center justify-center" style="background-color: #84a572;">
                        <img src="images/holy_clinic.png" 
                             alt="Holy Clinic Emblem" 
                             class="w-72 h-72 sm:w-80 sm:h-80 object-contain">
                    </div>

                    <div class="absolute -bottom-4 -right-2 sm:-right-4 z-20 bg-white rounded-xl shadow-lg border border-slate-100 px-5 py-3.5 text-center">
                        <div class="w-9 h-9 mx-auto mb-1 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                        </div>
                        <div class="text-base font-extrabold text-slate-900 leading-tight">24/7</div>
                        <div class="text-[11px] text-slate-500 leading-tight">Emergency<br>Support</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="w-full max-w-7xl mx-auto px-6 sm:px-10 py-14 space-y-16">

    <section id="ourServicesSection" class="scroll-mt-24">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold tracking-wider uppercase text-emerald-600">Medical Specialties</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1">Our Services</h2>
            <div class="w-14 h-1 bg-emerald-600 rounded-full mx-auto mt-3"></div>
            <p class="text-slate-500 text-sm sm:text-base mt-3">Explore our clinical departments and assigned specialists available for weekday consultations.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
            <?php foreach ($departments as $dept): 
                $deptName = $dept['name'];
                $info = $specialtyDetails[$deptName] ?? [
                    'svg'  => $defaultSvg,
                    'desc' => 'Specialized medical consultations, diagnostics, and personalized patient care.'
                ];
                $assignedDoctors = $doctorsByDept[$deptName] ?? [];
            ?>
                <div class="bg-white rounded-2xl border border-slate-200/90 p-7 shadow-sm hover:shadow-md hover:border-emerald-200 transition flex flex-col justify-between">
                    <div>
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-5">
                            <?= $info['svg'] ?>
                        </div>

                        <h3 class="text-xl font-extrabold text-slate-900 mb-2"><?= htmlspecialchars($deptName) ?></h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-5">
                            <?= htmlspecialchars($info['desc']) ?>
                        </p>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Specialists</div>
                        <?php if (!empty($assignedDoctors)): ?>
                            <div class="space-y-2">
                                <?php foreach ($assignedDoctors as $doc): ?>
                                    <div class="flex items-center justify-between text-xs sm:text-sm bg-slate-50 px-3.5 py-2 rounded-lg">
                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($doc['name']) ?></span>
                                        <span class="text-emerald-600 font-semibold">₱500</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic">Consultations open weekdays</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="howItWorksSection" class="scroll-mt-24 bg-white rounded-3xl border border-slate-200/80 p-8 md:p-12 shadow-sm">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold tracking-wider uppercase text-emerald-600">Simple Process</span>
            <h2 class="text-3xl font-extrabold text-slate-900 mt-1">How It Works</h2>
            <div class="w-14 h-1 bg-emerald-600 rounded-full mx-auto mt-3"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-extrabold text-sm flex items-center justify-center mb-4">1</div>
                <h3 class="text-base font-bold text-slate-900 mb-1.5">Pick a Date & Specialist</h3>
                <p class="text-sm text-slate-500 leading-relaxed">Open the live schedule matrix to view open 1-hour time slots across all departments.</p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-extrabold text-sm flex items-center justify-center mb-4">2</div>
                <h3 class="text-base font-bold text-slate-900 mb-1.5">Confirm Your Details</h3>
                <p class="text-sm text-slate-500 leading-relaxed">Enter your name, Gmail address, and mobile number in a quick two-step confirmation popup.</p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-extrabold text-sm flex items-center justify-center mb-4">3</div>
                <h3 class="text-base font-bold text-slate-900 mb-1.5">Receive Your Clinic Pass</h3>
                <p class="text-sm text-slate-500 leading-relaxed">Get an instant digital pass in your Gmail with a Google Calendar invite and easy cancellation link.</p>
            </div>
        </div>
    </section>

    <section id="whyChooseUsSection" class="scroll-mt-24 grid grid-cols-1 md:grid-cols-3 gap-7">
        <div class="bg-emerald-900 text-white rounded-2xl p-7 flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Consultation Rate</span>
                <h3 class="text-2xl font-extrabold mt-1">Transparent Flat Fee</h3>
                <div class="text-4xl font-black text-emerald-300 my-4">₱500 <span class="text-xs font-normal text-emerald-100">/ session</span></div>
                <p class="text-sm text-emerald-100/90 leading-relaxed">Standard 1-hour specialist consultation with zero hidden booking charges.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-7 shadow-sm">
            <h3 class="text-xl font-extrabold text-slate-900 mb-4">Why Choose Holy Clinic?</h3>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">✓</span>
                    Experienced Specialists
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">✓</span>
                    Real-Time Slot Availability
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">✓</span>
                    Patient-First Care Approach
                </li>
                <li class="flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">✓</span>
                    No Account Password Required
                </li>
            </ul>
        </div>

        <div class="bg-emerald-50/70 rounded-2xl border border-emerald-200/80 p-7 flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Clinic Hours</span>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1 mb-3">We're Here For You</h3>
                <p class="text-sm text-slate-600 leading-relaxed mb-4">
                    Morning Shift: <strong>8:00 AM – 12:00 PM</strong><br>
                    Afternoon Shift: <strong>1:00 PM – 5:00 PM</strong><br>
                    <span class="text-xs text-slate-500">Closed on Saturdays & Sundays</span>
                </p>
            </div>
            <div class="text-xs font-semibold text-emerald-800">
                Need to cancel? Use the link in your confirmation email at least 2 hours prior.
            </div>
        </div>
    </section>

</main>

<?php require_once 'includes/footer.php'; ?>