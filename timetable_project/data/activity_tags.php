<?php
/**
 * Admin CRUD page for activity tags.
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
    'table' => 'activity_tags',
    'table_id' => 'tagsTable',
    'add_title' => 'Add Activity Tag',
    'edit_title' => 'Edit Activity Tag',
    'list_title' => 'Activity Tags',
    'delete_confirm' => 'Delete tag?',
    'has_status' => true,
    'fields' => [
        [
            'name' => 'tag_name',
            'label' => 'Tag Name',
            'bind' => 's',
            'required' => true,
            'cell_class' => 'name'
        ],
        [
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'bind' => 's',
            'form_class' => 'md:col-span-2',
            'cell_class' => 'desc'
        ]
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingActivityTagId($conn, $data['tag_name']);
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM activity_tags ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'activity_tags');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
