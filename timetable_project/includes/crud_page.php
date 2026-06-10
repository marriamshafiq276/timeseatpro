<?php
/**
 * Shared helpers for admin CRUD pages.
 */

function crudEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function crudFieldValue(array $field, array $row): string
{
    $display = $field['display'] ?? $field['name'];
    return (string)($row[$display] ?? '');
}

function crudDataValue(array $field, array $row): string
{
    return (string)($row[$field['name']] ?? '');
}

function crudRenderInput(array $field, string $prefix = '', $value = null): void
{
    $name = $field['name'];
    $id = $prefix === 'edit' ? 'edit_' . $name : $name;
    $inputName = $prefix === 'edit' ? null : $name;
    $required = !empty($field['required']) ? ' required' : '';
    $class = $prefix === 'edit'
        ? 'border w-full px-3 py-2 rounded'
        : 'border px-3 py-2 rounded w-full';
    $valueAttr = crudEscape($value);

    if (($field['type'] ?? 'text') === 'select') {
        echo '<select id="' . crudEscape($id) . '"';
        if ($inputName !== null) {
            echo ' name="' . crudEscape($inputName) . '"';
        }
        echo ' class="' . $class . '"' . $required . '>';

        foreach (($field['options'] ?? []) as $optionValue => $optionLabel) {
            $selected = (string)$optionValue === (string)$value ? ' selected' : '';
            echo '<option value="' . crudEscape($optionValue) . '"' . $selected . '>';
            echo crudEscape($optionLabel);
            echo '</option>';
        }

        echo '</select>';
        return;
    }

    if (($field['type'] ?? 'text') === 'textarea') {
        echo '<textarea id="' . crudEscape($id) . '"';
        if ($inputName !== null) {
            echo ' name="' . crudEscape($inputName) . '"';
        }
        echo ' class="' . $class . '" rows="' . crudEscape($field['rows'] ?? 2) . '"' . $required . '>';
        echo $valueAttr;
        echo '</textarea>';
        return;
    }

    $type = crudEscape($field['type'] ?? 'text');
    echo '<input type="' . $type . '" id="' . crudEscape($id) . '"';
    if ($inputName !== null) {
        echo ' name="' . crudEscape($inputName) . '"';
    }
    echo ' class="' . $class . '" value="' . $valueAttr . '"' . $required . '>';
}

function crudNextId(mysqli $conn, string $table): int
{
    $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    return (int)($conn->query("SELECT IFNULL(MAX(id),0)+1 AS nid FROM {$safeTable}")->fetch_assoc()['nid'] ?? 1);
}

function crudFetchRows(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function crudBindParams(mysqli_stmt $stmt, string $types, array &$values): void
{
    $refs = [];
    $refs[] = $types;

    foreach ($values as $key => &$value) {
        $refs[] = &$value;
    }

    $stmt->bind_param(...$refs);
}

function crudNormalizeFieldValue(array $field, $value)
{
    if (($field['bind'] ?? 's') === 'i' && ($value === '' || $value === null)) {
        return null;
    }

    return $value ?? '';
}

function crudHandleAjax(mysqli $conn, array $config): void
{
    if (isset($_POST['upload_csv'])) {
        crudHandleCsvUpload($conn, $config);
    }

    if (!isset($_POST['ajax'])) {
        return;
    }

    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $config['table']);
    $fields = $config['fields'];

    if (!empty($config['require_csrf']) && function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        if (function_exists('jsonError')) {
            jsonError('Security token expired. Refresh the page and try again.', 403);
        }

        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Security token expired. Refresh the page and try again.']);
        exit();
    }

    if ($action === 'add') {
        if (isset($config['duplicate']) && is_callable($config['duplicate'])) {
            $existingId = $config['duplicate']($conn, $_POST);
            if ($existingId !== null) {
                jsonDuplicateResponse($existingId);
            }
        }

        $columns = array_column($fields, 'name');
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $statusSql = !empty($config['has_status']) ? ", status" : "";
        $statusValueSql = !empty($config['has_status']) ? ", 'Active'" : "";
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . "{$statusSql}, created_at, updated_at) VALUES ({$placeholders}{$statusValueSql}, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $types = implode('', array_column($fields, 'bind'));
        $values = [];

        foreach ($fields as $field) {
            $values[] = crudNormalizeFieldValue($field, $_POST[$field['name']] ?? '');
        }

        crudBindParams($stmt, $types, $values);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'id' => $stmt->insert_id
        ]);
        exit();
    }

    if ($action === 'update') {
        $assignments = [];
        foreach ($fields as $field) {
            $assignments[] = $field['name'] . '=?';
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $assignments) . ", updated_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $types = implode('', array_column($fields, 'bind')) . 'i';
        $values = [];

        foreach ($fields as $field) {
            $values[] = crudNormalizeFieldValue($field, $_POST[$field['name']] ?? '');
        }
        $values[] = $_POST['id'];

        crudBindParams($stmt, $types, $values);
        $stmt->execute();

        echo json_encode(['status' => 'success']);
        exit();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE id=?");
        $stmt->bind_param('i', $_POST['id']);
        $stmt->execute();

        echo json_encode(['status' => 'deleted']);
        exit();
    }
}

function crudHandleCsvUpload(mysqli $conn, array $config): void
{
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['csv_upload_message'] = 'Choose a valid CSV file before uploading.';
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit();
    }

    $fields = array_values(array_filter($config['fields'], function ($field) {
        return empty($field['csv_skip']);
    }));
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $config['table']);
    $columns = array_column($fields, 'name');
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $statusSql = !empty($config['has_status']) ? ", status" : "";
    $statusValueSql = !empty($config['has_status']) ? ", 'Active'" : "";
    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . "{$statusSql}, created_at, updated_at) VALUES ({$placeholders}{$statusValueSql}, NOW(), NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['csv_upload_message'] = 'CSV upload failed: ' . $conn->error;
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit();
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    $inserted = 0;
    $skipped = 0;

    if ($handle !== false) {
        $header = fgetcsv($handle);
        $headerMap = [];

        if (is_array($header)) {
            foreach ($header as $index => $name) {
                $normalized = strtolower(trim((string) $name));
                if ($normalized !== '') {
                    $headerMap[$normalized] = $index;
                }
            }
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $data = [];
            $values = [];

            foreach ($fields as $index => $field) {
                $key = strtolower($field['name']);
                $value = array_key_exists($key, $headerMap)
                    ? ($row[$headerMap[$key]] ?? '')
                    : ($row[$index] ?? '');
                $data[$field['name']] = $value;
                $values[] = crudNormalizeFieldValue($field, $value);
            }

            if (isset($config['duplicate']) && is_callable($config['duplicate']) && $config['duplicate']($conn, $data) !== null) {
                $skipped++;
                continue;
            }

            $types = implode('', array_column($fields, 'bind'));
            crudBindParams($stmt, $types, $values);

            if ($stmt->execute()) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);
    }

    $stmt->close();
    $_SESSION['csv_upload_message'] = "CSV upload complete. Added {$inserted} row(s), skipped {$skipped}.";
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit();
}

function crudRenderPage(array $config): void
{
    $tableId = $config['table_id'] ?? $config['table'] . 'Table';
    $fields = $config['fields'];
    $tableFields = array_values(array_filter($fields, function ($field) {
        return empty($field['hide_in_table']);
    }));
    $rows = $config['rows'];
    $gridClass = $config['form_grid'] ?? 'grid grid-cols-1 md:grid-cols-2 gap-4 items-end';
    $tableClass = $config['table_class'] ?? 'min-w-full border text-center table-fixed';
    $actionIndex = count($tableFields) + 1;
    ?>

<div class="container mx-auto p-6">

<?php if (!empty($config['before_form'])): ?>
    <?= $config['before_form'] ?>
<?php endif; ?>

<?php if (($config['csv_upload'] ?? true) !== false): ?>
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-lg font-bold mb-2 text-emerald-900">Upload <?= crudEscape($config['list_title'] ?? $config['table']) ?> via CSV</h2>
    <p class="text-sm text-slate-600 mb-4">
        CSV columns can use these headers: <?= crudEscape(implode(', ', array_column($fields, 'name'))) ?>.
    </p>

    <?php if (!empty($_SESSION['csv_upload_message'])): ?>
        <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <?= crudEscape($_SESSION['csv_upload_message']) ?>
        </div>
        <?php unset($_SESSION['csv_upload_message']); ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="file" name="csv_file" accept=".csv" required class="border px-3 py-2 rounded w-full">
        <button type="submit" name="upload_csv" class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800 sm:w-44">Upload CSV</button>
    </form>
</div>
<?php endif; ?>

<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-lg font-bold mb-4 text-emerald-900"><?= crudEscape($config['add_title']) ?></h2>

    <form id="addForm" class="<?= crudEscape($gridClass) ?>">
        <div>
            <label class="block mb-1 font-medium">ID</label>
            <input type="text" id="nextId" class="border px-3 py-2 rounded w-full bg-gray-100" value="<?= crudEscape($config['next_id']) ?>" readonly>
        </div>

        <?php foreach ($fields as $field): ?>
            <div class="<?= crudEscape($field['form_class'] ?? '') ?>">
                <label class="block mb-1 font-medium"><?= crudEscape($field['label']) ?></label>
                <?php crudRenderInput($field); ?>
            </div>
        <?php endforeach; ?>

        <div class="<?= crudEscape($config['button_class'] ?? 'md:col-span-2') ?>">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded hover:bg-emerald-800 w-full">Save</button>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded shadow">
    <h2 class="text-lg font-bold mb-4 text-emerald-900 text-center"><?= crudEscape($config['list_title']) ?></h2>
    <div class="overflow-x-auto w-full">
    <div class="min-w-[700px] md:min-w-full">
        <table id="<?= crudEscape($tableId) ?>" class="<?= crudEscape($tableClass) ?>">
            <thead class="bg-emerald-700 text-white">
                <tr>
                    <th class="border px-4 py-3 w-16">ID</th>
                    <?php foreach ($tableFields as $field): ?>
                        <th class="border px-4 py-3"><?= crudEscape($field['table_label'] ?? $field['label']) ?></th>
                    <?php endforeach; ?>
                    <th class="border px-4 py-3 w-32">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr id="row<?= crudEscape($row['id']) ?>">
                        <td class="border px-4 py-2"><?= crudEscape($row['id']) ?></td>
                        <?php foreach ($tableFields as $field): ?>
                            <td class="border px-4 py-2 <?= crudEscape($field['cell_class'] ?? $field['name']) ?>">
                                <?= crudEscape(crudFieldValue($field, $row)) ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="border px-4 py-2">
                            <button class="bg-emerald-600 text-white px-3 py-1 rounded editBtn"
                                data-id="<?= crudEscape($row['id']) ?>"
                                <?php foreach ($fields as $field): ?>
                                    data-<?= crudEscape($field['name']) ?>="<?= crudEscape(crudDataValue($field, $row)) ?>"
                                <?php endforeach; ?>
                            >Edit</button>
                            <button class="bg-red-600 text-white px-3 py-1 rounded deleteBtn" data-id="<?= crudEscape($row['id']) ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    </div>
</div>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow w-[92vw] max-w-3xl max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4"><?= crudEscape($config['edit_title']) ?></h2>

        <form id="editForm">
            <input type="hidden" id="editId">

            <?php foreach ($fields as $field): ?>
                <div class="mb-3">
                    <label><?= crudEscape($field['label']) ?></label>
                    <?php crudRenderInput($field, 'edit'); ?>
                </div>
            <?php endforeach; ?>

            <div class="flex gap-3">
                <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded w-full hover:bg-emerald-800">Update</button>
                <button type="button" id="cancelEdit" class="bg-gray-500 text-white px-4 py-2 rounded w-full hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
$(function(){
    var crudFields = <?= json_encode(array_map(function ($field) {
        return [
            'name' => $field['name'],
            'cellClass' => $field['cell_class'] ?? $field['name'],
            'type' => $field['type'] ?? 'text',
            'displayFromSelect' => !empty($field['display_from_select']),
            'showInTable' => empty($field['hide_in_table'])
        ];
    }, $fields)) ?>;

    function htmlEscape(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function fieldAddValue(field) {
        return $('[name="' + field.name + '"]').val();
    }

    function fieldAddDisplay(field) {
        var input = $('[name="' + field.name + '"]');
        return field.displayFromSelect ? input.find('option:selected').text().trim() : input.val();
    }

    function fieldEditValue(field) {
        return $('#edit_' + field.name).val();
    }

    function fieldEditDisplay(field) {
        var input = $('#edit_' + field.name);
        return field.displayFromSelect ? input.find('option:selected').text().trim() : input.val();
    }

    var table = $('#<?= crudEscape($tableId) ?>').DataTable({
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100, 1000, 10000, -1],
            [10, 25, 50, 100, 1000, 10000, "All"]
        ],
        stateSave: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: <?= (int)$actionIndex ?> }
        ]
    });

    $('#addForm').submit(function(e){
        e.preventDefault();

        var payload = { ajax: 1, action: 'add', csrf_token: window.appCsrfToken || '' };
        crudFields.forEach(function(field){
            payload[field.name] = fieldAddValue(field);
        });

        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(res){
                if (res.status === 'duplicate') {
                    alert('This item already exists with ID ' + res.id + '.');
                    $('#nextId').val(parseInt(res.id, 10) + 1);
                    return;
                }

                var buttonData = '';
                crudFields.forEach(function(field){
                    buttonData += ' data-' + field.name + '="' + htmlEscape(fieldAddValue(field)) + '"';
                });

                var newRow = '<tr id="row' + htmlEscape(res.id) + '">' +
                    '<td class="border px-4 py-2">' + htmlEscape(res.id) + '</td>';

                crudFields.forEach(function(field){
                    if (!field.showInTable) {
                        return;
                    }

                    newRow += '<td class="border px-4 py-2 ' + htmlEscape(field.cellClass) + '">' +
                        htmlEscape(fieldAddDisplay(field)) +
                        '</td>';
                });

                newRow += '<td class="border px-4 py-2">' +
                    '<button class="bg-emerald-600 text-white px-3 py-1 rounded editBtn" data-id="' + htmlEscape(res.id) + '"' + buttonData + '>Edit</button> ' +
                    '<button class="bg-red-600 text-white px-3 py-1 rounded deleteBtn" data-id="' + htmlEscape(res.id) + '">Delete</button>' +
                    '</td></tr>';

                table.row.add($(newRow)).draw(false);
                $('#addForm')[0].reset();
                $('#nextId').val(parseInt(res.id, 10) + 1);
            }
        });
    });

    $(document).on('click', '.editBtn', function(){
        var button = $(this);
        $('#editId').val(button.data('id'));

        crudFields.forEach(function(field){
            $('#edit_' + field.name).val(button.attr('data-' + field.name));
        });

        $('#editModal').removeClass('hidden');
    });

    $('#editForm').submit(function(e){
        e.preventDefault();

        var id = $('#editId').val();
        var payload = { ajax: 1, action: 'update', id: id, csrf_token: window.appCsrfToken || '' };
        crudFields.forEach(function(field){
            payload[field.name] = fieldEditValue(field);
        });

        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(){
                var row = $('#row' + id);
                var button = row.find('.editBtn');

                crudFields.forEach(function(field){
                    if (field.showInTable) {
                        row.find('.' + field.cellClass).text(fieldEditDisplay(field));
                    }

                    button.attr('data-' + field.name, fieldEditValue(field));
                });

                $('#editModal').addClass('hidden');
            }
        });
    });

    $(document).on('click', '.deleteBtn', function(){
        if(!confirm(<?= json_encode($config['delete_confirm'] ?? 'Delete this record?') ?>)) return;

        var id = $(this).data('id');
        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: { ajax: 1, action: 'delete', id: id, csrf_token: window.appCsrfToken || '' },
            success: function(){
                table.row($('#row' + id)).remove().draw(false);
            }
        });
    });

    $('#cancelEdit').click(function(){
        $('#editModal').addClass('hidden');
    });

    $('#editModal').click(function(e){
        if(!$(e.target).closest('.bg-white').length){
            $('#editModal').addClass('hidden');
        }
    });
});
</script>
<?php
}
