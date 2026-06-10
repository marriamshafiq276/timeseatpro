<?php
/**
 * Student timetable view.
 * Shows the logged-in student's schedule and supports export/print actions.
 */
include 'includes/auth_check.php';
include 'includes/config.php';
require_once 'includes/security.php';
require_once 'includes/export_helpers.php';
require_once 'includes/schema_helpers.php';

ensureBuildingRoomSupport($conn);

/* ================= SECURITY CHECK ================= */
requireRole('student');

$student_id = (int) ($_SESSION['linked_id'] ?? 0);
if ($student_id <= 0) {
    $stmt = $conn->prepare("SELECT linked_id FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $student_id = (int) ($stmt->get_result()->fetch_assoc()['linked_id'] ?? 0);
    $_SESSION['linked_id'] = $student_id ?: null;
    $stmt->close();
}

$student_class = '';
if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT class FROM students WHERE id=?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_class = (string) ($stmt->get_result()->fetch_assoc()['class'] ?? '');
    $stmt->close();
}

/* ================= GET LATEST VERSION ================= */
$latestVersion = $conn->query("
    SELECT id 
    FROM timetable_versions 
    ORDER BY id DESC 
    LIMIT 1
");

if ($latestVersion->num_rows > 0) {
    $version_id = $latestVersion->fetch_assoc()['id'];
} else {
    die("No timetable version found.");
}

/* ================= REUSABLE FUNCTION ================= */
function getTimetable($conn, $version_id, $student_class) {

    $query = "
    SELECT tt.*,
       s.name AS subject_name,
       r.room_name,
       r.floor,
       b.name AS building_name,
       dh.day,
       dh.start_time,
       dh.end_time,
       t.name AS teacher_name
    FROM timetable tt
    LEFT JOIN subjects s ON tt.subject_id = s.id
    LEFT JOIN rooms r ON tt.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    LEFT JOIN days_hours dh ON tt.day_hour_id = dh.id
    LEFT JOIN teachers t ON tt.teacher_id = t.id
    WHERE tt.version_id = ?
      AND (tt.class = ? OR tt.class LIKE CONCAT(?, ' -%'))
    ORDER BY dh.day, dh.start_time
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $version_id, $student_class, $student_class);
    $stmt->execute();

    return $stmt->get_result();
}

$studentExportColumns = [
    ['label' => 'Day', 'field' => 'day'],
    ['label' => 'Time', 'value' => fn($row) => $row['start_time'] . ' - ' . $row['end_time']],
    ['label' => 'Class', 'field' => 'class'],
    ['label' => 'Subject', 'field' => 'subject_name'],
    ['label' => 'Teacher', 'field' => 'teacher_name'],
    ['label' => 'Building', 'field' => 'building_name'],
    ['label' => 'Room', 'field' => 'room_name'],
    ['label' => 'Floor', 'field' => 'floor'],
];

handleTableExport([
    'title' => 'Student Timetable',
    'filename' => 'student_timetable.xlsx',
    'columns' => $studentExportColumns,
    'rows' => fn() => getTimetable($conn, $version_id, $student_class),
    'print_param' => 'print',
]);

/* ================= MAIN DATA ================= */
$result = $student_class !== '' ? getTimetable($conn, $version_id, $student_class) : false;
$rows = [];
$uniqueDays = [];
$uniqueSubjects = [];
$uniqueRooms = [];

while ($result && $row = $result->fetch_assoc()) {
    $rows[] = $row;

    if (!empty($row['day'])) {
        $uniqueDays[$row['day']] = true;
    }

    if (!empty($row['subject_name'])) {
        $uniqueSubjects[$row['subject_name']] = true;
    }

    if (!empty($row['room_name'])) {
        $uniqueRooms[$row['room_name']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Timetable</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

<header class="bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="/timetable_project/assets/logo.png" alt="logo" class="h-8 w-8 object-contain rounded"/>
            <div class="text-sm font-semibold text-emerald-900">Academic Scheduling & Seating Suite</div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-sm text-slate-700">Logged in as <span class="font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span></div>

            <form action="/timetable_project/logout_action.php" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to logout?');">
                <button type="submit" class="rounded-md bg-emerald-900 hover:bg-emerald-800 text-white px-4 py-2 text-sm font-semibold transition shadow-sm">Logout</button>
            </form>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8">

    <!-- HERO SECTION -->
    <section class="bg-emerald-900 border border-emerald-800 rounded-xl shadow-2xl p-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">

        <div>
            <p class="text-emerald-200 text-xs font-bold uppercase tracking-widest mb-2">
                Latest published schedule
            </p>

            <h1 class="text-white text-4xl md:text-5xl font-bold leading-tight">
                Student Timetable
            </h1>

            <p class="text-emerald-100 mt-3 max-w-2xl text-sm md:text-base">
                View class sessions, subjects, teachers, rooms, and timings in one organized schedule.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">

            <a href="?export=excel"
               class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-5 py-3 rounded-lg shadow-lg transition duration-200">
                Export Excel
            </a>

            <a href="?export=pdf"
               class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-3 rounded-lg shadow-lg transition duration-200">
                Export PDF
            </a>

            <a href="?print=1"
               class="bg-slate-700 hover:bg-slate-800 text-white font-semibold px-5 py-3 rounded-lg shadow-lg transition duration-200">
                Print
            </a>

        </div>
    </section>

    <!-- STATS -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">

        <div class="bg-white border border-slate-200 rounded-xl shadow-md p-5">
            <p class="text-xs font-bold uppercase text-slate-500 mb-2">
                Total Classes
            </p>
            <p class="text-3xl font-extrabold text-emerald-800">
                <?= count($rows) ?>
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-md p-5">
            <p class="text-xs font-bold uppercase text-slate-500 mb-2">
                Study Days
            </p>
            <p class="text-3xl font-extrabold text-emerald-800">
                <?= count($uniqueDays) ?>
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-md p-5">
            <p class="text-xs font-bold uppercase text-slate-500 mb-2">
                Subjects
            </p>
            <p class="text-3xl font-extrabold text-emerald-800">
                <?= count($uniqueSubjects) ?>
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-md p-5">
            <p class="text-xs font-bold uppercase text-slate-500 mb-2">
                Rooms
            </p>
            <p class="text-3xl font-extrabold text-emerald-800">
                <?= count($uniqueRooms) ?>
            </p>
        </div>

    </section>

    <!-- TABLE PANEL -->
    <section class="bg-white border border-slate-200 rounded-xl shadow-xl mt-6 overflow-hidden">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 py-5 border-b border-slate-200">

            <h2 class="text-xl font-bold text-slate-900">
                Schedule Details
            </h2>

            <span class="text-sm text-slate-500">
                <?= count($rows) ?> timetable entries
            </span>

        </div>

        <?php if ($student_id <= 0 || $student_class === ''): ?>

            <div class="p-10 text-center text-slate-500">
                Your student account is not linked yet. Please contact the administrator.
            </div>

        <?php elseif (empty($rows)): ?>

            <div class="p-10 text-center text-slate-500">
                No timetable entries are available for your class yet.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-emerald-700 text-white uppercase text-sm">
                        <tr>
                            <th class="px-6 py-4 text-left">Day</th>
                            <th class="px-6 py-4 text-left">Time</th>
                            <th class="px-6 py-4 text-left">Class</th>
                            <th class="px-6 py-4 text-left">Subject</th>
                            <th class="px-6 py-4 text-left">Teacher</th>
                            <th class="px-6 py-4 text-left">Building</th>
                             <th class="px-6 py-4 text-left">Room</th>
                             <th class="px-6 py-4 text-left">Floor</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">

                        <?php foreach($rows as $row): ?>

                            <tr class="hover:bg-emerald-50 transition duration-150">

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center justify-center min-w-[90px] px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-sm font-bold">
                                        <?= htmlspecialchars($row['day']) ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    <?= htmlspecialchars($row['start_time']) ?> - <?= htmlspecialchars($row['end_time']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    <?= htmlspecialchars($row['class']) ?>
                                </td>

                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= htmlspecialchars($row['subject_name']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    <?= htmlspecialchars($row['teacher_name']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    <?= htmlspecialchars($row['building_name'] ?? 'Not Assigned') ?>
                                </td>
                                  <td class="px-6 py-4 text-slate-700">
    <?= htmlspecialchars($row['room_name']) ?>
</td>

<td class="px-6 py-4 text-slate-700">
    <?= htmlspecialchars($row['floor'] ?? 'N/A') ?>
</td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
