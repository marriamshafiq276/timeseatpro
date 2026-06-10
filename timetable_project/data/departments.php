<?php
/**
 * Admin CRUD page for departments.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$facultyRows = crudFetchRows($conn, 'SELECT id, name FROM faculties ORDER BY name ASC');
$facultyOptions = ['' => 'Select Faculty'];
foreach ($facultyRows as $faculty) {
    $facultyOptions[$faculty['id']] = $faculty['name'];
}

$config = [
    'table' => 'departments',
    'table_id' => 'deptTable',
    'add_title' => 'Add Department',
    'edit_title' => 'Edit Department',
    'list_title' => 'Departments List',
    'delete_confirm' => 'Delete department?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'faculty_id',
            'label' => 'Faculty',
            'type' => 'select',
            'bind' => 'i',
            'required' => true,
            'display' => 'faculty_name',
            'display_from_select' => true,
            'cell_class' => 'faculty',
            'options' => $facultyOptions
        ],
        [
            'name' => 'name',
            'label' => 'Department Name',
            'table_label' => 'Department',
            'bind' => 's',
            'required' => true,
            'form_class' => 'md:col-span-2',
            'cell_class' => 'name'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingDepartmentId($conn, $data['faculty_id'], $data['name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, '
    SELECT
        d.id,
        d.faculty_id,
        d.name,
        f.name AS faculty_name
    FROM departments d
    JOIN faculties f ON d.faculty_id = f.id
    ORDER BY d.id DESC
');
$config['next_id'] = crudNextId($conn, 'departments');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
