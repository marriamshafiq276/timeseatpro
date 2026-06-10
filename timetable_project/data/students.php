<?php
/**
 * Admin CRUD page for students.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$config = [
    'table' => 'students',
    'table_id' => 'studentsTable',
    'add_title' => 'Add Student',
    'edit_title' => 'Edit Student',
    'list_title' => 'Students',
    'delete_confirm' => 'Delete this student?',
    'fields' => [
        ['name' => 'student_name', 'label' => 'Student Name', 'table_label' => 'Name', 'bind' => 's', 'required' => true, 'cell_class' => 'name'],
        ['name' => 'registration_no', 'label' => 'Registration No', 'table_label' => 'Reg', 'bind' => 's', 'required' => true, 'cell_class' => 'reg'],
        ['name' => 'batch', 'label' => 'Batch', 'bind' => 's', 'required' => true, 'cell_class' => 'batch'],
        ['name' => 'class', 'label' => 'Class', 'bind' => 's', 'required' => true, 'cell_class' => 'class'],
        ['name' => 'total_students', 'label' => 'Total Students', 'table_label' => 'Total', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'total'],
        ['name' => 'groups', 'label' => 'Groups', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'groups'],
        ['name' => 'students_per_group', 'label' => 'Students Per Group', 'table_label' => 'Per', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'per']
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingStudentId($conn, $data['registration_no'], $data['student_name'], $data['batch'], $data['class']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM students ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'students');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
