<?php
/**
 * Admin CRUD page for institution information.
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/auth_check.php';

requireRole('admin');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/crud_page.php';

$config = [
    'table' => 'institution',
    'table_id' => 'institutionTable',
    'add_title' => 'Add Institution',
    'edit_title' => 'Edit Institution',
    'list_title' => 'Institutions List',
    'delete_confirm' => 'Delete institution?',
    'has_status' => true,
    'fields' => [
        ['name' => 'institute_name', 'label' => 'Name', 'bind' => 's', 'required' => true, 'form_class' => 'md:col-span-2', 'cell_class' => 'name'],
        ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 3, 'bind' => 's', 'form_class' => 'md:col-span-2', 'table_label' => 'Address', 'cell_class' => 'address', 'hide_in_table' => true],
        ['name' => 'phone', 'label' => 'Phone', 'bind' => 's', 'cell_class' => 'phone'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'bind' => 's', 'cell_class' => 'email'],
        ['name' => 'website', 'label' => 'Website', 'bind' => 's', 'form_class' => 'md:col-span-2', 'cell_class' => 'website']
    ]
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM institution ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'institution');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
