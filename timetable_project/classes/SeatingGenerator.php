<?php
/**
 * Seating-plan generation engine.
 * Allocates active students into active rooms while rotating teachers, subjects, and day/hour slots.
 */

class SeatingGenerator {

    private $conn;
    private $version_id;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function generate() {

        // 1. Create version first
        $this->createVersion();

        // 2. Fetch data
        $rooms    = $this->fetchActive("rooms", "capacity DESC");
        $students = $this->fetchActive("students", "class, batch, id");
        $teachers = $this->fetchActive("teachers");
        $subjects = $this->fetchActive("subjects");
        $days     = $this->fetchActive("days_hours");

        if (empty($rooms) || empty($students) || empty($subjects) || empty($days) || empty($teachers)) {
            throw new Exception("Incomplete data for seating generation. Add active rooms, students, teachers, subjects, and days/hours first.");
        }

        $room_index = 0;
        $teacher_index = 0;
        $subject_index = 0;
        $day_index = 0;
        $seat_no = 1;
        $used_in_room = 0;

        $total_entries = 0;

        mysqli_begin_transaction($this->conn);

        try {

            foreach ($students as $student) {

                if (!isset($rooms[$room_index])) break;

                $room    = $rooms[$room_index];

                $teacher = $teachers[$teacher_index % count($teachers)];
                $subject = $subjects[$subject_index % count($subjects)];
                $day     = $days[$day_index % count($days)];

                $stmt = $this->conn->prepare("
                    INSERT INTO seat_allocation 
                    (student_id, room_id, teacher_id, seat_no, subject_id, day_hour_id, version_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }

                $teacher_id = $teacher['id'] ?? null;

                $stmt->bind_param(
                    "iiiiiii",
                    $student['id'],
                    $room['id'],
                    $teacher_id,
                    $seat_no,
                    $subject['id'],
                    $day['id'],
                    $this->version_id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Insert failed: " . $stmt->error);
                }

                $seat_no++;
                $used_in_room++;
                $subject_index++;
                $day_index++;
                $total_entries++;

                if ($used_in_room >= $room['capacity']) {
                    $room_index++;
                    $teacher_index++;
                    $seat_no = 1;
                    $used_in_room = 0;
                }
            }

            // update version table (FIXED TABLE NAME)
            $update = $this->conn->prepare("
                UPDATE seating_versions
                SET total_entries = ?
                WHERE id = ?
            ");

            if ($update) {
                $update->bind_param("ii", $total_entries, $this->version_id);
                $update->execute();
            }

            mysqli_commit($this->conn);

            return $this->version_id;

        } catch (Exception $e) {

            mysqli_rollback($this->conn);
            throw $e;
        }
    }

    private function createVersion() {

        $generated_by = $_SESSION['username'] ?? 'Admin';
        $name = "Seating Plan " . date("Y-m-d H:i:s");

        $stmt = $this->conn->prepare("
            INSERT INTO seating_versions (version_name, generated_by)
            VALUES (?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Version insert failed: " . $this->conn->error);
        }

        $stmt->bind_param("ss", $name, $generated_by);
        $stmt->execute();

        $this->version_id = $this->conn->insert_id;
    }

    private function fetchActive($table, $order = "id ASC") {

        if ($table === 'rooms') {
            $sql = "
                SELECT r.*, b.name AS building_name
                FROM rooms r
                LEFT JOIN buildings b ON r.building_id = b.id
                WHERE r.status='Active'
                ORDER BY {$order}
            ";
        } else {
            $sql = "SELECT * FROM `$table` WHERE status='Active' ORDER BY $order";
        }

        $result = mysqli_query($this->conn, $sql);

        if (!$result) {
            return [];
        }

        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
