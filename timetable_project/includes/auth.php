<?php
/**
 * Authenticated-page session guard.
 * Redirects guests away from pages that require a logged-in user.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   GLOBAL ACCESS CONTROL
   ========================= */
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    session_destroy();
    header("Location: /timetable_project/login.php");
    exit();
}
