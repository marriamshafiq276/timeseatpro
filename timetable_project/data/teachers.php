<?php
/**
 * Admin CRUD page for teachers.
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
    'table' => 'teachers',
    'table_id' => 'teachersTable',
    'add_title' => 'Add Teacher',
    'edit_title' => 'Edit Teacher',
    'list_title' => 'Existing Teachers',
    'delete_confirm' => 'Delete teacher?',
    'has_status' => true,
    'table_class' => 'w-full min-w-[1500px] border text-center table-auto text-sm',
    'fields' => [
        ['name' => 'name', 'label' => 'Name', 'bind' => 's', 'required' => true, 'cell_class' => 'name'],
        ['name' => 'father_name', 'label' => 'Father Name', 'bind' => 's', 'cell_class' => 'father'],
        ['name' => 'cnic', 'label' => 'CNIC', 'bind' => 's', 'cell_class' => 'cnic'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'bind' => 's', 'cell_class' => 'email'],
        ['name' => 'phone', 'label' => 'Phone', 'bind' => 's', 'cell_class' => 'phone'],
        ['name' => 'qualification', 'label' => 'Qualification', 'bind' => 's', 'cell_class' => 'qualification'],
        [
            'name' => 'visiting_status',
            'label' => 'Employment Type',
            'table_label' => 'Employment',
            'type' => 'select',
            'bind' => 's',
            'cell_class' => 'visiting',
            'options' => [
                'Permanent' => 'Permanent',
                'Visiting' => 'Visiting'
            ]
        ],
        ['name' => 'designation', 'label' => 'Designation', 'bind' => 's', 'cell_class' => 'designation'],
        ['name' => 'major', 'label' => 'Major', 'bind' => 's', 'cell_class' => 'major'],
        ['name' => 'minor', 'label' => 'Minor', 'bind' => 's', 'cell_class' => 'minor'],
        ['name' => 'department', 'label' => 'Department', 'bind' => 's', 'cell_class' => 'department']
    ],
    'duplicate' => function (mysqli $conn, array $data) {
        return getExistingTeacherId(
            $conn,
            $data['cnic'],
            $data['email'],
            $data['name'],
            $data['father_name'],
            $data['department']
        );
    }
];

crudHandleAjax($conn, $config);

$config['rows'] = crudFetchRows($conn, 'SELECT * FROM teachers ORDER BY id DESC');
$config['next_id'] = crudNextId($conn, 'teachers');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/nav.php';
?>

<style>
#teachersTable {
    table-layout: auto;
}

#teachersTable th,
#teachersTable td {
    box-sizing: border-box;
    line-height: 1.35;
    max-width: 180px;
    overflow-wrap: anywhere;
    padding: 0.75rem 0.625rem;
    vertical-align: middle;
    white-space: normal;
    word-break: normal;
}

#teachersTable th {
    font-size: 0.875rem;
    line-height: 1.25rem;
    min-width: 115px;
    white-space: nowrap;
}

#teachersTable th:first-child,
#teachersTable td:first-child {
    max-width: 70px;
    min-width: 70px;
    width: 70px;
}

#teachersTable th:last-child,
#teachersTable td:last-child {
    max-width: 170px;
    min-width: 170px;
    white-space: nowrap;
}

#teachersTable .editBtn,
#teachersTable .deleteBtn {
    margin: 0.125rem;
}
</style>

<?php
crudRenderPage($config);
include __DIR__ . '/../includes/footer.php';
