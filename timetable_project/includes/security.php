<?php
/**
 * Security helper functions.
 * Provides role checks, CSRF token helpers, JSON errors, redirects, and safe output.
 */

function appBaseUrl(): string
{
    return '/timetable_project/';
}

function loginUrl(): string
{
    return appBaseUrl() . 'login.php';
}

function roleHomeUrl(?string $role = null): string
{
    $role = $role ?? ($_SESSION['role'] ?? '');

    return match ($role) {
        'admin' => appBaseUrl() . 'dashboard.php',
        'teacher' => appBaseUrl() . 'teacher_timetable.php',
        'student' => appBaseUrl() . 'student_timetable.php',
        default => loginUrl(),
    };
}

function redirectToRoleHome(): void
{
    header('Location: ' . roleHomeUrl());
    exit();
}

function requireRole(string $role, ?string $redirect = null): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (($_SESSION['role'] ?? '') !== $role) {
        $target = isset($_SESSION['role']) ? roleHomeUrl() : ($redirect ?? loginUrl());
        header("Location: {$target}");
        exit();
    }
}

function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfForPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);

        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Security token expired. Refresh the page and try again.']);
        } else {
            echo 'Security token expired. Refresh the page and try again.';
        }

        exit();
    }
}

function jsonError(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}
