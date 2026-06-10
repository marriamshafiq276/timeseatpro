<?php
/**
 * Admin CRUD page for contacts.
 * Maintains contact information shown or referenced by support workflows.
 */
include_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/config.php';

requireRole('admin');

$res = $conn->query("SELECT id, name, email, message, ip_address, created_at, status FROM contacts ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

<?php include_once __DIR__ . '/../includes/nav.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-8">
    <section class="bg-white border border-slate-200 rounded-xl shadow-xl p-6">
        <h1 class="text-2xl font-bold">Contact Messages</h1>
        <p class="text-sm text-slate-600">Messages submitted via the public contact form.</p>

        <div class="mt-6 overflow-x-auto">
            <table id="contactsTable" class="min-w-full table-auto">
                <thead class="bg-emerald-700 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Message</th>
                    <th class="px-4 py-2 text-left">IP</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Status</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3"><?= htmlspecialchars($row['id']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['email']) ?></td>
                        <td class="px-4 py-3 max-w-[40ch] truncate"><?= htmlspecialchars($row['message']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    $(document).ready(function () {
        $('#contactsTable').DataTable({"order": [[5, "desc"]]});
    });
</script>

</body>
</html>
