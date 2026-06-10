<?php
/**
 * Public index/home page.
 * Acts as the main landing page before users register or log in.
 */
$pageTitle = 'Home | Academic Scheduling & Seating Suite';
include 'includes/public_header.php';
?>

<!-- RESPONSIVE FIX (ADDED ONLY) -->
<style>
html, body {
    overflow-x: hidden;
}

/* Fix hero grid on small screens */
@media (max-width: 1024px) {
    .lg\:grid-cols-\[1\.05fr_0\.95fr\] {
        grid-template-columns: 1fr !important;
    }

    .whitespace-nowrap {
        white-space: normal !important;
    }
}
</style>

<main>

<!-- HERO SECTION -->
<section class="bg-white">

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <!-- HERO + RIGHT CARD -->
        <div class="grid gap-10 grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] items-start">

            <!-- LEFT SIDE -->
            <div>

                <p class="inline-flex rounded-md bg-emerald-100 px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide text-emerald-900 ring-1 ring-emerald-200">
                    University Administration Portal
                </p>

                <h1 class="mt-3 text-3xl font-extrabold leading-tight text-emerald-950 sm:text-4xl whitespace-nowrap">
                    Academic Scheduling & Seating Suite
                </h1>

                <p class="mt-5 max-w-3xl text-base leading-8 text-slate-700 sm:text-lg">
                    A secure academic management platform for generating schedules, organizing examination seating plans,
                    and giving administrators, teachers, and students clear access to schedule information.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="login.php" class="inline-flex items-center justify-center rounded-md bg-emerald-900 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800">
                        Open Portal Login
                    </a>
                    <a href="about.php" class="inline-flex items-center justify-center rounded-md border border-emerald-300 bg-white px-6 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-50">
                        View System Overview
                    </a>
                </div>

                <!-- KPI BLOCKS WRAPPER -->
                <div class="mt-10 space-y-6">

                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-y border-emerald-200 py-5">

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Access</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">3 Roles</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Planning</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">Automated</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Exports</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">Ready</dd>
                        </div>

                    </dl>

                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-y border-emerald-200 py-5">

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Departments</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">Active</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Teachers</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">Registered</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase text-emerald-700">Students</dt>
                            <dd class="mt-1 text-xl font-extrabold text-emerald-950">Enrolled</dd>
                        </div>

                    </dl>

            5

                </div>
            </div>

            <!-- RIGHT SIDE CARD (RESPONSIVE FIXED) -->
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:p-6 shadow-xl">

                <div class="overflow-hidden rounded-xl border border-emerald-200 bg-white">

                    <div class="border-b border-emerald-800 bg-emerald-950 px-6 py-5 text-white">
                        <p class="text-sm font-bold text-emerald-100">Live Administration Snapshot</p>
                        <p class="mt-1 text-2xl font-extrabold">Academic Scheduling Workspace</p>
                    </div>

                    <div class="p-5 space-y-4">

                        <div class="rounded-md border border-emerald-100 bg-emerald-50 p-4 sm:p-5">
                            <p class="text-sm font-bold text-emerald-700">Master Data</p>
                            <p class="mt-1 text-base sm:text-lg font-extrabold text-emerald-950">
                                Classes, rooms, teachers
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="rounded-md border border-emerald-100 p-4">
                                <p class="text-sm font-bold text-emerald-700">Timetables</p>
                                <p class="mt-1 text-lg font-extrabold text-emerald-950">Conflict-aware</p>
                            </div>

                            <div class="rounded-md border border-emerald-100 p-4">
                                <p class="text-sm font-bold text-emerald-700">Seating</p>
                                <p class="mt-1 text-lg font-extrabold text-emerald-950">Capacity-based</p>
                            </div>

                        </div>

                        <div class="rounded-md bg-emerald-900 p-5 sm:p-6 text-white">
                            <p class="text-xs sm:text-sm font-bold text-emerald-100">Role-Based Access</p>
                            <p class="mt-1 text-lg sm:text-2xl font-extrabold">Admin • Teacher • Student</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- REST OF YOUR CODE (UNCHANGED) -->
<section class="border-y border-emerald-200 bg-emerald-100/60">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-800">Core Capabilities</p>
                <h2 class="mt-2 text-3xl font-extrabold text-emerald-950">Designed for daily academic operations.</h2>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            <article class="rounded-lg border border-emerald-200 bg-white p-7 shadow-sm">
                <p class="mb-5 flex h-10 w-10 items-center justify-center rounded-md bg-emerald-900 text-base font-extrabold text-white">01</p>
                <h2 class="text-xl font-extrabold text-emerald-900">Smart Scheduling</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600">Generate organized class timetables from teachers, subjects, rooms, days, and constraints.</p>
            </article>

            <article class="rounded-lg border border-emerald-200 bg-white p-7 shadow-sm">
                <p class="mb-5 flex h-10 w-10 items-center justify-center rounded-md bg-emerald-900 text-base font-extrabold text-white">02</p>
                <h2 class="text-xl font-extrabold text-emerald-900">Seat Allocation</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600">Prepare examination seating plans using room capacity and student records.</p>
            </article>

            <article class="rounded-lg border border-emerald-200 bg-white p-7 shadow-sm">
                <p class="mb-5 flex h-10 w-10 items-center justify-center rounded-md bg-emerald-900 text-base font-extrabold text-white">03</p>
                <h2 class="text-xl font-extrabold text-emerald-900">Schedule Access</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600">Teachers and students can log in to view timetable information assigned to them.</p>
            </article>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="mb-10 max-w-3xl">
            <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-800">System Modules</p>
            <h2 class="mt-3 text-3xl font-extrabold text-emerald-950">A complete administrative workspace.</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-7">
                <h3 class="text-lg font-extrabold text-emerald-950">Academic Data</h3>
                <p class="mt-3 text-sm leading-6 text-slate-700">Manage departments, faculties, classes, subjects, teachers, students, rooms, and activities.</p>
            </article>

            <article class="rounded-lg border border-emerald-200 bg-white p-7 shadow-sm">
                <h3 class="text-lg font-extrabold text-emerald-950">Timetable Tools</h3>
                <p class="mt-3 text-sm leading-6 text-slate-700">Generate, review, edit, display, export, and track timetable history.</p>
            </article>

            <article class="rounded-lg border border-emerald-200 bg-white p-7 shadow-sm">
                <h3 class="text-lg font-extrabold text-emerald-950">Seat Planning</h3>
                <p class="mt-3 text-sm leading-6 text-slate-700">Create seating plans and review allocated rooms, capacities, and arrangements.</p>
            </article>

            <article class="rounded-lg border border-emerald-200 bg-emerald-900 p-7 text-white">
                <h3 class="text-lg font-extrabold">User Access</h3>
                <p class="mt-3 text-sm leading-6 text-emerald-100">Admin approval, teacher access, student schedules, and secured role-based navigation.</p>
            </article>
        </div>
    </div>
</section>

</main>

<?php include 'includes/public_footer.php'; ?>