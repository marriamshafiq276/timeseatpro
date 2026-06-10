<?php
/**
 * Admin CRUD page for buildings.
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
    'table' => 'buildings',
    'table_id' => 'buildingsTable',
    'add_title' => 'Add Building',
    'edit_title' => 'Edit Building',
    'list_title' => 'Existing Buildings',
    'delete_confirm' => 'Delete this building?',
    'fields' => [
        [
            'name' => 'name',
            'label' => 'Building Name',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'name'
        ],
        [
            'name' => 'location',
            'label' => 'Location',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'location'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingBuildingId($conn, $data['name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM buildings ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'buildings');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
