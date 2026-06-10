<?php
/**
 * Lightweight schema upgrades used by existing local XAMPP databases.
 */

function tableColumnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0;
    $stmt->close();

    return $exists;
}

function tableIndexExists(mysqli $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $index);
    $stmt->execute();
    $exists = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0;
    $stmt->close();

    return $exists;
}

function ensureBuildingRoomSupport(mysqli $conn): void
{
    if (!tableColumnExists($conn, 'rooms', 'building_id')) {
        $conn->query("ALTER TABLE rooms ADD building_id INT DEFAULT NULL AFTER id");
    }

    if (!tableIndexExists($conn, 'rooms', 'idx_rooms_building_id')) {
        $conn->query("CREATE INDEX idx_rooms_building_id ON rooms(building_id)");
    }

    if (tableIndexExists($conn, 'rooms', 'uniq_rooms_name_floor')) {
        $conn->query("ALTER TABLE rooms DROP INDEX uniq_rooms_name_floor");
    }

    if (!tableIndexExists($conn, 'rooms', 'uniq_rooms_building_name_floor')) {
        $conn->query("CREATE UNIQUE INDEX uniq_rooms_building_name_floor ON rooms(building_id, room_name, floor)");
    }
}
