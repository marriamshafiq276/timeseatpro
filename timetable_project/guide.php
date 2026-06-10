<?php
/**
 * Authenticated quick guide page.
 * Gives admins a first-run checklist and demo account reminders.
 */
session_start();
include 'includes/auth_check.php';
include 'includes/header.php';
include 'includes/nav.php';
?>

<main class="max-w-5xl mx-auto px-4 py-8">
    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6">
        <h1 class="text-3xl font-bold text-emerald-800 mb-4">Project Guide</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="border rounded p-4">
                <h2 class="font-bold text-slate-800 mb-2">Admin</h2>
                <p class="text-sm text-slate-600">Username: <strong>admin</strong></p>
                <p class="text-sm text-slate-600">Password: <strong>admin123</strong></p>
            </div>
            <div class="border rounded p-4">
                <h2 class="font-bold text-slate-800 mb-2">Teacher</h2>
                <p class="text-sm text-slate-600">Username: <strong>teacher_demo</strong></p>
                <p class="text-sm text-slate-600">Password: <strong>admin123</strong></p>
            </div>
            <div class="border rounded p-4">
                <h2 class="font-bold text-slate-800 mb-2">Student</h2>
                <p class="text-sm text-slate-600">Username: <strong>student_demo</strong></p>
                <p class="text-sm text-slate-600">Password: <strong>admin123</strong></p>
            </div>
        </div>

        <h2 class="text-xl font-bold text-slate-900 mb-3">First Run Checklist</h2>
        <ol class="list-decimal pl-6 space-y-2 text-slate-700">
            <li>Import <strong>database/all_queries.sql</strong> into MySQL or MariaDB.</li>
            <li>Open <strong>http://localhost/timetable_project/login.php</strong>.</li>
            <li>Log in as admin and review the master data from the Data menu.</li>
            <li>Generate or view a timetable, then test Excel, PDF, and print exports.</li>
            <li>Generate or view a seating plan, then test its exports.</li>
            <li>Register a new teacher/student account and approve/link it from Users.</li>
        </ol>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
