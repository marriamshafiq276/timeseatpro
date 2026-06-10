<?php
/**
 * Admin CRUD page for classes.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$facultyRows = crudFetchRows($conn, "SELECT id, name FROM faculties WHERE status='Active' ORDER BY name ASC");
$facultyOptions = ['' => 'Select Faculty'];
foreach ($facultyRows as $faculty) {
    $facultyOptions[$faculty['id']] = $faculty['name'];
}

$config = [
    'table' => 'classes',
    'table_id' => 'classesTable',
    'add_title' => 'Add Class',
    'edit_title' => 'Edit Class',
    'list_title' => 'Classes',
    'delete_confirm' => 'Delete this class?',
    'has_status' => true,
    'form_grid' => 'grid grid-cols-1 md:grid-cols-3 gap-4 items-end',
    'button_class' => 'md:col-span-3',
    'fields' => [
        [
            'name' => 'faculty_id',
            'label' => 'Faculty',
            'type' => 'select',
            'bind' => 'i',
            'required' => true,
            'display' => 'faculty_name',
            'display_from_select' => true,
            'cell_class' => 'faculty_name',
            'options' => $facultyOptions
        ],
        ['name' => 'class_name', 'label' => 'Class Name', 'bind' => 's', 'required' => true, 'cell_class' => 'class_name'],
        ['name' => 'semester', 'label' => 'Semester', 'bind' => 's', 'required' => true, 'cell_class' => 'semester'],
        ['name' => 'section', 'label' => 'Section', 'bind' => 's', 'required' => true, 'cell_class' => 'section']
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingClassId($conn, $data['class_name'], $data['semester'], $data['section']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, '
    SELECT c.*, f.name AS faculty_name
    FROM classes c
    LEFT JOIN faculties f ON c.faculty_id = f.id
    ORDER BY c.id DESC
');
$config['next_id'] = crudNextId($conn, 'classes');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
