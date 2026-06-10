<?php
$base = "/timetable_project/";
$active = basename($_SERVER['PHP_SELF']);

$dataItems = [
    'data/institution.php' => 'Institution Information',
    'data/faculties.php' => 'Faculties',
    'data/departments.php' => 'Departments',
    'data/days_hours.php' => 'Days and Hours',
    'data/subjects.php' => 'Subjects',
    'data/classes.php' => 'Classes',
    'data/activity_tags.php' => 'Activity Tags',
    'data/teachers.php' => 'Teachers',
    'data/students.php' => 'Students',
    'data/activities.php' => 'Activities',
    'data/modify_subactivities.php' => 'Modify Subactivities',
    'data/buildings.php' => 'Buildings',
    'data/rooms.php' => 'Rooms',
    'data/time_constraints.php' => 'All Time Constraints',
    'data/space_constraints.php' => 'Space Constraints'
];

$timetableItems = [
    'generate_timetable.php' => 'Generate Timetable',
    'view_timetable.php' => 'View Timetable',
    'timetable_display.php' => 'Display Timetable',
    'timetable_history.php' => 'Timetable History'
];

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$homeUrl = roleHomeUrl();
?>

<nav class="bg-emerald-700 text-white shadow-md relative z-50">

<div class="max-w-7xl mx-auto px-6">

<!-- TOP BAR -->
<div class="flex items-center justify-between h-14">

<!-- MOBILE BUTTON -->
<button id="mobileMenuBtn"
class="lg:hidden text-2xl px-2 py-1 hover:bg-emerald-600 rounded">
☰
</button>

<!-- DESKTOP MENU -->
<div id="desktopMenu"
class="hidden lg:flex items-center space-x-6 flex-1 overflow-visible">

<!-- DASHBOARD -->
<a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>"
class="font-semibold uppercase text-sm hover:text-emerald-200
<?= in_array($active, ['dashboard.php','teacher_timetable.php','student_timetable.php'], true) ? 'text-emerald-200' : '' ?>">
Dashboard
</a>

<!-- DATA -->
<?php if ($isAdmin): ?>
<div class="relative">

<button onclick="toggleMenu('dataMenu')"
class="flex items-center gap-1 font-semibold uppercase text-sm px-3 py-2 hover:bg-emerald-600 rounded">
Data ▼
</button>

<div id="dataMenu"
class="hidden absolute left-0 mt-2 w-80 bg-gray-100 text-emerald-900 border rounded shadow-xl z-50 max-h-96 overflow-y-auto">

<?php foreach ($dataItems as $file => $label): ?>
<a href="<?= $base . $file ?>"
class="block px-5 py-3 text-sm border-b hover:bg-gray-300">
<?= strtoupper($label) ?>
</a>
<?php endforeach; ?>

</div>
</div>
<?php endif; ?>

<!-- TIMETABLE -->
<?php if ($isAdmin): ?>
<div class="relative">

<button onclick="toggleMenu('timetableMenu')"
class="flex items-center gap-1 font-semibold uppercase text-sm px-3 py-2 hover:bg-emerald-600 rounded">
Timetable ▼
</button>

<div id="timetableMenu"
class="hidden absolute left-0 mt-2 w-80 bg-gray-100 text-emerald-900 border rounded shadow-xl z-50 max-h-96 overflow-y-auto">

<?php foreach ($timetableItems as $file => $label): ?>
<a href="<?= $base . $file ?>"
class="block px-5 py-3 text-sm border-b hover:bg-gray-300">
<?= strtoupper($label) ?>
</a>
<?php endforeach; ?>

</div>
</div>
<?php endif; ?>

<!-- SEAT -->
<?php if ($isAdmin): ?>
<div class="relative">

<button onclick="toggleMenu('seatMenu')"
class="flex items-center gap-1 font-semibold uppercase text-sm px-3 py-2 hover:bg-emerald-600 rounded">
Seat ▼
</button>

<div id="seatMenu"
class="hidden absolute left-0 mt-2 w-72 bg-gray-100 text-emerald-900 border rounded shadow-xl z-50">

<a href="<?= $base ?>generate_seating.php"
class="block px-5 py-3 text-sm hover:bg-gray-300">
Generate Seating
</a>

<a href="<?= $base ?>view_seating_plan.php?view=1"
class="block px-5 py-3 text-sm border-t hover:bg-gray-300">
View Seating
</a>

</div>
</div>
<?php endif; ?>

<!-- OTHER -->
<a href="<?= $base ?>guide.php"
class="font-semibold uppercase text-sm hover:text-emerald-200">
Guide
</a>

<?php if ($isAdmin): ?>
<a href="<?= $base ?>data/contacts.php"
class="font-semibold uppercase text-sm hover:text-emerald-200">
Contacts
</a>

<a href="<?= $base ?>user_management.php"
class="font-semibold uppercase text-sm hover:text-emerald-200">
Users
</a>
<?php endif; ?>

<!-- LOGOUT -->
<a href="<?= $base ?>logout.php"
class="ml-auto bg-emerald-900 hover:bg-emerald-800 px-4 py-2 rounded text-sm font-semibold uppercase">
Logout
</a>

</div>
</div>

<!-- MOBILE MENU -->
<div id="mobileMenu"
class="hidden lg:hidden bg-emerald-800 border-t border-emerald-600">

<div class="flex flex-col p-3 space-y-2">

<a href="<?= $homeUrl ?>" class="px-3 py-2 hover:bg-emerald-600 rounded">
Dashboard
</a>

<?php if ($isAdmin): ?>

<button onclick="toggleMenu('mData')"
class="text-left px-3 py-2 hover:bg-emerald-600 rounded">
Data ▼
</button>

<div id="mData" class="hidden pl-4 space-y-1">
<?php foreach ($dataItems as $file => $label): ?>
<a href="<?= $base . $file ?>" class="block py-1 hover:text-emerald-200">
<?= $label ?>
</a>
<?php endforeach; ?>
</div>

<button onclick="toggleMenu('mTime')"
class="text-left px-3 py-2 hover:bg-emerald-600 rounded">
Timetable ▼
</button>

<div id="mTime" class="hidden pl-4 space-y-1">
<?php foreach ($timetableItems as $file => $label): ?>
<a href="<?= $base . $file ?>" class="block py-1 hover:text-emerald-200">
<?= $label ?>
</a>
<?php endforeach; ?>
</div>

<button onclick="toggleMenu('mSeat')"
class="text-left px-3 py-2 hover:bg-emerald-600 rounded">
Seat ▼
</button>

<div id="mSeat" class="hidden pl-4 space-y-1">
<a href="<?= $base ?>generate_seating.php">Generate Seating</a>
<a href="<?= $base ?>view_seating_plan.php?view=1">View Seating</a>
</div>

<a href="<?= $base ?>guide.php" class="px-3 py-2 hover:bg-emerald-600 rounded">Guide</a>
<a href="<?= $base ?>data/contacts.php" class="px-3 py-2 hover:bg-emerald-600 rounded">Contacts</a>
<a href="<?= $base ?>user_management.php" class="px-3 py-2 hover:bg-emerald-600 rounded">Users</a>

<?php endif; ?>

<a href="<?= $base ?>logout.php"
class="px-3 py-2 bg-emerald-900 text-center rounded">
Logout
</a>

</div>
</div>

</nav>

<!-- SCRIPT -->
<script>
function toggleMenu(id){

const el = document.getElementById(id);
if(!el) return;

el.classList.toggle('hidden');
}

document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
document.getElementById('mobileMenu').classList.toggle('hidden');
});

document.addEventListener('click', function(e){
if(!e.target.closest('.relative')){
document.querySelectorAll('[id$="Menu"]').forEach(m => {
if(!m.id.startsWith('m')) m.classList.add('hidden');
});
}
});
</script>