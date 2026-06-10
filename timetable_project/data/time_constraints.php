<?php
/**
 * Admin CRUD page for time constraints.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$dayOptions = [
    '' => 'Select Day',
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday'
];

$config = [
    'table' => 'time_constraints',
    'table_id' => 'constraintsTable',
    'add_title' => 'Add Time Constraint',
    'edit_title' => 'Edit Time Constraint',
    'list_title' => 'Time Constraints',
    'delete_confirm' => 'Delete record?',
    'fields' => [
        ['name' => 'room', 'label' => 'Room', 'bind' => 's', 'required' => true, 'cell_class' => 'room'],
        ['name' => 'day', 'label' => 'Day', 'type' => 'select', 'bind' => 's', 'required' => true, 'cell_class' => 'day', 'options' => $dayOptions],
        ['name' => 'period', 'label' => 'Period', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'period'],
        ['name' => 'note', 'label' => 'Note', 'type' => 'textarea', 'rows' => 3, 'bind' => 's', 'form_class' => 'md:col-span-2', 'cell_class' => 'note'],
        [
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'status',
            'options' => [
                'Active' => 'Active',
                'Inactive' => 'Inactive'
            ]
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingTimeConstraintId($conn, $data['room'], $data['day'], $data['period']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM time_constraints ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'time_constraints');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
