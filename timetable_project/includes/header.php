<?php
/**
 * Authenticated layout header.
 * Defines shared document metadata, CSS/JS assets, and opening page structure.
 */
// HEADER.PHP
require_once __DIR__ . '/security.php';

// Start output buffering and session
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ' . loginUrl());
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Scheduling & Seating Suite</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        /* Fix DataTables search bar alignment */
        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
        }
    </style>
    <script>
        window.appCsrfToken = "<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
                if (!form.querySelector('input[name="csrf_token"]')) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = window.appCsrfToken;
                    form.appendChild(input);
                }
            });
        });

        if (window.jQuery) {
            jQuery.ajaxSetup({
                beforeSend: function (xhr, settings) {
                    if ((settings.type || settings.method || 'GET').toUpperCase() === 'POST') {
                        xhr.setRequestHeader('X-CSRF-Token', window.appCsrfToken);
                    }
                }
            });
        }
    </script>
</head>

<body class="bg-slate-100 min-h-screen">

<!-- TOP UNIVERSITY HEADER BAR -->
<header class="bg-emerald-900 border-b border-emerald-800 text-emerald-100 px-6 py-3 flex justify-between items-center">

    <!-- System Title -->
    <h1 class="text-lg font-semibold tracking-wide">
        Academic Scheduling & Seating Suite
    </h1>

    <!-- Logged-in User -->
    <div class="text-sm">
        Logged in as
        <span class="font-semibold">
            <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
    </div>

</header>

<?php
// Success message
if (isset($_SESSION['timetable_generated']) && $_SESSION['timetable_generated'] === true) {
    echo '
    <div class="container mx-auto px-4 mt-4">
        <div class="border border-green-300 bg-green-50 text-green-800 px-4 py-3 rounded text-sm flex justify-between items-center">
            <span class="font-medium">Timetable generated successfully using official data.</span>
            <button onclick="this.parentElement.style.display=\'none\'" class="font-bold text-lg leading-none">&times;</button>
        </div>
    </div>';
    unset($_SESSION['timetable_generated']);
}
?>
