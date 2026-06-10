<?php
/**
 * Admin CRUD page for activities.
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
    'table' => 'activities',
    'table_id' => 'activitiesTable',
    'add_title' => 'Add Activity',
    'edit_title' => 'Edit Activity',
    'list_title' => 'Activities',
    'delete_confirm' => 'Delete activity?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'name',
            'label' => 'Activity Name',
            'table_label' => 'Name',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'name'
        ],
        [
            'name' => 'description',
            'label' => 'Description',
            'bind' => 's',
            'form_class' => 'md:col-span-2',
            'cell_class' => 'desc'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingActivityId($conn, $data['name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM activities ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'activities');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
