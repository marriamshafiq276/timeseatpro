<?php
/**
 * Admin timetable viewer.
 * Displays generated timetable entries, conflict summaries, edits, deletes, and exports.
 */
session_start();
include 'includes/auth_check.php';
require_once 'includes/security.php';

requireRole('admin', 'login.php');

include 'includes/config.php';
require_once 'includes/export_helpers.php';
require_once 'includes/schema_helpers.php';

ensureBuildingRoomSupport($conn);

$versions = $conn->query("
    SELECT *
    FROM timetable_versions
    ORDER BY id DESC
    LIMIT 10
");
/* ================= GET VERSION ================= */

$version_id = intval($_GET['version_id'] ?? 0);

/* If no version selected, show latest version */

if ($version_id == 0) {

    $latestVersion = $conn->query("
        SELECT id
        FROM timetable_versions
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($latestVersion->num_rows > 0) {

        $latest = $latestVersion->fetch_assoc();

        $version_id = $latest['id'];

    } else {

        die("No timetable version found.");

    }
}


/* Fetch Version Details */

$versionQuery = $conn->prepare("
    SELECT *
    FROM timetable_versions
    WHERE id=?
");

$versionQuery->bind_param("i", $version_id);

$versionQuery->execute();

$versionData = $versionQuery->get_result()->fetch_assoc();

/* ================= AJAX HANDLER ================= */
if (isset($_POST['ajax'])) {

    header('Content-Type: application/json');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        jsonError('Security token expired. Refresh the page and try again.', 403);
    }

    $action = $_POST['action'] ?? '';

    /* ================= UPDATE ================= */
    if ($action == 'update') {

        $activity_id = !empty($_POST['activity_id']) ? $_POST['activity_id'] : NULL;

        $stmt = $conn->prepare("
            UPDATE timetable 
            SET 
                teacher_id=?,
                subject_id=?,
                room_id=?,
                activity_id=?,
                day_hour_id=?,
                class=?,
                faculty_id=?,
                department_id=?,
                room_display=?,
                subject_display=?,
                class_display=?,
                status=?
            WHERE id=? AND version_id=?
        ");

        if (!$stmt) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit();
        }

        // FIXED TYPES (IMPORTANT)
        $stmt->bind_param(
            "iiiiisiissssii",
            $_POST['teacher_id'],
            $_POST['subject_id'],
            $_POST['room_id'],
            $activity_id,
            $_POST['day_hour_id'],
            $_POST['class'],
            $_POST['faculty_id'],
            $_POST['department_id'],
            $_POST['room_display'],
            $_POST['subject_display'],
            $_POST['class_display'],
            $_POST['status'],
            $_POST['id'],
            $version_id
        );

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();
        exit();
    }

    /* ================= DELETE ================= */
    if ($action == 'delete') {

        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM timetable WHERE id=? AND version_id=?");
        $stmt->bind_param("ii", $id, $version_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();
        exit();
    }
}

/* ================= FETCH DATA ================= */
$teachers     = $conn->query("SELECT id,name FROM teachers ORDER BY name ASC");
$rooms        = $conn->query("
    SELECT r.id,r.room_name,r.floor,b.name AS building_name
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id=b.id
    ORDER BY b.name ASC, r.room_name ASC
");
$subjects     = $conn->query("SELECT id,code,name FROM subjects ORDER BY name ASC");
$activities   = $conn->query("SELECT id,name FROM activities ORDER BY name ASC");
$faculties    = $conn->query("SELECT id,name FROM faculties ORDER BY name ASC");
$departments  = $conn->query("SELECT id,name FROM departments ORDER BY name ASC");
$dayshours    = $conn->query("SELECT * FROM days_hours ORDER BY day ASC, start_time ASC");

/* ================= MAIN QUERY ================= */

$query = "
SELECT 
    tt.*,
    t.name AS teacher_name,
    r.room_name,
    r.floor,
    b.name AS building_name,
    s.name AS subject_name,
    s.code AS subject_code,
    a.name AS activity_name,
    f.name AS faculty_name,
    d.name AS department_name,
    dh.day,
    dh.start_time,
    dh.end_time,
    dh.class_type

FROM timetable tt

LEFT JOIN teachers t 
    ON tt.teacher_id=t.id

LEFT JOIN rooms r 
    ON tt.room_id=r.id

LEFT JOIN buildings b
    ON r.building_id=b.id

LEFT JOIN subjects s 
    ON tt.subject_id=s.id

LEFT JOIN activities a 
    ON tt.activity_id=a.id

LEFT JOIN faculties f 
    ON tt.faculty_id=f.id

LEFT JOIN departments d 
    ON tt.department_id=d.id

LEFT JOIN days_hours dh 
    ON tt.day_hour_id=dh.id

WHERE tt.version_id = ?

ORDER BY dh.day, dh.start_time
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $version_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query Failed: " . $conn->error);
}

/* ================= EXPORT QUERY ================= */

$exportQuery = "
SELECT 
    tt.class,

    t.name AS teacher_name,

    r.room_name,
    r.floor,
    b.name AS building_name,

    s.name AS subject_name,
    s.code AS subject_code,

    a.name AS activity_name,

    f.name AS faculty_name,

    d.name AS department_name,

    dh.day,
    dh.start_time,
    dh.end_time,
    dh.class_type

FROM timetable tt

LEFT JOIN teachers t 
    ON tt.teacher_id=t.id

LEFT JOIN rooms r 
    ON tt.room_id=r.id

LEFT JOIN buildings b
    ON r.building_id=b.id

LEFT JOIN subjects s 
    ON tt.subject_id=s.id

LEFT JOIN activities a 
    ON tt.activity_id=a.id

LEFT JOIN faculties f 
    ON tt.faculty_id=f.id

LEFT JOIN departments d 
    ON tt.department_id=d.id

LEFT JOIN days_hours dh 
    ON tt.day_hour_id=dh.id

WHERE tt.version_id = ?

ORDER BY dh.day, dh.start_time
";

$exportStmt = $conn->prepare($exportQuery);
$exportStmt->bind_param("i", $version_id);
$exportStmt->execute();
$exportResult = $exportStmt->get_result();

if (!$exportResult) {
    die("Export Query Failed: " . $conn->error);
}

$timetableExportColumns = [
    ['label' => 'Day', 'field' => 'day'],
    ['label' => 'Time', 'value' => fn($row) => $row['start_time'] . ' - ' . $row['end_time']],
    ['label' => 'Class', 'field' => 'class'],
    ['label' => 'Faculty', 'field' => 'faculty_name'],
    ['label' => 'Department', 'field' => 'department_name'],
    ['label' => 'Subject', 'value' => fn($row) => $row['subject_code'] . ' - ' . $row['subject_name']],
    ['label' => 'Teacher', 'field' => 'teacher_name'],
    ['label' => 'Building', 'field' => 'building_name'],
    ['label' => 'Room', 'value' => fn($row) => $row['room_name'] . ' (' . $row['floor'] . ')'],
    ['label' => 'Activity', 'field' => 'activity_name'],
    ['label' => 'Type', 'field' => 'class_type'],
];

handleTableExport([
    'title' => 'Timetable Report',
    'filename' => 'timetable.xlsx',
    'columns' => $timetableExportColumns,
    'rows' => $exportResult,
]);

$conflictSummary = [
    'teacher_clashes' => 0,
    'room_clashes' => 0,
    'class_clashes' => 0,
];

$conflictQueries = [
    'teacher_clashes' => "SELECT COUNT(*) - COUNT(DISTINCT teacher_id, day_hour_id) AS clashes FROM timetable WHERE version_id = ?",
    'room_clashes' => "SELECT COUNT(*) - COUNT(DISTINCT room_id, day_hour_id) AS clashes FROM timetable WHERE version_id = ?",
    'class_clashes' => "SELECT COUNT(*) - COUNT(DISTINCT class, day_hour_id) AS clashes FROM timetable WHERE version_id = ?",
];

foreach ($conflictQueries as $key => $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $version_id);
    $stmt->execute();
    $conflictSummary[$key] = (int) ($stmt->get_result()->fetch_assoc()['clashes'] ?? 0);
    $stmt->close();
}



include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="bg-white p-4 rounded shadow mb-6">

    <h2 class="text-lg font-bold mb-3 text-emerald-700">
        Timetable Versions
    </h2>

    <div class="flex flex-wrap gap-2">

        <?php while($v = $versions->fetch_assoc()): ?>

            <a href="view_timetable.php?version_id=<?= $v['id'] ?>"
               class="px-4 py-2 rounded text-white
               <?= ($version_id == $v['id'])
                    ? 'bg-emerald-700' 
                    : 'bg-gray-500' ?>">

                <?= htmlspecialchars($v['version_name']) ?>

            </a>

        <?php endwhile; ?>

    </div>

</div>




<!-- ================= UI (UNCHANGED) ================= -->
<div class="bg-gray-100 min-h-screen py-10 px-4">

<div class="max-w-7xl mx-auto text-center">

    <h1 class="text-3xl font-bold text-emerald-800 mb-8">
        View Timetable
    </h1>
    <div class="bg-white shadow rounded-lg p-4 mb-6 border">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">

        <div>
            <span class="font-bold text-gray-700">
                Version:
            </span>

            <?= htmlspecialchars($versionData['version_name']) ?>
        </div>

        <div>
            <span class="font-bold text-gray-700">
                Generated By:
            </span>

            <?= htmlspecialchars($versionData['generated_by']) ?>
        </div>

        <div>
            <span class="font-bold text-gray-700">
                Generated At:
            </span>

            <?= htmlspecialchars($versionData['generated_at']) ?>
        </div>

        <div>
            <span class="font-bold text-gray-700">
                Total Entries:
            </span>

            <?= htmlspecialchars($versionData['total_entries']) ?>
        </div>

    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border rounded-lg p-4 shadow">
        <p class="text-xs uppercase text-gray-500 font-bold">Teacher Clashes</p>
        <p class="text-2xl font-bold <?= $conflictSummary['teacher_clashes'] === 0 ? 'text-emerald-700' : 'text-red-700' ?>">
            <?= $conflictSummary['teacher_clashes'] ?>
        </p>
    </div>
    <div class="bg-white border rounded-lg p-4 shadow">
        <p class="text-xs uppercase text-gray-500 font-bold">Room Clashes</p>
        <p class="text-2xl font-bold <?= $conflictSummary['room_clashes'] === 0 ? 'text-emerald-700' : 'text-red-700' ?>">
            <?= $conflictSummary['room_clashes'] ?>
        </p>
    </div>
    <div class="bg-white border rounded-lg p-4 shadow">
        <p class="text-xs uppercase text-gray-500 font-bold">Class Clashes</p>
        <p class="text-2xl font-bold <?= $conflictSummary['class_clashes'] === 0 ? 'text-emerald-700' : 'text-red-700' ?>">
            <?= $conflictSummary['class_clashes'] ?>
        </p>
    </div>
</div>
<!-- ================= EXPORT BUTTONS ================= -->
<div class="flex gap-3 mb-4 justify-end">

    <a href="?export=excel&version_id=<?= $version_id ?>"
       class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">

        ⬇ Export Excel

    </a>

    <a href="?export=pdf&version_id=<?= $version_id ?>"
       class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg shadow">

        ⬇ Export PDF

    </a>
<a href="?export=print&version_id=<?= $version_id ?>"
   class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow">
    🖨 Print
</a>
</div>


    <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-300">

        <div class="overflow-x-auto p-4">

            <table id="timetableTable" class="min-w-full text-sm border border-gray-300">

                <thead class="bg-emerald-600 text-white">
                    <tr>
                        <th class="border px-5 py-3">Day</th>
                        <th class="border px-5 py-3">Time</th>
                        <th class="border px-5 py-3">Class</th>
                        <th class="border px-5 py-3">Faculty</th>
                        <th class="border px-5 py-3">Department</th>
                        <th class="border px-5 py-3">Subject</th>
                        <th class="border px-5 py-3">Teacher</th>
                        <th class="border px-5 py-3">Building</th>
                        <th class="border px-5 py-3">Room</th>
                        <th class="border px-5 py-3">Activity</th>
                        <th class="border px-5 py-3">Type</th>
                        <th class="border px-5 py-3">Status</th>
                        <th class="border px-5 py-3">Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php while($row = $result->fetch_assoc()): ?>
                    <tr id="row<?= $row['id'] ?>">

                        <td class="border px-3 py-2 text-emerald-700"><?= $row['day'] ?></td>
                        <td class="border px-3 py-2 text-purple-700">
                            <?= $row['start_time']." - ".$row['end_time'] ?>
                        </td>
                        <td class="border px-3 py-2"><?= $row['class'] ?></td>
                        <td class="border px-3 py-2"><?= $row['faculty_name'] ?></td>
                        <td class="border px-3 py-2"><?= $row['department_name'] ?></td>
                        <td class="border px-3 py-2 text-emerald-700">
                            <?= $row['subject_code']." - ".$row['subject_name'] ?>
                        </td>
                        <td class="border px-3 py-2"><?= $row['teacher_name'] ?></td>
                        <td class="border px-3 py-2"><?= htmlspecialchars($row['building_name'] ?? 'Not Assigned') ?></td>
                        <td class="border px-3 py-2"><?= $row['room_name']." (".$row['floor'].")" ?></td>
                        <td class="border px-3 py-2"><?= $row['activity_name'] ?></td>
                        <td class="border px-3 py-2"><?= $row['class_type'] ?></td>
                        <td class="border px-3 py-2"><?= htmlspecialchars($row['status']) ?></td>

                        <td class="border px-3 py-2 flex gap-2 justify-center">

                            <button class="bg-emerald-600 text-white px-3 py-1 rounded editBtn"
                                data-id="<?= $row['id'] ?>"
                                data-teacher="<?= $row['teacher_id'] ?>"
                                data-subject="<?= $row['subject_id'] ?>"
                                data-room="<?= $row['room_id'] ?>"
                                data-activity="<?= $row['activity_id'] ?>"
                                data-dayhour="<?= $row['day_hour_id'] ?>"
                                data-class="<?= htmlspecialchars($row['class'], ENT_QUOTES) ?>"
                                data-faculty="<?= $row['faculty_id'] ?>"
                                data-department="<?= $row['department_id'] ?>"
                                data-roomdisplay="<?= htmlspecialchars($row['room_display'] ?? '', ENT_QUOTES) ?>"
                                data-subjectdisplay="<?= htmlspecialchars($row['subject_display'] ?? '', ENT_QUOTES) ?>"
                                data-classdisplay="<?= htmlspecialchars($row['class_display'] ?? '', ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($row['status'] ?? 'Active', ENT_QUOTES) ?>"
                                data-subjectcode="<?= $row['subject_code'] ?>"
                                data-classtype="<?= $row['class_type'] ?>">
                                Edit
                            </button>

                            <button class="bg-red-600 text-white px-3 py-1 rounded deleteBtn"
                                data-id="<?= $row['id'] ?>">
                                Delete
                            </button>

                        </td>

                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</div>

<!-- ================= EDIT MODAL ================= -->
<div id="editModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">

    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-4xl my-10">

        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-bold text-emerald-700">Edit Timetable Entry</h2>
            <button type="button" id="closeModal" class="text-red-600 text-3xl font-bold">&times;</button>
        </div>

        <form id="editForm">

            <input type="hidden" id="id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block mb-2 font-semibold">Class</label>
                    <input id="class" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Teacher</label>
                    <select id="teacher_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($t=$teachers->fetch_assoc()){ ?>
                            <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Subject</label>
                    <select id="subject_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($s=$subjects->fetch_assoc()){ ?>
                            <option value="<?= $s['id'] ?>">
                                <?= $s['code']." - ".$s['name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Room</label>
                    <select id="room_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($r=$rooms->fetch_assoc()){ ?>
                            <option value="<?= $r['id'] ?>">
                                <?= htmlspecialchars(trim(($r['building_name'] ? $r['building_name'] . ' - ' : '') . $r['room_name'])) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Activity</label>
                    <select id="activity_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <option value="">None</option>
                        <?php while($a=$activities->fetch_assoc()){ ?>
                            <option value="<?= $a['id'] ?>"><?= $a['name'] ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Day & Time</label>
                    <select id="day_hour_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($d=$dayshours->fetch_assoc()){ ?>
                            <option value="<?= $d['id'] ?>">
                                <?= htmlspecialchars($d['day']) ?> - <?= htmlspecialchars($d['start_time']) ?> to <?= htmlspecialchars($d['end_time']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Faculty</label>
                    <select id="faculty_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($f=$faculties->fetch_assoc()){ ?>
                            <option value="<?= $f['id'] ?>"><?= $f['name'] ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Department</label>
                    <select id="department_id" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <?php while($d=$departments->fetch_assoc()){ ?>
                            <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Subject Code</label>
                    <input id="subject_code_display" class="border border-gray-300 w-full px-4 py-2 bg-gray-100 rounded-lg" readonly>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Class Type</label>
                    <input id="class_type_display" class="border border-gray-300 w-full px-4 py-2 bg-gray-100 rounded-lg" readonly>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Room Display</label>
                    <input id="room_display" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Subject Display</label>
                    <input id="subject_display" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Class Display</label>
                    <input id="class_display" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Status</label>
                    <select id="status" class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

            </div>

        <div class="flex gap-3 mt-6">

    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white w-full py-3 rounded-lg font-semibold">
        Update Record
    </button>

    <button type="button" id="cancelBtn"
        class="bg-gray-500 hover:bg-gray-600 text-white w-full py-3 rounded-lg font-semibold">
        Cancel
    </button>

</div>

        </form>

    </div>
</div>

<!-- ================= JS ================= -->
<script>
$(document).ready(function(){
    const csrfToken = '<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>';

    let table = $('#timetableTable').DataTable({
    pageLength: 10,
    lengthMenu: [
        [5, 10, 25, 50, 100, 200, 500, 1000, -1],
        [5, 10, 25, 50, 100, 200, 500, 1000, "All"]
    ],
    responsive: true,
    autoWidth: false,
    stateSave: true
});

    $(document).on('click','.editBtn',function(){

        $('#id').val($(this).data('id'));
        $('#teacher_id').val($(this).data('teacher'));
        $('#subject_id').val($(this).data('subject'));
        $('#room_id').val($(this).data('room'));
        $('#activity_id').val($(this).data('activity'));
        $('#day_hour_id').val($(this).data('dayhour'));
        $('#class').val($(this).data('class'));
        $('#faculty_id').val($(this).data('faculty'));
        $('#department_id').val($(this).data('department'));
        $('#room_display').val($(this).data('roomdisplay'));
        $('#subject_display').val($(this).data('subjectdisplay'));
        $('#class_display').val($(this).data('classdisplay'));
        $('#status').val($(this).data('status') || 'Active');

        $('#subject_code_display').val($(this).data('subjectcode'));
        $('#class_type_display').val($(this).data('classtype'));

        $('#editModal').removeClass('hidden').addClass('flex');
    });

    $('#closeModal, #cancelBtn').click(function(){
        $('#editModal')
            .removeClass('flex')
            .addClass('hidden');
    });

    $('#editForm').submit(function(e){
        e.preventDefault();

        $.ajax({
            url:'',
            type:'POST',
            dataType:'json',
            data:{
                ajax:1,
                csrf_token: csrfToken,
                action:'update',
                id:$('#id').val(),
                teacher_id:$('#teacher_id').val(),
                subject_id:$('#subject_id').val(),
                room_id:$('#room_id').val(),
                activity_id:$('#activity_id').val(),
                day_hour_id:$('#day_hour_id').val(),
                class:$('#class').val(),
                faculty_id:$('#faculty_id').val(),
                department_id:$('#department_id').val(),
                room_display:$('#room_display').val(),
                subject_display:$('#subject_display').val(),
                class_display:$('#class_display').val(),
                status:$('#status').val()
            },
            success:function(res){
                if(res.status=='success'){
                    table.state.save();
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error:function(xhr){
                alert(xhr.responseJSON?.message || 'Update failed.');
            }
        });

    });

    $(document).on('click','.deleteBtn',function(){
        if(!confirm('Delete this timetable entry?')) return;

        $.ajax({
            url:'',
            type:'POST',
            dataType:'json',
            data:{
                ajax:1,
                csrf_token: csrfToken,
                action:'delete',
                id:$(this).data('id')
            },
            success:function(res){
                if(res.status=='success'){
                    table.state.save();
                    location.reload();
                } else {
                    alert(res.message || 'Delete failed.');
                }
            },
            error:function(xhr){
                alert(xhr.responseJSON?.message || 'Delete failed.');
            }
        });
    });

});

</script>

<?php include 'includes/footer.php'; ?>
