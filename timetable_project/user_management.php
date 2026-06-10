<?php
/**
 * Admin user-management page.
 * Reviews pending accounts, links users to teachers/students, and changes statuses.
 */
session_start();

include 'includes/auth_check.php';
include 'includes/config.php';
require_once 'includes/user_helpers.php';
require_once 'includes/security.php';

requireRole('admin', 'login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfForPost();

    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    $linked_id = ($_POST['linked_id'] ?? '') !== '' ? (int) $_POST['linked_id'] : null;

    if (!in_array($status, ['Active', 'Pending', 'Rejected'], true)) {
        $status = 'Pending';
    }

    $userStmt = $conn->prepare("SELECT role FROM users WHERE id=? AND role <> 'admin'");
    $userStmt->bind_param("i", $id);
    $userStmt->execute();
    $managedUser = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$managedUser) {
        $_SESSION['user_management_message'] = "User account was not found or cannot be updated.";
        header("Location: user_management.php");
        exit();
    }

    if ($managedUser['role'] === 'student' && $status === 'Active') {
        $studentClass = '';

        if ($linked_id !== null) {
            $studentStmt = $conn->prepare("SELECT class FROM students WHERE id=?");
            $studentStmt->bind_param("i", $linked_id);
            $studentStmt->execute();
            $studentClass = trim((string) ($studentStmt->get_result()->fetch_assoc()['class'] ?? ''));
            $studentStmt->close();
        }

        if ($studentClass === '') {
            $_SESSION['user_management_message'] = "Student users must be linked to a student record with a class before activation.";
            header("Location: user_management.php");
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE users SET status=?, linked_id=?, updated_at=NOW() WHERE id=? AND role <> 'admin'");
    $stmt->bind_param("sii", $status, $linked_id, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['user_management_message'] = "User account updated.";
    header("Location: user_management.php");
    exit();
}

$teachers = [];
$teacherResult = $conn->query("SELECT id, name, email, department FROM teachers ORDER BY name");
while ($row = $teacherResult->fetch_assoc()) {
    $teachers[] = $row;
}

$students = [];
$studentClasses = [];
$studentResult = $conn->query("SELECT id, student_name, registration_no, class FROM students ORDER BY class, student_name");
while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row;
    $studentClasses[(int) $row['id']] = (string) ($row['class'] ?? '');
}

$users = $conn->query("
    SELECT id, full_name, email, username, role, linked_id, status, created_at
    FROM users
    ORDER BY FIELD(status, 'Pending', 'Active', 'Rejected'), role, username
");

include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="bg-slate-100 min-h-screen py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-emerald-800">User Approval and Linking</h1>
            <p class="text-sm text-slate-600 mt-2">Approve registered teachers/students and connect each login to the correct academic record.</p>
        </div>

        <?php if (!empty($_SESSION['user_management_message'])): ?>
            <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <?= htmlspecialchars($_SESSION['user_management_message']) ?>
            </div>
            <?php unset($_SESSION['user_management_message']); ?>
        <?php endif; ?>

        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-emerald-700 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Linked Record / Class</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr class="border-b border-slate-200">
                            <td class="px-4 py-3">
                                <strong class="block text-slate-900"><?= htmlspecialchars($user['username']) ?></strong>
                                <span class="text-slate-500"><?= htmlspecialchars($user['full_name'] ?? '') ?></span>
                            </td>
                            <td class="px-4 py-3 capitalize"><?= htmlspecialchars($user['role']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($user['email'] ?? '') ?></td>
                            <td class="px-4 py-3">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="text-slate-500">Admin account</span>
                                <?php else: ?>
                                    <?php $formId = 'userForm' . (int) $user['id']; ?>
                                    <div class="flex min-w-[320px] gap-2 items-center">
                                        <select name="linked_id" form="<?= $formId ?>" class="w-full border rounded px-3 py-2">
                                            <option value="">Not linked</option>
                                            <?php $options = $user['role'] === 'teacher' ? $teachers : $students; ?>
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?= (int) $option['id'] ?>" <?= (int) $user['linked_id'] === (int) $option['id'] ? 'selected' : '' ?>>
                                                    <?php if ($user['role'] === 'student'): ?>
                                                        <?= htmlspecialchars(userLabel($option) . ' - Class: ' . (($option['class'] ?? '') !== '' ? $option['class'] : 'Not set')) ?>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars(userLabel($option)) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if ($user['role'] === 'student' && !empty($user['linked_id'])): ?>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Class:
                                            <span class="font-semibold text-emerald-800">
                                                <?= htmlspecialchars($studentClasses[(int) $user['linked_id']] ?? 'Not set') ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="inline-flex rounded bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Active</span>
                                <?php else: ?>
                                    <select name="status" form="<?= $formId ?>" class="border rounded px-3 py-2">
                                        <?php foreach (['Pending', 'Active', 'Rejected'] as $status): ?>
                                            <option value="<?= $status ?>" <?= $user['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($user['created_at']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="text-slate-500">Protected</span>
                                <?php else: ?>
                                    <form id="<?= $formId ?>" method="POST">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2 rounded font-semibold">
                                            Save
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
