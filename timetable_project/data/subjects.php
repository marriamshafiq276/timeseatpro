<?php
/**
 * Admin CRUD page for subjects.
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
    'table' => 'subjects',
    'table_id' => 'subjectsTable',
    'add_title' => 'Add Subject',
    'edit_title' => 'Edit Subject',
    'list_title' => 'Existing Subjects',
    'delete_confirm' => 'Delete subject?',
    'has_status' => true,
    'fields' => [
        ['name' => 'code', 'label' => 'Code', 'bind' => 's', 'required' => true, 'cell_class' => 'code'],
        ['name' => 'name', 'label' => 'Name', 'bind' => 's', 'required' => true, 'cell_class' => 'name'],
        ['name' => 'credit_hours', 'label' => 'Credit Hours', 'table_label' => 'Credit', 'bind' => 's', 'required' => true, 'cell_class' => 'credit'],
        ['name' => 'department', 'label' => 'Department', 'bind' => 's', 'required' => true, 'cell_class' => 'dept'],
        [
            'name' => 'level',
            'label' => 'Level',
            'type' => 'select',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'level',
            'options' => [
                '' => 'Select',
                'Undergraduate' => 'Undergraduate',
                'Post Graduate' => 'Post Graduate'
            ]
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingSubjectId($conn, $data['code']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM subjects ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'subjects');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
