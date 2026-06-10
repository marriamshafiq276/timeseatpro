<?php
/**
 * Public about page.
 * Explains the project for visitors who are not authenticated.
 */
$pageTitle = 'About | Academic Scheduling & Seating Suite';
include 'includes/public_header.php';
?>

<main class="bg-emerald-50/40">
    <section class="border-b border-emerald-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 pt-2 pb-8 sm:px-6 lg:px-8">
            <div class="max-w-4xl">
                <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-800">About The System</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight text-emerald-950">Built for smoother academic planning.</h1>
                <p class="mt-5 text-lg leading-8 text-slate-700">
                This project helps university administrators reduce manual timetable preparation, prevent common scheduling
                conflicts, and create structured seating plans for examinations.
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-emerald-200 bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-extrabold text-emerald-900">Purpose</h2>
                <p class="mt-4 text-sm leading-7 text-slate-700">
                    The system centralizes academic data such as teachers, students, classes, subjects, rooms, and constraints.
                    It then uses that data to support timetable generation and examination seat allocation from one secure portal.
                </p>
            </section>
            <section class="rounded-lg border border-emerald-200 bg-emerald-900 p-8 text-white shadow-sm">
                <h2 class="text-2xl font-extrabold">Users</h2>
                <p class="mt-4 text-sm leading-7 text-emerald-50">
                    Admin users manage institutional data and generation tools. Teachers and students can access their relevant
                    timetable views after logging in with approved accounts.
                </p>
            </section>
        </div>

        <div class="mt-8 rounded-lg border border-emerald-200 bg-white p-8 shadow-sm">
            <h2 class="text-2xl font-extrabold text-emerald-900">Professional Workflow</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-4">
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-6">
                    <p class="text-lg font-extrabold text-emerald-950">1. Prepare Data</p>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Enter academic records, rooms, activities, and constraints.</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-6 shadow-sm">
                    <p class="text-lg font-extrabold text-emerald-950">2. Generate</p>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Create timetable and seating outputs from official data.</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-6 shadow-sm">
                    <p class="text-lg font-extrabold text-emerald-950">3. Review</p>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Check schedules, histories, conflicts, and presentation views.</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-6">
                    <p class="text-lg font-extrabold text-emerald-950">4. Share</p>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Teachers and students access their assigned timetable pages.</p>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-3">
            <article class="rounded-lg border border-emerald-200 bg-white p-8 shadow-sm">
                <h3 class="text-xl font-extrabold text-emerald-900">Reliability</h3>
                <p class="mt-4 text-sm leading-6 text-slate-700">The system supports organized data entry and consistent output generation for repeated academic sessions.</p>
            </article>
            <article class="rounded-lg border border-emerald-200 bg-white p-8 shadow-sm">
                <h3 class="text-xl font-extrabold text-emerald-900">Efficiency</h3>
                <p class="mt-4 text-sm leading-6 text-slate-700">Administrators can reduce manual work by using structured records and generation tools.</p>
            </article>
            <article class="rounded-lg border border-emerald-200 bg-white p-8 shadow-sm">
                <h3 class="text-xl font-extrabold text-emerald-900">Clarity</h3>
                <p class="mt-4 text-sm leading-6 text-slate-700">Role-specific pages help teachers and students see the timetable information relevant to them.</p>
            </article>
        </div>
    </section>
</main>

<?php include 'includes/public_footer.php'; ?>
