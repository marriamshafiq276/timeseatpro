<?php
/**
 * Login POST controller.
 * Validates credentials, upgrades legacy password hashes, and routes users by role.
 */
session_start();
include 'includes/config.php';
require_once 'includes/user_helpers.php';
require_once 'includes/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrfForPost();

    $username = trim($_POST['login_username'] ?? '');
    $rawPassword = $_POST['login_password'] ?? '';
    $role = $_POST['login_role'] ?? '';

    /* =========================
       VALIDATION FIX
       ========================= */
    if (empty($username) || empty($rawPassword) || empty($role)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: login.php");
        exit();
    }

    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        $_SESSION['error'] = "Please choose a valid login type.";
        header("Location: login.php");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id, username, password, role, linked_id, status
        FROM users 
        WHERE username = ? AND role = ?
    ");

    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (!userVerifyPassword($rawPassword, $user['password'])) {
            $_SESSION['error'] = "Invalid login credentials!";
            header("Location: login.php");
            exit();
        }

        if (!userCanLogin($user)) {
            $_SESSION['error'] = "Your account is pending admin approval.";
            header("Location: login.php");
            exit();
        }

        if (userPasswordNeedsMigration($user['password'])) {
            userMigratePassword($conn, (int) $user['id'], $rawPassword);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['linked_id'] = $user['linked_id'];

        /* =========================
           ROLE REDIRECT (CLEAN)
           ========================= */
        switch ($user['role']) {

            case 'admin':
                header("Location: dashboard.php");
                break;

            case 'teacher':
                header("Location: teacher_timetable.php");
                break;

            case 'student':
                header("Location: student_timetable.php");
                break;
        }

        exit();

    } else {
        $_SESSION['error'] = "Invalid login credentials!";
        header("Location: login.php");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>
