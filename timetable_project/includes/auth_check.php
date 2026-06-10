<?php
/**
 * Shared authentication bootstrap.
 * Starts sessions and provides baseline checks used by protected pages.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . loginUrl());
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);

        if (!empty($_POST['ajax']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Security token expired. Refresh the page and try again.']);
        } else {
            echo 'Security token expired. Refresh the page and try again.';
        }

        exit();
    }
}
