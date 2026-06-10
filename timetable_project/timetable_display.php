<?php
/**
 * Filtered timetable display page.
 * Lets admins query timetable rows by selected dimensions and export the result set.
 */
session_start();
include 'includes/auth_check.php';
include 'includes/config.php';
require_once 'includes/security.php';
require_once 'includes/export_helpers.php';
require_once 'includes/schema_helpers.php';

ensureBuildingRoomSupport($conn);
requireRole('admin');

/* ================= HEADER ================= */
$title = "Time Table of Winter Semester 2025-26";

/* ================= FILTERS ================= */
$filter_day        = $_GET['day'] ?? '';
$filter_building   = $_GET['building_id'] ?? '';
$filter_room       = $_GET['room_id'] ?? '';
$filter_faculty    = $_GET['faculty_id'] ?? '';
$filter_department = $_GET['department_id'] ?? '';
$filter_teacher_id = $_GET['teacher_id'] ?? '';
$filter_teacher    = $_GET['teacher'] ?? '';
$filter_class      = $_GET['class'] ?? '';

$requested_view = $_GET['view'] ?? '';
$view_type = 'room';
$allowed_views = ['day', 'building', 'room', 'faculty', 'department', 'teacher', 'class'];

/* ================= TIME SLOTS ================= */
$timeQuery = $conn->query("
    SELECT id, day, start_time, end_time, class_type
    FROM days_hours
    WHERE status='Active'
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time
");

$periods = [];
$days = [];

while ($t = $timeQuery->fetch_assoc()) {
    $periods[$t['id']] = $t['start_time'] . " - " . $t['end_time'];
    $days[$t['day']] = $t['day'];
}

/* ================= ROOMS ================= */
$buildingQuery = $conn->query("
    SELECT id, name
    FROM buildings
    WHERE status='Active'
    ORDER BY name
");

$buildings = [];

while ($b = $buildingQuery->fetch_assoc()) {
    $buildings[$b['id']] = $b;
}

/* ================= ROOMS ================= */
$roomQuery = $conn->query("
    SELECT r.id, r.building_id, r.room_name, r.floor, b.name AS building_name
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id = b.id
    ORDER BY b.name, r.floor, r.room_name
");

$rooms = [];

while ($r = $roomQuery->fetch_assoc()) {
    $rooms[$r['id']] = $r;
}

/* ================= FACULTIES ================= */
$facultyQuery = $conn->query("
    SELECT id, name
    FROM faculties
    ORDER BY name
");

$faculties = [];

while ($f = $facultyQuery->fetch_assoc()) {
    $faculties[$f['id']] = $f;
}

/* ================= DEPARTMENTS ================= */
$departmentQuery = $conn->query("
    SELECT id, name
    FROM departments
    ORDER BY name
");

$departments = [];

while ($d = $departmentQuery->fetch_assoc()) {
    $departments[$d['id']] = $d;
}

/* ================= TEACHERS ================= */
$teacherQuery = $conn->query("
    SELECT id, name
    FROM teachers
    ORDER BY name
");

$teachers = [];

while ($teacher = $teacherQuery->fetch_assoc()) {
    $teachers[$teacher['id']] = $teacher;
}

/* ================= CLASSES ================= */
$classQuery = $conn->query("
    SELECT DISTINCT class
    FROM timetable
    WHERE class IS NOT NULL AND class != ''
    ORDER BY class
");

$classes = [];

while ($classRow = $classQuery->fetch_assoc()) {
    $classes[$classRow['class']] = ['name' => $classRow['class']];
}

$dayRows = [];

foreach ($days as $day) {
    $dayRows[$day] = ['name' => $day];
}

$rowSets = [
    'day' => [
        'label' => 'Day',
        'items' => $dayRows,
        'name' => function ($day) {
            return $day['name'];
        },
        'key' => 'day',
        'filename' => 'day_timetable.xlsx',
    ],
    'room' => [
        'label' => 'Room',
        'items' => $rooms,
        'name' => function ($room) {
            return trim(($room['building_name'] ? $room['building_name'] . ' - ' : '') . $room['floor'] . ' - ' . $room['room_name']);
        },
        'key' => 'room_id',
        'filename' => 'room_timetable.xlsx',
    ],
    'building' => [
        'label' => 'Building',
        'items' => $buildings,
        'name' => function ($building) {
            return $building['name'];
        },
        'key' => 'building_id',
        'filename' => 'building_timetable.xlsx',
    ],
    'faculty' => [
        'label' => 'Faculty',
        'items' => $faculties,
        'name' => function ($faculty) {
            return $faculty['name'];
        },
        'key' => 'faculty_id',
        'filename' => 'faculty_timetable.xlsx',
    ],
    'department' => [
        'label' => 'Department',
        'items' => $departments,
        'name' => function ($department) {
            return $department['name'];
        },
        'key' => 'department_id',
        'filename' => 'department_timetable.xlsx',
    ],
    'teacher' => [
        'label' => 'Teacher',
        'items' => $teachers,
        'name' => function ($teacher) {
            return $teacher['name'];
        },
        'key' => 'teacher_id',
        'filename' => 'teacher_timetable.xlsx',
    ],
    'class' => [
        'label' => 'Class',
        'items' => $classes,
        'name' => function ($class) {
            return $class['name'];
        },
        'key' => 'class',
        'filename' => 'class_timetable.xlsx',
    ],
];

$view_type = in_array($requested_view, $allowed_views, true) ? $requested_view : 'room';

if (!empty($filter_class)) {
    $view_type = 'class';
} elseif (!empty($filter_teacher_id) || !empty($filter_teacher)) {
    $view_type = 'teacher';
} elseif (!empty($filter_department)) {
    $view_type = 'department';
} elseif (!empty($filter_faculty)) {
    $view_type = 'faculty';
} elseif (!empty($filter_room)) {
    $view_type = 'room';
} elseif (!empty($filter_building)) {
    $view_type = 'building';
}

$activeRows = $rowSets[$view_type];

function timetableExportSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_') ?: 'selected';
}

$selectedExportLabel = '';

if ($view_type === 'day' && !empty($filter_day) && isset($dayRows[$filter_day])) {
    $selectedExportLabel = $filter_day;
} elseif (!empty($filter_day) && empty($filter_building) && empty($filter_room) && empty($filter_faculty) && empty($filter_department) && empty($filter_teacher_id) && empty($filter_teacher) && empty($filter_class)) {
    $selectedExportLabel = $filter_day;
} elseif ($view_type === 'building' && !empty($filter_building) && isset($buildings[$filter_building])) {
    $selectedExportLabel = $buildings[$filter_building]['name'];
} elseif ($view_type === 'faculty' && !empty($filter_faculty) && isset($faculties[$filter_faculty])) {
    $selectedExportLabel = $faculties[$filter_faculty]['name'];
} elseif ($view_type === 'department' && !empty($filter_department) && isset($departments[$filter_department])) {
    $selectedExportLabel = $departments[$filter_department]['name'];
} elseif ($view_type === 'room' && !empty($filter_room) && isset($rooms[$filter_room])) {
    $selectedExportLabel = $rooms[$filter_room]['room_name'];
} elseif ($view_type === 'teacher' && !empty($filter_teacher_id) && isset($teachers[$filter_teacher_id])) {
    $selectedExportLabel = $teachers[$filter_teacher_id]['name'];
} elseif ($view_type === 'class' && !empty($filter_class) && isset($classes[$filter_class])) {
    $selectedExportLabel = $filter_class;
}

$exportTitle = $selectedExportLabel
    ? $selectedExportLabel . ' ' . $activeRows['label'] . ' ' . $title
    : $activeRows['label'] . ' ' . $title;

$exportFilename = $selectedExportLabel
    ? timetableExportSlug($selectedExportLabel) . '_' . strtolower($activeRows['label']) . '_timetable.xlsx'
    : $activeRows['filename'];

/* ================= FILTER QUERY ================= */
$where = [];
$params = [];
$types = "";

if (!empty($filter_day)) {
    $where[] = "dh.day = ?";
    $params[] = $filter_day;
    $types .= "s";
}

if (!empty($filter_room)) {
    $where[] = "tt.room_id = ?";
    $params[] = $filter_room;
    $types .= "i";
}

if (!empty($filter_building)) {
    $where[] = "r.building_id = ?";
    $params[] = $filter_building;
    $types .= "i";
}

if (!empty($filter_faculty)) {
    $where[] = "tt.faculty_id = ?";
    $params[] = $filter_faculty;
    $types .= "i";
}

if (!empty($filter_department)) {
    $where[] = "tt.department_id = ?";
    $params[] = $filter_department;
    $types .= "i";
}

if (!empty($filter_teacher_id)) {
    $where[] = "tt.teacher_id = ?";
    $params[] = $filter_teacher_id;
    $types .= "i";
}

if (!empty($filter_teacher)) {
    $where[] = "t.name LIKE ?";
    $params[] = "%" . $filter_teacher . "%";
    $types .= "s";
}

if (!empty($filter_class)) {
    $where[] = "tt.class = ?";
    $params[] = $filter_class;
    $types .= "s";
}

/* ================= MAIN QUERY ================= */
$query = "
SELECT
    dh.day,
    dh.id AS period_id,
    dh.class_type,
    tt.teacher_id,
    r.building_id,
    tt.room_id,
    tt.faculty_id,
    tt.department_id,
    tt.class,
    t.name AS teacher,
    s.code AS subject_code,
    s.name AS subject,
    r.room_name,
    r.floor,
    b.name AS building_name,
    f.name AS faculty_name,
    d.name AS department_name

FROM timetable tt

JOIN days_hours dh
    ON tt.day_hour_id = dh.id

LEFT JOIN teachers t
    ON tt.teacher_id = t.id

LEFT JOIN subjects s
    ON tt.subject_id = s.id

LEFT JOIN rooms r
    ON tt.room_id = r.id

LEFT JOIN buildings b
    ON r.building_id = b.id

LEFT JOIN faculties f
    ON tt.faculty_id = f.id

LEFT JOIN departments d
    ON tt.department_id = d.id

INNER JOIN timetable_versions tv
    ON tt.version_id = tv.id

WHERE tv.id = (
    SELECT id
    FROM timetable_versions
    WHERE status = 'active'
    ORDER BY generated_at DESC
    LIMIT 1
)
";

/* ================= APPLY FILTERS ================= */
if (!empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
}

/* ================= ORDER ================= */
$query .= "
ORDER BY
FIELD(dh.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
dh.start_time
";

/* ================= EXECUTE QUERY ================= */
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

/* ================= TIMETABLE DATA ================= */
$timetable = [];

while ($row = $result->fetch_assoc()) {
    $rowKey = (string) ($row[$activeRows['key']] ?? '');

    if ($rowKey === '') {
        continue;
    }

    $timetable[$row['day']][$rowKey][$row['period_id']] = [
        'class' => $row['class'],
        'class_type' => $row['class_type'],
        'subject_code' => $row['subject_code'],
        'subject' => $row['subject'],
        'teacher' => $row['teacher'],
        'room' => trim(($row['building_name'] ? $row['building_name'] . ' - ' : '') . ($row['floor'] ? $row['floor'] . ' - ' : '') . $row['room_name']),
        'building' => $row['building_name'],
        'faculty' => $row['faculty_name'],
        'department' => $row['department_name'],
    ];
}

$displayExportRows = [];

foreach ($days as $day) {
    if ($filter_day && $filter_day != $day) {
        continue;
    }

    foreach ($activeRows['items'] as $row_id => $rowItem) {
        if ($view_type === 'room' && $filter_room && $filter_room != $row_id) {
            continue;
        }

        if ($view_type === 'building' && $filter_building && $filter_building != $row_id) {
            continue;
        }

        if ($view_type === 'faculty' && $filter_faculty && $filter_faculty != $row_id) {
            continue;
        }

        if ($view_type === 'department' && $filter_department && $filter_department != $row_id) {
            continue;
        }

        if ($view_type === 'teacher' && $filter_teacher_id && $filter_teacher_id != $row_id) {
            continue;
        }

        if ($view_type === 'class' && $filter_class && $filter_class != $row_id) {
            continue;
        }

        if ($view_type === 'day' && $row_id != $day) {
            continue;
        }

        $exportRow = [
            'day' => $day,
            'row_label' => $activeRows['name']($rowItem),
        ];

        foreach ($periods as $pid => $slot) {
            $data = $timetable[$day][$row_id][$pid] ?? null;
            $subjectLabel = $data && !empty($data['subject_code'])
                ? $data['subject_code'] . ' - ' . $data['subject']
                : ($data['subject'] ?? '');
            $exportRow['period_' . $pid] = $data
                ? $data['class'] . ' | ' . $subjectLabel . ' | ' . $data['teacher'] . ' | ' . $data['room'] . ' | ' . $data['class_type'] . ' | ' . $data['faculty'] . ' | ' . $data['department']
                : '-';
        }

        $displayExportRows[] = $exportRow;
    }
}

$displayExportColumns = [
    ['label' => 'Day', 'field' => 'day'],
    ['label' => $activeRows['label'], 'field' => 'row_label'],
];

foreach ($periods as $pid => $slot) {
    $displayExportColumns[] = [
        'label' => $slot,
        'field' => 'period_' . $pid,
    ];
}

handleTableExport([
    'title' => $exportTitle,
    'filename' => $exportFilename,
    'columns' => $displayExportColumns,
    'rows' => $displayExportRows,
]);

include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="container mx-auto p-6">

    <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($title) ?></h2>

    <!-- ================= FILTER ================= -->

    <form method="GET" class="mb-4 flex gap-3 flex-wrap">

        <select name="day" class="border p-2 rounded">

            <option value="">Select Day</option>

            <?php foreach ($days as $d): ?>

                <option value="<?= htmlspecialchars($d) ?>" <?= ($filter_day == $d ? 'selected' : '') ?>>

                    <?= htmlspecialchars($d) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="room_id" class="border p-2 rounded">

            <option value="">Select Room</option>

            <?php foreach ($rooms as $rid => $r): ?>

                <option value="<?= $rid ?>" <?= ($filter_room == $rid ? 'selected' : '') ?>>

                    <?= htmlspecialchars(trim(($r['building_name'] ? $r['building_name'] . ' - ' : '') . $r['floor'] . " - " . $r['room_name'])) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="building_id" class="border p-2 rounded">

            <option value="">Select Building</option>

            <?php foreach ($buildings as $bid => $b): ?>

                <option value="<?= $bid ?>" <?= ($filter_building == $bid ? 'selected' : '') ?>>

                    <?= htmlspecialchars($b['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="faculty_id" class="border p-2 rounded">

            <option value="">Select Faculty</option>

            <?php foreach ($faculties as $fid => $f): ?>

                <option value="<?= $fid ?>" <?= ($filter_faculty == $fid ? 'selected' : '') ?>>

                    <?= htmlspecialchars($f['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="department_id" class="border p-2 rounded">

            <option value="">Select Department</option>

            <?php foreach ($departments as $did => $d): ?>

                <option value="<?= $did ?>" <?= ($filter_department == $did ? 'selected' : '') ?>>

                    <?= htmlspecialchars($d['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="teacher_id" class="border p-2 rounded">

            <option value="">Select Teacher</option>

            <?php foreach ($teachers as $tid => $teacher): ?>

                <option value="<?= $tid ?>" <?= ($filter_teacher_id == $tid ? 'selected' : '') ?>>

                    <?= htmlspecialchars($teacher['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select name="class" class="border p-2 rounded">

            <option value="">Select Class</option>

            <?php foreach ($classes as $className => $class): ?>

                <option value="<?= htmlspecialchars($className) ?>" <?= ($filter_class == $className ? 'selected' : '') ?>>

                    <?= htmlspecialchars($class['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">

            Search

        </button>

        <a
            href="timetable_display.php"
            class="bg-gray-500 text-white px-4 py-2 rounded"
        >
            Reset
        </a>

    </form>

    <!-- ================= EXPORT BUTTONS ================= -->

    <div class="mb-4 flex gap-3">

        <a
            href="?<?= http_build_query(array_merge($_GET, ['view' => $view_type, 'export' => 'excel'])) ?>"
            class="bg-green-600 text-white px-4 py-2 rounded"
        >
            Export Excel
        </a>

        <a
            href="?<?= http_build_query(array_merge($_GET, ['view' => $view_type, 'export' => 'pdf'])) ?>"
            class="bg-red-600 text-white px-4 py-2 rounded"
        >
            Export PDF
        </a>

        <a
            href="?<?= http_build_query(array_merge($_GET, ['view' => $view_type, 'export' => 'print'])) ?>"
            class="bg-blue-600 text-white px-4 py-2 rounded"
        >
            Print
        </a>

    </div>

    <!-- ================= TIMETABLE ================= -->

    <div class="overflow-auto border rounded shadow max-h-[70vh]">

        <table class="w-full text-sm border-collapse">

            <thead class="bg-blue-200 sticky top-0">

                <tr>

                    <th class="border p-2">Day</th>
                    <th class="border p-2"><?= htmlspecialchars($activeRows['label']) ?></th>

                    <?php foreach ($periods as $slot): ?>

                        <th class="border p-2 min-w-40"><?= htmlspecialchars($slot) ?></th>

                    <?php endforeach; ?>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($days as $day): ?>

                <?php if ($filter_day && $filter_day != $day) continue; ?>

                <?php foreach ($activeRows['items'] as $row_id => $rowItem): ?>

                    <?php if ($view_type === 'room' && $filter_room && $filter_room != $row_id) continue; ?>
                    <?php if ($view_type === 'building' && $filter_building && $filter_building != $row_id) continue; ?>
                    <?php if ($view_type === 'faculty' && $filter_faculty && $filter_faculty != $row_id) continue; ?>
                    <?php if ($view_type === 'department' && $filter_department && $filter_department != $row_id) continue; ?>
                    <?php if ($view_type === 'teacher' && $filter_teacher_id && $filter_teacher_id != $row_id) continue; ?>
                    <?php if ($view_type === 'class' && $filter_class && $filter_class != $row_id) continue; ?>
                    <?php if ($view_type === 'day' && $row_id != $day) continue; ?>

                    <tr>

                        <td class="border p-2 font-bold bg-gray-50">

                            <?= htmlspecialchars($day) ?>

                        </td>

                        <td class="border p-2 bg-gray-50 font-semibold">

                            <?= htmlspecialchars($activeRows['name']($rowItem)) ?>

                        </td>

                        <?php foreach ($periods as $pid => $slot): ?>

                            <?php
                            $data = $timetable[$day][$row_id][$pid] ?? null;
                            $subjectLabel = $data && !empty($data['subject_code'])
                                ? $data['subject_code'] . ' - ' . $data['subject']
                                : ($data['subject'] ?? '');
                            ?>

                            <td class="border p-2 text-center align-top">

                                <?php if ($data): ?>

                                    <b><?= htmlspecialchars($data['class']) ?></b><br>

                                    <small><?= htmlspecialchars($subjectLabel) ?></small><br>

                                    <small><?= htmlspecialchars($data['teacher']) ?></small><br>

                                    <small><?= htmlspecialchars($data['room']) ?></small><br>

                                    <small><b>Type:</b> <?= htmlspecialchars($data['class_type']) ?></small><br>

                                    <small><b>Faculty:</b> <?= htmlspecialchars($data['faculty']) ?></small><br>

                                    <small><b>Department:</b> <?= htmlspecialchars($data['department']) ?></small>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                        <?php endforeach; ?>

                    </tr>

                <?php endforeach; ?>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
