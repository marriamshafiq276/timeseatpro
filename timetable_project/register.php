<?php
/**
 * User registration page.
 * Creates teacher/student accounts for later admin approval and role-linked access.
 */
include "includes/config.php";
require_once "includes/user_helpers.php";
require_once "includes/security.php";

$message = "";

if (isset($_POST['register'])) {
    requireCsrfForPost();

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    $username = trim($_POST['username']);
    $raw_password = $_POST['password'];
    $password = userHashPassword($raw_password);
    $role = $_POST['role'];

    if (!in_array($role, ['teacher', 'student'], true)) {
        $message = "Please select a valid role.";
    } elseif (strlen($raw_password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif ($role == 'admin') {
        $message = "Admin registration is not allowed!";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists!";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO users (username, password, role, full_name, email, status)
                VALUES (?, ?, ?, ?, ?, 'Pending')
            ");

            $stmt->bind_param(
                "sssss",
                $username,
                $password,
                $role,
                $full_name,
                $email
            );

            if ($stmt->execute()) {
                $message = "Registered successfully! Wait for admin approval before login.";
            } else {
                $message = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>University Registration</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family: 'Inter', sans-serif; }
</style>
</head>

<body class="min-h-screen bg-emerald-950/5 flex items-center justify-center px-6">

<div class="w-full max-w-4xl bg-white border border-emerald-900 rounded-xl shadow-xl flex overflow-hidden">

<!-- LEFT SIDE -->
<div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-emerald-950 to-emerald-800 text-white p-10 flex-col justify-between">

    <div>
        <img src="assets/logo.png" class="w-24 mb-6">

        <h2 class="text-xl font-bold uppercase tracking-wide leading-snug">
            University Registration Portal
        </h2>

        <p class="mt-5 text-sm text-emerald-100 leading-relaxed">
            This registration system is designed to securely onboard new users into the University Management System.
            It ensures proper identity creation for students, teachers, and administrative staff with role-based access control.
        </p>

        <p class="mt-4 text-sm text-emerald-100 leading-relaxed">
            By creating an account, users gain access to academic services including course information, schedules,
            attendance records, and institutional notifications through a centralized platform.
        </p>

        <p class="mt-4 text-sm text-emerald-100 leading-relaxed">
            All user data is securely stored and managed to maintain privacy, integrity, and system reliability across
            all university operations.
        </p>
    </div>

    <div class="text-[11px] text-emerald-200 mt-6 leading-relaxed">
    <p>
        © <?php echo date("Y"); ?> University Management System. All rights reserved.
    </p>

    <p class="mt-1">
        Supervised By: 
        <span class="font-semibold text-white">
            Dr. Qamar Nawaz
        </span>
    </p>

    <p>
        Developed By: 
        <span class="font-semibold text-white">
            Marriam Shafiq
        </span>
    </p>
</div>

</div>

<!-- RIGHT SIDE -->
<div class="w-full md:w-1/2 p-8 md:p-10">

    <div class="mb-7 border-b border-emerald-900 pb-4">
        <h1 class="text-lg font-semibold text-emerald-950">
            Create Account
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Teacher / Student Registration
        </p>
    </div>

    <!-- MESSAGE -->
    <?php if (!empty($message)): ?>
        <div class="mb-4 text-xs px-4 py-2 rounded border
            <?php echo (strpos($message, 'Registered successfully') === 0) 
                ? 'bg-green-50 text-green-700 border-green-300' 
                : 'bg-red-50 text-red-700 border-red-300'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="space-y-4">
        <?= csrfInput() ?>

        <!-- FULL NAME -->
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Full Name</label>
            <input type="text" name="full_name" required
                class="w-full px-4 py-2 text-sm border rounded-lg focus:border-emerald-800"
                placeholder="Enter full name">
        </div>

        <!-- EMAIL -->
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Email</label>
            <input type="email" name="email" required
                class="w-full px-4 py-2 text-sm border rounded-lg focus:border-emerald-800"
                placeholder="Enter email">
        </div>

        <!-- USERNAME -->
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Username</label>
            <input type="text" name="username" required
                class="w-full px-4 py-2 text-sm border rounded-lg focus:border-emerald-800"
                placeholder="Enter username">
        </div>

        <!-- PASSWORD -->
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Password</label>
            <input type="password" name="password" required
                class="w-full px-4 py-2 text-sm border rounded-lg focus:border-emerald-800"
                placeholder="Enter password">
        </div>

        <!-- ROLE -->
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Register As</label>
            <select name="role" required
                class="w-full px-4 py-2 text-sm border rounded-lg focus:border-emerald-800">

                <option value="">Select Role</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
        </div>

        <!-- BUTTON -->
<button type="submit" name="register"
    class="w-full bg-emerald-950 hover:bg-emerald-900 text-white py-2 rounded-lg font-semibold">
    Create Account
</button>

<!-- LOGIN BUTTON -->
<a href="login.php"
   class="w-full mt-3 block text-center bg-slate-700 hover:bg-slate-800 text-white py-2 rounded-lg font-semibold">
    Login
</a>

    </form>

    
</div>
</div>

</body>
</html>
