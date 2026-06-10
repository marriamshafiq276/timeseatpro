<?php
/**
 * Admin subactivity editor.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';

$activityRows = crudFetchRows($conn, 'SELECT id, name FROM activities ORDER BY name ASC');
$activityOptions = ['' => 'Select Activity'];
foreach ($activityRows as $activity) {
    $activityOptions[$activity['id']] = $activity['name'];
}

$config = [
    'table' => 'subactivities',
    'table_id' => 'subTable',
    'add_title' => 'Add Sub-Activity',
    'edit_title' => 'Edit Sub-Activity',
    'list_title' => 'Sub-Activities List',
    'delete_confirm' => 'Delete this sub-activity?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'activity_id',
            'label' => 'Activity',
            'type' => 'select',
            'bind' => 'i',
            'required' => true,
            'display' => 'activity_name',
            'display_from_select' => true,
            'cell_class' => 'activity',
            'options' => $activityOptions
        ],
        [
            'name' => 'name',
            'label' => 'Sub-Activity Name',
            'table_label' => 'Sub Activity',
            'bind' => 's',
            'required' => true,
            'form_class' => 'md:col-span-2',
            'cell_class' => 'name'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingSubactivityId($conn, $data['activity_id'], $data['name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, '
    SELECT s.*, a.name AS activity_name
    FROM subactivities s
    LEFT JOIN activities a ON s.activity_id = a.id
    ORDER BY s.id DESC
');
$config['next_id'] = crudNextId($conn, 'subactivities');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
