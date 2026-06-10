<?php
/**
 * User/account helper functions.
 * Handles password verification, migration, account status checks, and link lookups.
 */

function userPasswordNeedsMigration(string $hash): bool
{
    return strlen($hash) === 32 && ctype_xdigit($hash);
}

function userVerifyPassword(string $rawPassword, string $storedHash): bool
{
    if (userPasswordNeedsMigration($storedHash)) {
        return hash_equals($storedHash, md5($rawPassword));
    }

    return password_verify($rawPassword, $storedHash);
}

function userHashPassword(string $rawPassword): string
{
    return password_hash($rawPassword, PASSWORD_DEFAULT);
}

function userMigratePassword(mysqli $conn, int $userId, string $rawPassword): void
{
    $hash = userHashPassword($rawPassword);
    $stmt = $conn->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");

    if ($stmt) {
        $stmt->bind_param("si", $hash, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

function userCanLogin(array $user): bool
{
    return strtolower((string) ($user['status'] ?? '')) === 'active';
}

function userLabel(array $row): string
{
    $name = trim((string) ($row['name'] ?? $row['student_name'] ?? $row['username'] ?? ''));
    $extra = trim((string) ($row['email'] ?? $row['registration_no'] ?? $row['department'] ?? ''));

    return $extra === '' ? $name : "{$name} ({$extra})";
}
