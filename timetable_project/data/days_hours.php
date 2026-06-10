<?php
/**
 * Admin CRUD page for day/hour slots.
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
    'Saturday' => 'Saturday',
    'Sunday' => 'Sunday'
];

$config = [
    'table' => 'days_hours',
    'table_id' => 'daysTable',
    'add_title' => 'Add Days & Time Slots',
    'edit_title' => 'Edit Day & Time Slot',
    'list_title' => 'Existing Days & Hours',
    'delete_confirm' => 'Delete this record?',
    'has_status' => true,
    'fields' => [
        ['name' => 'day', 'label' => 'Day', 'type' => 'select', 'bind' => 's', 'required' => true, 'cell_class' => 'day', 'options' => $dayOptions],
        ['name' => 'start_time', 'label' => 'Start Time', 'table_label' => 'Start', 'type' => 'time', 'bind' => 's', 'required' => true, 'cell_class' => 'start'],
        ['name' => 'end_time', 'label' => 'End Time', 'table_label' => 'End', 'type' => 'time', 'bind' => 's', 'required' => true, 'cell_class' => 'end'],
        [
            'name' => 'class_type',
            'label' => 'Class Type',
            'table_label' => 'Type',
            'type' => 'select',
            'bind' => 's',
            'cell_class' => 'class_type',
            'options' => [
                'Theory' => 'Theory',
                'Practical' => 'Practical'
            ]
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingDaysHoursId($conn, $data['day'], $data['start_time'], $data['end_time'], $data['class_type']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, "
    SELECT
        id,
        day,
        TIME_FORMAT(start_time, '%H:%i') AS start_time,
        TIME_FORMAT(end_time, '%H:%i') AS end_time,
        class_type
    FROM days_hours
    ORDER BY id DESC
");
$config['next_id'] = crudNextId($conn, 'days_hours');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
