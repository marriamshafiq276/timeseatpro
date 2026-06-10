<?php
/**
 * Admin timetable generation controller.
 * Loads active master data, runs the genetic algorithm, and stores a versioned result.
 */
session_start();
include 'includes/auth_check.php';
include 'includes/config.php';
require_once 'includes/security.php';
require_once 'includes/schema_helpers.php';
require_once 'classes/TimetableGA.php';

requireRole('admin', 'login.php');
ensureBuildingRoomSupport($conn);

if (isset($_POST['generate'])) {
    requireCsrfForPost();

    // Master datasets
    $master = [
        'teachers'    => $conn->query("SELECT * FROM teachers WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'subjects'    => $conn->query("SELECT * FROM subjects WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'rooms'       => $conn->query("
            SELECT r.*, b.name AS building_name
            FROM rooms r
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE r.status='Active'
        ")->fetch_all(MYSQLI_ASSOC),
        'classes'     => $conn->query("SELECT * FROM classes WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'faculties'   => $conn->query("SELECT * FROM faculties WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'departments' => $conn->query("SELECT * FROM departments WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'activities'  => $conn->query("SELECT * FROM activities WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'time_constraints' => $conn->query("SELECT * FROM time_constraints WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'space_constraints' => $conn->query("SELECT * FROM space_constraints WHERE status='Active'")->fetch_all(MYSQLI_ASSOC),
        'student_groups' => $conn->query("SELECT class,total_students FROM students WHERE status='Active'")->fetch_all(MYSQLI_ASSOC)
    ];

    $days_hours = $conn->query("SELECT * FROM days_hours WHERE status='Active'")->fetch_all(MYSQLI_ASSOC);
    $master['days'] = array_unique(array_column($days_hours, 'day'));
    $periodNumbers = [];
    foreach ($days_hours as $dh) {
        $periodNumbers[$dh['day']] = ($periodNumbers[$dh['day']] ?? 0) + 1;
        $master['day_period_map'][$dh['day']][] = [
            'id' => $dh['id'],
            'class_type' => $dh['class_type'],
            'period_number' => $periodNumbers[$dh['day']]
        ];
    }

    $scope = $_POST['scope'] ?? 'combined';
    $generated_versions = [];

    $run_and_save = function($data, $label) use ($conn, &$generated_versions) {
        try {
            $ga = new TimetableGA($data);
            $best_timetable = $ga->generate();
            $conflict_report = $ga->analyzeConflicts($best_timetable);
        } catch (Exception $e) {
            $_SESSION['generation_error'] = $e->getMessage();
            return null;
        }

        $version_name = "Timetable - {$label} " . date("Y-m-d H:i:s");
        $generated_by = $_SESSION['username'] ?? 'Admin';

        $version_stmt = $conn->prepare("INSERT INTO timetable_versions (version_name, generated_by) VALUES (?, ?)");
        $version_stmt->bind_param("ss", $version_name, $generated_by);
        $version_stmt->execute();
        $version_id = $conn->insert_id;
        $version_stmt->close();

        $stmt = $conn->prepare("INSERT INTO timetable (version_id, teacher_id, room_id, day_hour_id, subject_id, activity_id, class, faculty_id, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($best_timetable as $entry) {
            $activity_id = $entry['activity_id'] ?? NULL;
            $stmt->bind_param("iiiiiisii", $version_id, $entry['teacher_id'], $entry['room_id'], $entry['day_hour_id'], $entry['subject_id'], $activity_id, $entry['class'], $entry['faculty_id'], $entry['department_id']);
            $stmt->execute();
        }
        $stmt->close();

        $total_entries = count($best_timetable);
        $update_stmt = $conn->prepare("UPDATE timetable_versions SET total_entries = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $total_entries, $version_id);
        $update_stmt->execute();
        $update_stmt->close();

        $generated_versions[] = ['version_id' => $version_id, 'label' => $label, 'conflicts' => $conflict_report];
        return $version_id;
    };

    if ($scope === 'combined') {
        $vid = $run_and_save($master, 'Combined');
        if ($vid === null) { header("Location: generate_timetable.php"); exit(); }
    } elseif ($scope === 'per_faculty') {
        foreach ($master['faculties'] as $faculty) {
            $fid = (int) $faculty['id'];
            $fac_depts = array_values(array_filter($master['departments'], function($d) use ($fid){ return (int)$d['faculty_id'] === $fid; }));
            $dept_names = array_map(function($d){ return strtolower((string)$d['name']); }, $fac_depts);

            $data = $master;
            $data['faculties'] = [$faculty];
            $data['departments'] = $fac_depts;
            $data['classes'] = array_values(array_filter($master['classes'], function($c) use ($fid){ return (int)$c['faculty_id'] === $fid; }));
            $data['subjects'] = array_values(array_filter($master['subjects'], function($s) use ($dept_names){ return in_array(strtolower((string)$s['department']), $dept_names, true); }));
            $data['teachers'] = array_values(array_filter($master['teachers'], function($t) use ($dept_names){ return in_array(strtolower((string)$t['department']), $dept_names, true); }));
            $data['student_groups'] = array_values(array_filter($master['student_groups'], function($g) use ($data){ foreach($data['classes'] as $c){ if ((string)$g['class'] === $c['class_name'] || strpos((string)$g['class'], $c['class_name'].' -') === 0) return true;} return false; }));

            $run_and_save($data, 'Faculty: ' . $faculty['name']);
        }
    } else { // per_department
        foreach ($master['departments'] as $dept) {
            $faculty = null;
            foreach ($master['faculties'] as $f) if ((int)$f['id'] === (int)$dept['faculty_id']) { $faculty = $f; break; }

            $data = $master;
            $data['faculties'] = $faculty ? [$faculty] : [];
            $data['departments'] = [$dept];
            $dept_name = strtolower((string)$dept['name']);

            $data['subjects'] = array_values(array_filter($master['subjects'], function($s) use ($dept_name){ return strtolower((string)$s['department']) === $dept_name; }));
            $data['teachers'] = array_values(array_filter($master['teachers'], function($t) use ($dept_name){ return strtolower((string)$t['department']) === $dept_name; }));
            $data['classes'] = array_values(array_filter($master['classes'], function($c) use ($faculty){ return $faculty && (int)$c['faculty_id'] === (int)$faculty['id']; }));
            $data['student_groups'] = array_values(array_filter($master['student_groups'], function($g) use ($data){ foreach($data['classes'] as $c){ if ((string)$g['class'] === $c['class_name'] || strpos((string)$g['class'], $c['class_name'].' -') === 0) return true;} return false; }));

            $run_and_save($data, 'Department: ' . $dept['name']);
        }
    }

    $_SESSION['timetable_generated'] = true;
    $_SESSION['generated_versions'] = $generated_versions;
    $_SESSION['latest_version_id'] = end($generated_versions)['version_id'] ?? $vid ?? null;
    $_SESSION['conflict_report'] = end($generated_versions)['conflicts'] ?? $conflict_report ?? null;

    header("Location: generate_timetable.php");
    exit();
}

include 'includes/header.php';
include 'includes/nav.php';
?>

<!-- UI -->
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4 text-center">Generate Timetable</h2>
    <form method="POST" class="text-center">
        <?= csrfInput() ?>
        <div class="max-w-md mx-auto mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Generation Scope</label>
            <select name="scope" class="w-full border p-2 rounded">
                <option value="combined">Combined (single timetable)</option>
                <option value="per_faculty">Per Faculty (separate timetable per faculty)</option>
                <option value="per_department">Per Department (separate timetable per department)</option>
            </select>
        </div>
        <button type="submit" name="generate" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700">Generate Now</button>
    </form>

    <?php if (isset($_SESSION['generation_error'])): ?>
        <div class="max-w-3xl mx-auto mt-6 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded">
            <?= htmlspecialchars($_SESSION['generation_error']) ?>
        </div>
        <?php unset($_SESSION['generation_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['conflict_report'])): ?>
        <?php $report = $_SESSION['conflict_report']; ?>
        <div class="max-w-5xl mx-auto mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border rounded shadow p-4">
                <p class="text-xs uppercase text-gray-500 font-bold">Teacher Clashes</p>
                <p class="text-2xl font-bold text-emerald-700"><?= (int) $report['teacher_clashes'] ?></p>
            </div>
            <div class="bg-white border rounded shadow p-4">
                <p class="text-xs uppercase text-gray-500 font-bold">Room Clashes</p>
                <p class="text-2xl font-bold text-emerald-700"><?= (int) $report['room_clashes'] ?></p>
            </div>
            <div class="bg-white border rounded shadow p-4">
                <p class="text-xs uppercase text-gray-500 font-bold">Class Clashes</p>
                <p class="text-2xl font-bold text-emerald-700"><?= (int) $report['class_clashes'] ?></p>
            </div>
            <div class="bg-white border rounded shadow p-4">
                <p class="text-xs uppercase text-gray-500 font-bold">Fitness Score</p>
                <p class="text-2xl font-bold text-emerald-700"><?= (int) $report['fitness'] ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['latest_version_id'])): ?>
        <div class="text-center mt-4">
            <a href="view_timetable.php?version_id=<?php echo $_SESSION['latest_version_id']; ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg">👀 View Timetable</a>
        </div>
    <?php endif; ?>

<?php include 'includes/footer.php'; ?>
