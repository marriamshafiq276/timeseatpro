<?php
/**
 * Login form page.
 * Provides role-aware sign-in and displays authentication feedback from the session.
 */
session_start();
require_once 'includes/security.php';

setcookie('remember_user', '', time() - 3600, '/');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Administration Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-100">

<header class="border-b border-emerald-200 bg-white shadow-sm">
    <div class="bg-emerald-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-2 text-xs font-semibold sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <span class="uppercase tracking-wide">University Administration System</span>
            <span class="text-emerald-100">Working Hours: Monday-Friday 8:00am - 16:00pm | Phone: +92419200161-70</span>
        </div>
    </div>

    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <a href="index.php" class="flex min-w-0 items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-emerald-200 bg-white shadow-sm">
                <img src="assets/logo.png" alt="University Logo" class="h-10 w-10 object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-base font-extrabold uppercase tracking-wide text-emerald-950 sm:text-lg">
                    Academic Scheduling & Seating Suite
                </span>
                <span class="hidden text-xs font-semibold text-emerald-700 sm:block">
                    Scheduling and Seating Management Portal
                </span>
            </span>
        </a>

        <nav class="flex gap-2 overflow-x-auto lg:gap-6">
            <a href="index.php" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-900 lg:px-0">Home</a>
            <a href="about.php" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-900 lg:px-0">About</a>
            <a href="features.php" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-900 lg:px-0">Features</a>
            <a href="support.php" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-900 lg:px-0">Support</a>
            <a href="contact.php" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-900 lg:px-0">Contact</a>
        </nav>
    </div>
</header>

<main class="flex min-h-[calc(100vh-150px)] items-center justify-center px-6 py-10">
<div class="w-full max-w-4xl bg-white border border-slate-200 rounded-xl shadow-xl flex overflow-hidden">

   

       <!-- LEFT SIDE -->
<div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-emerald-950 to-emerald-800 text-white p-10 flex-col justify-between">

    <div>
        <img src="assets/logo.png" alt="University Logo" class="w-24 mb-6">

        <h2 class="text-xl font-semibold uppercase tracking-wide leading-snug">
            University Administration Portal
        </h2>

        <p class="mt-5 text-sm text-emerald-100 leading-relaxed">
            A secure, centralized academic management system designed to streamline university operations
            and enhance communication between administration, faculty, and students.
        </p>

        <p class="mt-4 text-sm text-emerald-100 leading-relaxed">
            This platform enables efficient management of academic schedules, attendance tracking, course coordination,
            and student records with accuracy and reliability.
        </p>

        <p class="mt-4 text-sm text-emerald-100 leading-relaxed">
            Built with a focus on security, performance, and usability, ensuring a smooth digital experience for all users
            across the institution.
        </p>
    </div>

    <div class="text-[11px] text-emerald-200 mt-6 leading-relaxed">
    <p>© <?php echo date("Y"); ?> University Management System. All rights reserved.</p>
    
    <p class="mt-1">
        Supervised By: <span class="font-semibold text-white">Dr. Qamar Nawaz</span>
    </p>

    <p>
        Developed By: <span class="font-semibold text-white">Marriam Shafiq</span>
    </p>
</div>

</div>

    <!-- RIGHT SIDE -->
    <div class="w-full md:w-1/2 p-8 md:p-10">

        <div class="mb-7 border-b border-slate-200 pb-4">
            <h1 class="text-lg font-medium text-emerald-950">
                System Login
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Choose your access type and enter your credentials.
            </p>
        </div>

        <!-- ERROR -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 border border-red-300 bg-red-50 text-red-800 px-4 py-2 rounded text-xs">
                <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form action="authenticate.php" method="POST" class="space-y-5">
            <?= csrfInput() ?>
            <input type="hidden" name="login_role" id="login_role">

            <!-- ROLE BUTTONS -->
            <div>
                <label class="block text-xs font-medium text-slate-600 uppercase mb-2">
                    Login As
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button type="button" data-role="admin"
                        class="role-button rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        Admin
                    </button>

                    <button type="button" data-role="teacher"
                        class="role-button rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        Teacher
                    </button>

                    <button type="button" data-role="student"
                        class="role-button rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        Student
                    </button>
                </div>
            </div>

            <!-- USERNAME -->
            <div>
                <label class="block text-xs font-medium text-slate-600 uppercase mb-1">
                    Username
                </label>
                <input type="text" name="login_username" required
                    placeholder="Enter username"
                    class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg
                           focus:outline-none focus:border-emerald-800
                           focus:ring-1 focus:ring-emerald-800">
            </div>

            <!-- PASSWORD -->
            <div>
                <label class="block text-xs font-medium text-slate-600 uppercase mb-1">
                    Password
                </label>
                <input type="password" name="login_password" required
                    placeholder="Enter password"
                    class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg
                           focus:outline-none focus:border-emerald-800
                           focus:ring-1 focus:ring-emerald-800">
            </div>

            <!-- LOGIN BUTTON -->
            <button type="submit"
                class="w-full bg-emerald-900 hover:bg-emerald-800
                       text-white text-sm font-medium py-2.5
                       rounded-lg transition duration-200 shadow-sm">
                Login
            </button>


<a href="register.php"
   class="w-full mt-2 block text-center border border-slate-300 bg-white hover:bg-slate-50
          text-slate-700 py-2 rounded-lg text-sm font-medium transition duration-200">
    Register
</a>
        </form>

        <div class="mt-9 text-[11px] text-slate-500 leading-relaxed">
            Authorized access only.
        </div>

    </div>
</div>
</main>

<script>
    const roleInput = document.getElementById('login_role');
    const roleButtons = document.querySelectorAll('.role-button');
    const activeClasses = ['border-emerald-800', 'bg-emerald-50', 'text-emerald-900'];
    const inactiveClasses = ['border-slate-300', 'bg-white', 'text-slate-800'];

    roleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            roleInput.value = button.dataset.role;

            roleButtons.forEach((item) => {
                item.classList.remove(...activeClasses);
                item.classList.add(...inactiveClasses);
            });

            button.classList.remove(...inactiveClasses);
            button.classList.add(...activeClasses);
        });
    });
</script>

</body>
</html>
