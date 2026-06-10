<?php
/**
 * Admin dashboard page.
 * Summarizes key record counts and links to the main management workflows.
 */
session_start();

include 'includes/auth_check.php';
include 'includes/config.php';
include 'includes/auth.php';
require_once 'includes/security.php';

requireRole('admin', 'login.php');

include 'includes/header.php';
include 'includes/nav.php';

$studentCount    = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc()['cnt'];
$teacherCount    = $conn->query("SELECT COUNT(*) as cnt FROM teachers")->fetch_assoc()['cnt'];
$subjectCount    = $conn->query("SELECT COUNT(*) as cnt FROM subjects")->fetch_assoc()['cnt'];
$activitiesCount = $conn->query("SELECT COUNT(*) as cnt FROM activities")->fetch_assoc()['cnt'];
$timetableCount  = $conn->query("SELECT COUNT(*) as cnt FROM timetable")->fetch_assoc()['cnt'];
$seatAllocationCount = $conn->query("SELECT COUNT(*) as cnt FROM seat_allocation")->fetch_assoc()['cnt'];
$pendingUsers    = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE status='Pending'")->fetch_assoc()['cnt'];

$summaryCards = [
    [
        'label' => 'Students',
        'count' => $studentCount,
        'href' => $base . 'data/students.php',
        'action' => 'Manage',
        'accent' => 'border-blue-500',
        'text' => 'text-blue-700',
        'button' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-200',
        'bg' => 'bg-blue-50',
        'icon' => 'S',
    ],
    [
        'label' => 'Teachers',
        'count' => $teacherCount,
        'href' => $base . 'data/teachers.php',
        'action' => 'Manage',
        'accent' => 'border-emerald-500',
        'text' => 'text-emerald-700',
        'button' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-200',
        'bg' => 'bg-emerald-50',
        'icon' => 'T',
    ],
    [
        'label' => 'Subjects',
        'count' => $subjectCount,
        'href' => $base . 'data/subjects.php',
        'action' => 'Manage',
        'accent' => 'border-amber-500',
        'text' => 'text-amber-700',
        'button' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-200',
        'bg' => 'bg-amber-50',
        'icon' => 'B',
    ],
    [
        'label' => 'Activities',
        'count' => $activitiesCount,
        'href' => $base . 'data/activities.php',
        'action' => 'Manage',
        'accent' => 'border-indigo-500',
        'text' => 'text-indigo-700',
        'button' => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-200',
        'bg' => 'bg-indigo-50',
        'icon' => 'A',
    ],
    [
        'label' => 'Timetables',
        'count' => $timetableCount,
        'href' => $base . 'view_timetable.php',
        'action' => 'Review/Edit',
        'accent' => 'border-slate-600',
        'text' => 'text-slate-700',
        'button' => 'bg-slate-700 hover:bg-slate-800 focus:ring-slate-200',
        'bg' => 'bg-slate-100',
        'icon' => 'TT',
    ],
    [
        'label' => 'Seat Allocation',
        'count' => $seatAllocationCount,
        'href' => $base . 'view_seating_plan.php',
        'action' => 'Review/Edit',
        'accent' => 'border-rose-500',
        'text' => 'text-rose-700',
        'button' => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-200',
        'bg' => 'bg-rose-50',
        'icon' => 'SA',
    ],
    [
        'label' => 'Pending Users',
        'count' => $pendingUsers,
        'href' => $base . 'user_management.php',
        'action' => 'Review',
        'accent' => 'border-red-500',
        'text' => 'text-red-700',
        'button' => 'bg-red-600 hover:bg-red-700 focus:ring-red-200',
        'bg' => 'bg-red-50',
        'icon' => 'U',
    ],
];
?>

<main class="bg-slate-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <section class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 items-start">
            <aside class="bg-emerald-900 text-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-200">Admin Dashboard</p>
                    <h2 class="mt-3 text-3xl font-bold leading-tight">
                        Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
                    </h2>
                    <p class="mt-4 text-sm leading-6 text-emerald-100">
                        Manage institution data, review generated timetables, and open seat allocation from one place.
                    </p>
                </div>

                <div class="border-t border-emerald-800 p-6 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-emerald-200">Current role</span>
                        <strong class="rounded bg-white/10 px-3 py-1 text-white">
                            <?= htmlspecialchars($_SESSION['role']) ?>
                        </strong>
                    </div>
                    <a href="<?= $base ?>generate_timetable.php"
                       class="block w-full rounded-md bg-white px-4 py-3 text-center text-sm font-semibold text-emerald-900 shadow-sm hover:bg-emerald-50 transition">
                        Generate Schedule
                    </a>
                    <a href="<?= $base ?>generate_seating.php"
                       class="block w-full rounded-md border border-emerald-300 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-white/10 transition">
                        Generate Seating Plan
                    </a>
                </div>
            </aside>

            <section>
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">Overview</h3>
                        <p class="text-sm text-slate-600">Quick access to your scheduling records.</p>
                    </div>
                    <a href="<?= $base ?>data/institution.php"
                       class="inline-flex w-fit items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Institution Settings
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <?php foreach ($summaryCards as $card): ?>
                        <article class="bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition">
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500"><?= $card['label'] ?></p>
                                        <p class="mt-2 text-4xl font-bold tracking-tight text-slate-950"><?= $card['count'] ?></p>
                                    </div>
                                    <span class="<?= $card['bg'] ?> <?= $card['text'] ?> flex h-11 min-w-11 items-center justify-center rounded-md px-3 text-sm font-bold">
                                        <?= $card['icon'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t <?= $card['accent'] ?> border-t-4 px-5 py-4">
                                <a href="<?= $card['href'] ?>"
                                   class="<?= $card['button'] ?> inline-flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-4">
                                    <?= $card['action'] ?> <?= $card['label'] ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>

        <section class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-5">
            <a href="<?= $base ?>data/classes.php"
               class="group rounded-lg bg-white p-5 shadow-sm border border-slate-200 hover:shadow-md transition">
                <span class="text-sm font-medium text-slate-500">Academic Setup</span>
                <strong class="mt-2 block text-lg text-slate-900">Classes, days, hours and rooms</strong>
                <span class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition group-hover:bg-emerald-700">
                    Open Setup
                </span>
            </a>
            <a href="<?= $base ?>data/time_constraints.php"
               class="group rounded-lg bg-white p-5 shadow-sm border border-slate-200 hover:shadow-md transition">
                <span class="text-sm font-medium text-slate-500">Rules</span>
                <strong class="mt-2 block text-lg text-slate-900">Time and space constraints</strong>
                <span class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition group-hover:bg-slate-800">
                    Manage Rules
                </span>
            </a>
            <a href="<?= $base ?>timetable_display.php"
               class="group rounded-lg bg-white p-5 shadow-sm border border-slate-200 hover:shadow-md transition">
                <span class="text-sm font-medium text-slate-500">Presentation</span>
                <strong class="mt-2 block text-lg text-slate-900">Display final timetable</strong>
                <span class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition group-hover:bg-blue-700">
                    View Display
                </span>
            </a>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
