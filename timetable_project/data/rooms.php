<?php
/**
 * Admin CRUD page for rooms.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/duplicate_helpers.php';
include __DIR__ . '/../includes/crud_page.php';
include __DIR__ . '/../includes/schema_helpers.php';

ensureBuildingRoomSupport($conn);

$buildingRows = crudFetchRows($conn, "SELECT id, name FROM buildings WHERE status='Active' ORDER BY name ASC");
$buildingOptions = ['' => 'Select Building'];
foreach ($buildingRows as $building) {
    $buildingOptions[$building['id']] = $building['name'];
}

$config = [
    'table' => 'rooms',
    'table_id' => 'roomsTable',
    'add_title' => 'Add Room',
    'edit_title' => 'Edit Room',
    'list_title' => 'Rooms List',
    'delete_confirm' => 'Delete room?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'building_id',
            'label' => 'Building',
            'type' => 'select',
            'bind' => 'i',
            'display' => 'building_name',
            'display_from_select' => true,
            'cell_class' => 'building',
            'options' => $buildingOptions
        ],
        ['name' => 'room_name', 'label' => 'Room Name', 'bind' => 's', 'required' => true, 'cell_class' => 'name'],
        ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number', 'bind' => 'i', 'required' => true, 'cell_class' => 'capacity'],
        [
            'name' => 'room_type',
            'label' => 'Room Type',
            'table_label' => 'Type',
            'type' => 'select',
            'bind' => 's',
            'cell_class' => 'type',
            'options' => [
                '' => 'Select Type',
                'Classroom' => 'Classroom',
                'Lab' => 'Lab',
                'Seminar Hall' => 'Seminar Hall'
            ]
        ],
        [
            'name' => 'floor',
            'label' => 'Floor',
            'type' => 'select',
            'bind' => 's',
            'cell_class' => 'floor',
            'options' => [
                '' => 'Select Floor',
                'Ground Floor' => 'Ground Floor',
                '1st Floor' => '1st Floor',
                '2nd Floor' => '2nd Floor'
            ]
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingRoomId($conn, $data['room_name'], $data['floor'], $data['building_id'] ?? null);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, '
    SELECT r.*, b.name AS building_name
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id = b.id
    ORDER BY r.id DESC
');
$config['next_id'] = crudNextId($conn, 'rooms');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
