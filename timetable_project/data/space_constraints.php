<?php
/**
 * Admin CRUD page for space constraints.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/security.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$config = [
    'table' => 'space_constraints',
    'table_id' => 'constraintsTable',
    'add_title' => 'Add Space Constraint',
    'edit_title' => 'Edit Constraint',
    'list_title' => 'Space Constraints',
    'delete_confirm' => 'Delete this record?',
    'require_csrf' => true,
    'fields' => [
        ['name' => 'room', 'label' => 'Room', 'bind' => 's', 'required' => true, 'cell_class' => 'room'],
        ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'capacity'],
        ['name' => 'room_type', 'label' => 'Room Type', 'table_label' => 'Type', 'bind' => 's', 'required' => true, 'cell_class' => 'type'],
        ['name' => 'note', 'label' => 'Note', 'type' => 'textarea', 'bind' => 's', 'form_class' => 'md:col-span-2', 'cell_class' => 'note']
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingSpaceConstraintId($conn, $data['room'], $data['room_type']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM space_constraints ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'space_constraints');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
