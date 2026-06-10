<?php
/**
 * Admin seating-plan viewer.
 * Displays generated seat allocations with edit/delete controls and export options.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/auth_check.php';
require_once 'includes/security.php';

requireRole('admin', 'login.php');


include 'includes/config.php';
require_once 'includes/export_helpers.php';
require_once 'includes/schema_helpers.php';

ensureBuildingRoomSupport($conn);


$action = $_POST['action'] ?? '';
/* ================= VERSION SYSTEM ================= */

$versions = $conn->query("
    SELECT * FROM seating_versions
    ORDER BY id DESC
    LIMIT 10
");

$version_id = intval($_GET['version_id'] ?? 0);

/* load latest version if none selected */
if ($version_id == 0) {
    $latest = $conn->query("
        SELECT id FROM seating_versions
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($latest && $latest->num_rows > 0) {
        $version_id = $latest->fetch_assoc()['id'];
    }
}

/* ================= VERSION DETAILS ================= */

$versionData = null;

if ($version_id) {
    $stmt = $conn->prepare("
        SELECT * FROM seating_versions WHERE id=?
    ");
    $stmt->bind_param("i", $version_id);
    $stmt->execute();
    $versionData = $stmt->get_result()->fetch_assoc();
}
if (isset($_POST['ajax'])) {

    $action = $_POST['action'] ?? '';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        jsonError('Security token expired. Refresh the page and try again.', 403);
    }

    /* ================= UPDATE ================= */
    if ($action == 'update') {

    header('Content-Type: application/json');

    $allocation_id = intval($_POST['id']);
    $seat_no       = intval($_POST['seat_no']);
    $student_id  = intval($_POST['student_id']);
    $room_id     = intval($_POST['room_id']);
    $subject_id  = intval($_POST['subject_id']);
    $day_hour_id = intval($_POST['day_hour_id']);

    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    $student_name    = trim($_POST['student_name']);
    $registration_no = trim($_POST['registration_no']);
    $batch           = trim($_POST['batch']);
    $class_name      = trim($_POST['class_name']);

    /* ================= STUDENT UPDATE ================= */
    $studentStmt = $conn->prepare("
        UPDATE students SET
            student_name=?,
            registration_no=?,
            batch=?,
            `class`=?
        WHERE id=?
    ");

    if (!$studentStmt) {
        echo json_encode(["status"=>"error","message"=>$conn->error]);
        exit();
    }

    $studentStmt->bind_param(
        "ssssi",
        $student_name,
        $registration_no,
        $batch,
        $class_name,
        $student_id
    );

    $studentStmt->execute();

    /* ================= SEAT UPDATE ================= */
    $stmt = $conn->prepare("
        UPDATE seat_allocation SET
            student_id=?,
            room_id=?,
            subject_id=?,
            day_hour_id=?,
            teacher_id=?,
            seat_no=?
        WHERE id=? AND version_id=?
    ");

    if (!$stmt) {
        echo json_encode(["status"=>"error","message"=>$conn->error]);
        exit();
    }

    $stmt->bind_param(
        "iiiiiiii",
        $student_id,
        $room_id,
        $subject_id,
        $day_hour_id,
        $teacher_id,
        $seat_no,
        $allocation_id,
        $version_id
    );

    if ($stmt->execute()) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode([
            "status"=>"error",
            "message"=>$stmt->error
        ]);
    }

    exit();
}

    /* ================= DELETE ================= */
    if ($action == 'delete') {

        $allocation_id = intval($_POST['id']);

        $stmt = $conn->prepare("
            DELETE FROM seat_allocation
            WHERE id=? AND version_id=?
        ");

        $stmt->bind_param("ii", $allocation_id, $version_id);

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


/* ================= FETCH DATA FOR EXPORT ================= */
$exportStmt = $conn->prepare("
    SELECT 
        sa.seat_no,
        s.student_name,
        s.registration_no,
        s.batch,
        s.class,
        sub.name AS subject_name,
        dh.day,
        dh.start_time,
        dh.end_time,
        r.room_name,
        b.name AS building_name,
        COALESCE(t.name, 'Not Assigned') AS teacher_name

    FROM seat_allocation sa

    INNER JOIN students s
        ON sa.student_id = s.id

    INNER JOIN rooms r
        ON sa.room_id = r.id

    LEFT JOIN buildings b
        ON r.building_id = b.id

    INNER JOIN subjects sub
        ON sa.subject_id = sub.id

    INNER JOIN days_hours dh
        ON sa.day_hour_id = dh.id

    LEFT JOIN teachers t
        ON sa.teacher_id = t.id

    WHERE sa.version_id = ?

    ORDER BY sa.room_id, sa.seat_no
");
$exportStmt->bind_param("i", $version_id);
$exportStmt->execute();
$exportQuery = $exportStmt->get_result();


$seatingExportColumns = [
    ['label' => 'Student Name', 'field' => 'student_name'],
    ['label' => 'Registration No', 'field' => 'registration_no'],
    ['label' => 'Batch', 'field' => 'batch'],
    ['label' => 'Class', 'field' => 'class'],
    ['label' => 'Subject', 'field' => 'subject_name'],
    ['label' => 'Day', 'field' => 'day'],
    ['label' => 'Time', 'value' => fn($row) => $row['start_time'] . ' - ' . $row['end_time']],
    ['label' => 'Building', 'field' => 'building_name'],
    ['label' => 'Room', 'field' => 'room_name'],
    ['label' => 'Invigilator', 'field' => 'teacher_name'],
    ['label' => 'Seat No', 'field' => 'seat_no'],
];

handleTableExport([
    'title' => 'Seating Plan Report',
    'filename' => 'seating_plan.xlsx',
    'columns' => $seatingExportColumns,
    'rows' => $exportQuery,
]);


/* ================= VIEW ================= */

include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="bg-white p-4 rounded shadow mb-6">

    <h2 class="text-lg font-bold text-emerald-700">Seating Versions</h2>

    <div class="flex gap-2 flex-wrap mt-2">

        <?php while($v = $versions->fetch_assoc()): ?>
            <a href="?version_id=<?= $v['id'] ?>"
               class="px-3 py-1 rounded text-white
               <?= ($version_id == $v['id']) ? 'bg-emerald-700' : 'bg-gray-500' ?>">
               <?= htmlspecialchars($v['version_name']) ?>
            </a>
        <?php endwhile; ?>

    </div>
</div>


<!-- ================= EDIT MODAL ================= -->
<div id="editModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">

    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-3xl my-10">

        <div class="flex justify-between items-center mb-5">

            <h2 class="text-2xl font-bold text-emerald-700">
                Edit Seat Allocation
            </h2>

            <button type="button"
                    id="closeModal"
                    class="text-red-600 text-3xl font-bold">
                ×
            </button>

        </div>

        <form id="editForm">

            <input type="hidden" id="allocation_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Student Name -->
                <div>
                    <label class="block mb-2 font-semibold">Student Name</label>
                    <input type="text" id="student_name"
                           class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <!-- Registration No -->
                <div>
                    <label class="block mb-2 font-semibold">Registration No</label>
                    <input type="text" id="registration_no"
                           class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <!-- Batch -->
                <div>
                    <label class="block mb-2 font-semibold">Batch</label>
                    <input type="text" id="batch"
                           class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <!-- Class -->
                <div>
                    <label class="block mb-2 font-semibold">Class</label>
                    <input type="text" id="class"
                           class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <!-- Seat -->
                <div>
                    <label class="block mb-2 font-semibold">Seat No</label>
                    <input type="number" id="seat_no" min="1"
                           class="border border-gray-300 w-full px-4 py-2 rounded-lg">
                </div>

                <!-- Student -->
                <div>
                    <label class="block mb-2 font-semibold">Student</label>

                    <select id="student_id"
                            class="border border-gray-300 w-full px-4 py-2 rounded-lg">

                        <?php
                        $students = mysqli_query($conn,"
                            SELECT id, student_name
                            FROM students
                            ORDER BY student_name ASC
                        ");

                        while($s = mysqli_fetch_assoc($students)){
                        ?>

                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['student_name']) ?>
                        </option>

                        <?php } ?>

                    </select>
                </div>

                <!-- Room -->
                <div>
                    <label class="block mb-2 font-semibold">Room</label>

                    <select id="room_id"
                            class="border border-gray-300 w-full px-4 py-2 rounded-lg">

                        <?php
                        $rooms = mysqli_query($conn,"
                            SELECT r.id, r.room_name, b.name AS building_name
                            FROM rooms r
                            LEFT JOIN buildings b ON r.building_id = b.id
                            ORDER BY b.name ASC, r.room_name ASC
                        ");

                        while($r = mysqli_fetch_assoc($rooms)){
                        ?>

                        <option value="<?= $r['id'] ?>">
                            <?= htmlspecialchars(trim(($r['building_name'] ? $r['building_name'] . ' - ' : '') . $r['room_name'])) ?>
                        </option>

                        <?php } ?>

                    </select>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block mb-2 font-semibold">Subject</label>

                    <select id="subject_id"
                            class="border border-gray-300 w-full px-4 py-2 rounded-lg">

                        <?php
                        $subjects = mysqli_query($conn,"
                            SELECT id, name
                            FROM subjects
                            ORDER BY name ASC
                        ");

                        while($sub = mysqli_fetch_assoc($subjects)){
                        ?>

                        <option value="<?= $sub['id'] ?>">
                            <?= htmlspecialchars($sub['name']) ?>
                        </option>

                        <?php } ?>

                    </select>
                </div>

                <!-- Day -->
                <div>
                    <label class="block mb-2 font-semibold">Day & Time</label>

                    <select id="day_hour_id"
                            class="border border-gray-300 w-full px-4 py-2 rounded-lg">

                        <?php
                        $days = mysqli_query($conn,"
                            SELECT *
                            FROM days_hours
                            ORDER BY day ASC
                        ");

                        while($d = mysqli_fetch_assoc($days)){
                        ?>

                        <option value="<?= $d['id'] ?>">
                            <?= $d['day'] ?>
                            -
                            <?= $d['start_time'] ?>
                            to
                            <?= $d['end_time'] ?>
                        </option>

                        <?php } ?>

                    </select>
                </div>

                <!-- Teacher -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold">
                        Invigilator
                    </label>

                    <select id="teacher_id"
                            class="border border-gray-300 w-full px-4 py-2 rounded-lg">

                        <option value="">Select Teacher</option>

                        <?php
                        $teachers = mysqli_query($conn,"
                            SELECT id, name
                            FROM teachers
                            ORDER BY name ASC
                        ");

                        while($t = mysqli_fetch_assoc($teachers)){
                        ?>

                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['name']) ?>
                        </option>

                        <?php } ?>

                    </select>

                </div>

            </div>

            <div class="flex gap-3 mt-6">

                <button type="submit"
                        class="bg-emerald-700 hover:bg-emerald-800 text-white w-full py-3 rounded-lg font-semibold">

                    Update Record

                </button>

                <button type="button"
                        id="cancelBtn"
                        class="bg-gray-500 hover:bg-gray-600 text-white w-full py-3 rounded-lg font-semibold">

                    Cancel

                </button>

            </div>

        </form>

    </div>

</div>



<!-- EXPORT -->
<div class="flex gap-3 mb-4">
<a href="?view=1&export=excel&version_id=<?= $version_id ?>" class="bg-green-600 text-white px-5 py-2 rounded-lg">⬇ Excel</a>
<a href="?view=1&export=pdf&version_id=<?= $version_id ?>" class="bg-red-600 text-white px-5 py-2 rounded-lg">⬇ PDF</a>
<a href="?view=1&export=print&version_id=<?= $version_id ?>" class="bg-blue-600 text-white px-5 py-2 rounded-lg">🖨 Print</a>
</div>




<!-- ================= TABLE ================= -->
<div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-300">

    <div class="overflow-x-auto p-4">

        <table id="seatTable"
               class="min-w-full text-sm border border-gray-300">

            <thead class="bg-emerald-600 text-white">

                <tr>

                    <th class="border px-4 py-3">Student Name</th>
                    <th class="border px-4 py-3">Reg No</th>
                    <th class="border px-4 py-3">Batch</th>
                    <th class="border px-4 py-3">Class</th>
                    <th class="border px-4 py-3">Subject</th>
                    <th class="border px-4 py-3">Day</th>
                    <th class="border px-4 py-3">Time</th>
                    <th class="border px-4 py-3">Building</th>
                    <th class="border px-4 py-3">Room</th>
                    <th class="border px-4 py-3">Invigilator</th>
                    <th class="border px-4 py-3">Seat No</th>
                    <th class="border px-4 py-3">Action</th>

                </tr>

            </thead>

            <tbody>

            <?php
            $stmt = $conn->prepare("
            SELECT sa.*, 
                   s.student_name, 
                   s.registration_no, 
                   s.batch, 
                   s.class,

                   sub.name AS subject_name,

                   dh.day, 
                   dh.start_time, 
                   dh.end_time,

                   r.room_name,
                   b.name AS building_name,

                   COALESCE(t.name,'Not Assigned') AS teacher_name

            FROM seat_allocation sa

            INNER JOIN students s
                ON sa.student_id = s.id

            INNER JOIN rooms r
                ON sa.room_id = r.id

            LEFT JOIN buildings b
                ON r.building_id = b.id

            INNER JOIN subjects sub
                ON sa.subject_id = sub.id

            INNER JOIN days_hours dh
                ON sa.day_hour_id = dh.id

            LEFT JOIN teachers t
                ON sa.teacher_id = t.id

            WHERE sa.version_id=?

            ORDER BY sa.room_id, sa.seat_no
            ");
            $stmt->bind_param("i", $version_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()) {
            ?>

            <tr id="row<?= $row['id'] ?>">

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['student_name']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['registration_no']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['batch']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['class']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['subject_name']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['day']) ?>
                </td>

                <td class="border px-4 py-3">

                    <?= htmlspecialchars($row['start_time']) ?>
                    -
                    <?= htmlspecialchars($row['end_time']) ?>

                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['building_name'] ?? 'Not Assigned') ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['room_name']) ?>
                </td>

                <td class="border px-4 py-3">
                    <?= htmlspecialchars($row['teacher_name']) ?>
                </td>

                <td class="border px-4 py-3 font-bold text-emerald-700">
                    <?= $row['seat_no'] ?>
                </td>

                <td class="border px-4 py-3 flex gap-2">

                    <button
                        class="bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700 editBtn"

                        data-id="<?= $row['id'] ?>"
                        data-seat="<?= $row['seat_no'] ?>"
                        data-studentid="<?= $row['student_id'] ?>"
                        data-studentname="<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>"
                        data-reg="<?= htmlspecialchars($row['registration_no'], ENT_QUOTES) ?>"
                        data-batch="<?= htmlspecialchars($row['batch'], ENT_QUOTES) ?>"
                        data-class="<?= htmlspecialchars($row['class'], ENT_QUOTES) ?>"
                        data-roomid="<?= $row['room_id'] ?>"
                        data-subjectid="<?= $row['subject_id'] ?>"
                        data-dayhourid="<?= $row['day_hour_id'] ?>"
                        data-teacherid="<?= $row['teacher_id'] ?>">

                        Edit

                    </button>

                    <button
                        class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 deleteBtn"

                        data-id="<?= $row['id'] ?>"
                        data-seat="<?= $row['seat_no'] ?>">

                        Delete

                    </button>

                </td>

            </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
let table;
$(document).ready(function () {
   const csrfToken = '<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>';

   table = $('#seatTable').DataTable({
    pageLength: 10,
    responsive: true,
    autoWidth: false,
    stateSave: true
});

    /* ================= OPEN EDIT MODAL ================= */
    $(document).on('click', '.editBtn', function () {

        $('#allocation_id').val($(this).data('id'));
        $('#seat_no').val($(this).data('seat'));

        $('#student_name').val($(this).data('studentname'));

        $('#registration_no').val($(this).data('reg'));

        $('#batch').val($(this).data('batch'));

        $('#class').val($(this).data('class'));

        $('#student_id').val($(this).data('studentid'));

        $('#room_id').val($(this).data('roomid'));

        $('#subject_id').val($(this).data('subjectid'));

        $('#day_hour_id').val($(this).data('dayhourid'));

        let teacher_id = $(this).data('teacherid');

        if (teacher_id == null || teacher_id == '') {

            $('#teacher_id').val('');

        } else {

            $('#teacher_id').val(teacher_id);
        }

        $('#editModal')
            .removeClass('hidden')
            .addClass('flex');
    });

    /* ================= CLOSE MODAL ================= */
    $('#closeModal, #cancelBtn').click(function(){

        $('#editModal')
            .removeClass('flex')
            .addClass('hidden');
    });

    /* ================= UPDATE ================= */
    $('#editForm').submit(function (e) {

        e.preventDefault();

        $.ajax({

            url: '',

            type: 'POST',

            dataType: 'json',

            data: {

                ajax: 1,
                csrf_token: csrfToken,
                action: 'update',

                id: $('#allocation_id').val(),

                seat_no: $('#seat_no').val(),

                student_name: $('#student_name').val(),

                registration_no: $('#registration_no').val(),

                batch: $('#batch').val(),
                 class_name: $('#class').val(),
                student_id: $('#student_id').val(),

                room_id: $('#room_id').val(),

                subject_id: $('#subject_id').val(),

                day_hour_id: $('#day_hour_id').val(),

                teacher_id: $('#teacher_id').val()
            },

        success: function (response) {

    if (response.status == 'success') {

        table.state.save();

        location.reload();

    } else {

        alert(response.message);
    }
},

            error: function (xhr) {

                console.log(xhr.responseText);

                alert('Something went wrong');
            }
        });
    });

    /* ================= DELETE ================= */
    $(document).on('click', '.deleteBtn', function () {

        if (!confirm('Delete this record?')) return;

        let id = $(this).data('id');

        $.ajax({

            url: '',

            type: 'POST',

            dataType: 'json',

            data: {

                ajax: 1,
                csrf_token: csrfToken,
                action: 'delete',
                id: id
            },

          success: function (response) {

    if (response.status == 'success') {

        table.state.save();

        location.reload();

    } else {

        alert(response.message);
    }
}
        });
    });

});

</script>


<?php include 'includes/footer.php'; ?>
