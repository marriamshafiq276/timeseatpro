<?php
/**
 * Admin CRUD page for faculties.
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
    'table' => 'faculties',
    'table_id' => 'facultiesTable',
    'add_title' => 'Add Faculty',
    'edit_title' => 'Edit Faculty',
    'list_title' => 'Faculties List',
    'delete_confirm' => 'Delete faculty?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'name',
            'label' => 'Faculty Name',
            'table_label' => 'Name',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'name'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingFacultyId($conn, $data['name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM faculties ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'faculties');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
