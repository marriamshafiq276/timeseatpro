<?php
/**
 * Duplicate-record helper functions.
 * Centralizes duplicate detection and AJAX duplicate responses for master-data forms.
 */

function normalizeString(string $value): string
{
    return mb_strtolower(trim($value));
}

function jsonDuplicateResponse(int $id): void
{
    echo json_encode([
        'status' => 'duplicate',
        'id' => $id
    ]);
    exit();
}

function getExistingTeacherId($conn, $cnic, $email, $name, $father_name, $department)
{
    $cnic = trim((string) $cnic);
    if ($cnic !== '') {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE cnic = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $cnic);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }
    }

    $email = trim((string) $email);
    if ($email !== '') {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }
    }

    $name = normalizeString($name);
    $father_name = normalizeString($father_name);
    $department = normalizeString($department);

    if ($name !== '' && $father_name !== '' && $department !== '') {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE LOWER(name) = ? AND LOWER(father_name) = ? AND LOWER(department) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('sss', $name, $father_name, $department);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }
    }

    return null;
}

function getExistingStudentId($conn, $registration_no, $student_name, $batch, $class)
{
    $registration_no = trim((string) $registration_no);
    if ($registration_no !== '') {
        $stmt = $conn->prepare("SELECT id FROM students WHERE registration_no = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $registration_no);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }
    }

    $student_name = normalizeString($student_name);
    $batch = normalizeString($batch);
    $class = normalizeString($class);

    if ($student_name !== '' && $batch !== '' && $class !== '') {
        $stmt = $conn->prepare("SELECT id FROM students WHERE LOWER(student_name) = ? AND LOWER(batch) = ? AND LOWER(class) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('sss', $student_name, $batch, $class);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }
    }

    return null;
}

function getExistingFacultyId($conn, $name)
{
    $name = normalizeString($name);
    if ($name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM faculties WHERE LOWER(name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingDepartmentId($conn, $faculty_id, $name)
{
    $name = normalizeString($name);
    if ($name === '' || empty($faculty_id)) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM departments WHERE faculty_id = ? AND LOWER(name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $faculty_id, $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingClassId($conn, $class_name, $semester, $section, $faculty_id = null)
{
    $class_name = normalizeString($class_name);
    $semester = normalizeString($semester);
    $section = normalizeString($section);

    if ($class_name === '' || $semester === '' || $section === '') {
        return null;
    }

    if ($faculty_id !== null) {
        $stmt = $conn->prepare("SELECT id FROM classes WHERE faculty_id = ? AND LOWER(class_name) = ? AND LOWER(semester) = ? AND LOWER(section) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('isss', $faculty_id, $class_name, $semester, $section);
            $stmt->execute();
            $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM classes WHERE LOWER(class_name) = ? AND LOWER(semester) = ? AND LOWER(section) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('sss', $class_name, $semester, $section);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingSubjectId($conn, $code)
{
    $code = normalizeString($code);
    if ($code === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(code) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingRoomId($conn, $room_name, $floor, $building_id = null)
{
    $room_name = normalizeString($room_name);
    $floor = normalizeString($floor);
    if ($room_name === '' || $floor === '') {
        return null;
    }

    $building_id = $building_id === '' ? null : $building_id;

    if ($building_id === null) {
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE building_id IS NULL AND LOWER(room_name) = ? AND LOWER(floor) = ? LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE building_id = ? AND LOWER(room_name) = ? AND LOWER(floor) = ? LIMIT 1");
    }

    if ($stmt) {
        if ($building_id === null) {
            $stmt->bind_param('ss', $room_name, $floor);
        } else {
            $stmt->bind_param('iss', $building_id, $room_name, $floor);
        }

        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingActivityId($conn, $name)
{
    $name = normalizeString($name);
    if ($name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM activities WHERE LOWER(name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingActivityTagId($conn, $tag_name)
{
    $tag_name = normalizeString($tag_name);
    if ($tag_name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM activity_tags WHERE LOWER(tag_name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $tag_name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingBuildingId($conn, $name)
{
    $name = normalizeString($name);
    if ($name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM buildings WHERE LOWER(name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingInstitutionId($conn, $name)
{
    $name = normalizeString($name);
    if ($name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM institution WHERE LOWER(institute_name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingDaysHoursId($conn, $day, $start_time, $end_time, $class_type)
{
    $day = normalizeString($day);
    $start_time = trim((string) $start_time);
    $end_time = trim((string) $end_time);
    $class_type = normalizeString($class_type);

    if ($day === '' || $start_time === '' || $end_time === '' || $class_type === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM days_hours WHERE LOWER(day) = ? AND start_time = ? AND end_time = ? AND LOWER(class_type) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ssss', $day, $start_time, $end_time, $class_type);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingSubactivityId($conn, $activity_id, $name)
{
    $name = normalizeString($name);
    if ($name === '' || empty($activity_id)) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM subactivities WHERE activity_id = ? AND LOWER(name) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $activity_id, $name);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingSpaceConstraintId($conn, $room, $room_type)
{
    $room = normalizeString($room);
    $room_type = normalizeString($room_type);
    if ($room === '' || $room_type === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM space_constraints WHERE LOWER(room) = ? AND LOWER(room_type) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $room, $room_type);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}

function getExistingTimeConstraintId($conn, $room, $day, $period)
{
    $room = normalizeString($room);
    $day = normalizeString($day);
    $period = trim((string) $period);

    if ($room === '' || $day === '' || $period === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM time_constraints WHERE LOWER(room) = ? AND LOWER(day) = ? AND period = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ssi', $room, $day, $period);
        $stmt->execute();
        $id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();
        if ($id) {
            return (int) $id;
        }
    }

    return null;
}
