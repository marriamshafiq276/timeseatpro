<?php
/**
 * Genetic-algorithm timetable engine.
 * Builds candidate timetables, scores conflicts, repairs placements, and returns the best schedule.
 */

class TimetableGA {
    private $teachers;
    private $subjects;
    private $rooms;
    private $days;
    private $day_period_map;
    private $classes;
    private $faculties;
    private $departments;
    private $activities;
    private $time_constraints;
    private $space_constraints;
    private $student_groups;
    
    private $population_size = 30;
    private $max_generations = 60;
    private $placement_attempts = 200;

    public function __construct($data) {
        $this->teachers    = $data['teachers'];
        $this->subjects    = $data['subjects'];
        $this->rooms       = $data['rooms'];
        $this->classes     = $data['classes'];
        $this->faculties   = $data['faculties'];
        $this->departments = $data['departments'];
        $this->activities  = $data['activities'];
        $this->days        = $data['days'];
        $this->day_period_map = $data['day_period_map'];
        $this->time_constraints = $data['time_constraints'] ?? [];
        $this->space_constraints = $data['space_constraints'] ?? [];
        $this->student_groups = $data['student_groups'] ?? [];
    }

    public function generate() {
        $this->validateRequiredData();

        $population = $this->initializePopulation();
        $best_timetable = [];
        $best_fitness = null;

        for ($gen = 0; $gen < $this->max_generations; $gen++) {
            // Sort by fitness (descending)
            usort($population, function ($a, $b) {
                return $this->calculateFitness($b) <=> $this->calculateFitness($a);
            });

            $current_fitness = $this->calculateFitness($population[0]);
            if ($best_fitness === null || $current_fitness > $best_fitness) {
                $best_fitness = $current_fitness;
                $best_timetable = $population[0];
            }

            if ($this->hasNoConflicts($best_timetable)) {
                return $best_timetable;
            }

            // Selection (Keep top half)
            $population = array_slice($population, 0, ceil(count($population) / 2));
            $new_population = [$population[0]];

            while (count($new_population) < $this->population_size) {
                $p1 = $population[array_rand($population)];
                $p2 = $population[array_rand($population)];

                $child = $this->crossover($p1, $p2);
                $this->mutate($child);
                $this->repairTimetable($child);
                $new_population[] = $child;
            }
            $population = $new_population;
        }

        usort($population, function ($a, $b) {
            return $this->calculateFitness($b) <=> $this->calculateFitness($a);
        });

        if ($best_fitness === null || $this->calculateFitness($population[0]) > $best_fitness) {
            $best_timetable = $population[0];
        }

        $this->repairTimetable($best_timetable);
        return $best_timetable; // Return best checked result
    }

    private function initializePopulation() {
        $population = [];
        for ($i = 0; $i < $this->population_size; $i++) {
            $timetable = [];
            foreach ($this->subjects as $subject) {
                $timetable[] = $this->createConflictAwareEntry($subject, $timetable);
            }
            $population[] = $timetable;
        }
        return $population;
    }

    private function calculateFitness($timetable) {
        $penalty = 0;
        $teacher_slots = [];
        $room_slots = [];
        $class_slots = [];
        $class_subjects = [];

        foreach ($timetable as $entry) {
            if (empty($entry['teacher_id']) || empty($entry['room_id']) || empty($entry['day_hour_id']) || empty($entry['class'])) {
                $penalty += 1000;
                continue;
            }

            $tkey = $entry['teacher_id'] . "-" . $entry['day_hour_id'];
            $rkey = $entry['room_id'] . "-" . $entry['day_hour_id'];
            $ckey = $entry['class'] . "-" . $entry['day_hour_id'];
            $cskey = $entry['class'] . "-" . $entry['subject_id'];

            if (isset($teacher_slots[$tkey])) $penalty += 100;
            else $teacher_slots[$tkey] = true;

            if (isset($room_slots[$rkey])) $penalty += 100;
            else $room_slots[$rkey] = true;

            if (isset($class_slots[$ckey])) $penalty += 100;
            else $class_slots[$ckey] = true;

            if (isset($class_subjects[$cskey])) $penalty += 10;
            else $class_subjects[$cskey] = true;

            if ($this->violatesTimeConstraint($entry)) $penalty += 100;
            if (!$this->roomFitsEntry($entry)) $penalty += 50;
            if (!$this->teacherFitsSubject($entry['teacher_id'], $entry['subject_id'])) $penalty += 20;
        }
        return 1000 - $penalty;
    }

    public function analyzeConflicts($timetable) {
        $teacher_slots = [];
        $room_slots = [];
        $class_slots = [];
        $blocked_slots = 0;
        $room_mismatches = 0;
        $teacher_mismatches = 0;

        foreach ($timetable as $entry) {
            $slot = $entry['day_hour_id'];
            $teacherKey = $entry['teacher_id'] . '-' . $slot;
            $roomKey = $entry['room_id'] . '-' . $slot;
            $classKey = $entry['class'] . '-' . $slot;

            $teacher_slots[$teacherKey] = ($teacher_slots[$teacherKey] ?? 0) + 1;
            $room_slots[$roomKey] = ($room_slots[$roomKey] ?? 0) + 1;
            $class_slots[$classKey] = ($class_slots[$classKey] ?? 0) + 1;

            if ($this->violatesTimeConstraint($entry)) $blocked_slots++;
            if (!$this->roomFitsEntry($entry)) $room_mismatches++;
            if (!$this->teacherFitsSubject($entry['teacher_id'], $entry['subject_id'])) $teacher_mismatches++;
        }

        return [
            'teacher_clashes' => $this->countDuplicates($teacher_slots),
            'room_clashes' => $this->countDuplicates($room_slots),
            'class_clashes' => $this->countDuplicates($class_slots),
            'blocked_room_slots' => $blocked_slots,
            'room_mismatches' => $room_mismatches,
            'teacher_subject_mismatches' => $teacher_mismatches,
            'fitness' => $this->calculateFitness($timetable),
        ];
    }

    private function crossover($p1, $p2) {
        $child = [];
        foreach ($p1 as $i => $gene) {
            $child[] = (rand(0, 1) === 0) ? $gene : $p2[$i];
        }
        return $child;
    }

    private function mutate(&$timetable) {
        foreach ($timetable as $index => &$entry) {
            if (rand(0, 10) < 2) {
                $subject = ['id' => $entry['subject_id']];
                $other_entries = $timetable;
                unset($other_entries[$index]);
                $entry = $this->createConflictAwareEntry($subject, $other_entries);
            }
        }
        unset($entry);
    }

    private function validateRequiredData() {
        $required_sets = [
            'teachers' => $this->teachers,
            'subjects' => $this->subjects,
            'rooms' => $this->rooms,
            'classes' => $this->classes,
            'faculties' => $this->faculties,
            'departments' => $this->departments,
            'days' => $this->days,
            'day periods' => $this->getAllPeriods()
        ];

        foreach ($required_sets as $name => $rows) {
            if (empty($rows)) {
                throw new Exception("Cannot generate timetable because no active {$name} were found.");
            }
        }
    }

    private function createConflictAwareEntry($subject, $existing_entries) {
        $fallback = null;
        $fallback_penalty = null;

        for ($attempt = 0; $attempt < $this->placement_attempts; $attempt++) {
            $entry = $this->createRandomEntry($subject);
            $penalty = $this->countEntryConflicts($entry, $existing_entries);

            if ($penalty === 0) {
                return $entry;
            }

            if ($fallback_penalty === null || $penalty < $fallback_penalty) {
                $fallback = $entry;
                $fallback_penalty = $penalty;
            }
        }

        return $fallback ?: $this->createRandomEntry($subject);
    }

    private function createRandomEntry($subject) {
        $day = $this->days[array_rand($this->days)];
        $period = $this->day_period_map[$day][array_rand($this->day_period_map[$day])];
        $class_info = $this->classes[array_rand($this->classes)];
        $class_display = $class_info['class_name'] . " - " . $class_info['semester'] . " - " . $class_info['section'];
        $teacher = $this->pickTeacherForSubject($subject);
        $room = $this->pickRoomForPeriod($period, $class_display);
        $department = $this->pickDepartmentForSubject($subject);

        return [
            "teacher_id"    => $teacher['id'],
            "room_id"       => $room['id'],
            "day_hour_id"   => $period['id'],
            "subject_id"    => $subject['id'],
            "activity_id"   => !empty($this->activities) ? $this->activities[array_rand($this->activities)]['id'] : NULL,
            "class"         => $class_display,
            "faculty_id"    => $this->faculties[array_rand($this->faculties)]['id'],
            "department_id" => $department['id']
        ];
    }

    private function repairTimetable(&$timetable) {
        foreach ($timetable as $index => $entry) {
            $other_entries = $timetable;
            unset($other_entries[$index]);

            if ($this->countEntryConflicts($entry, $other_entries) > 0) {
                $subject = ['id' => $entry['subject_id']];
                $timetable[$index] = $this->createConflictAwareEntry($subject, $other_entries);
            }
        }
    }

    private function hasNoConflicts($timetable) {
        return $this->calculateFitness($timetable) === 1000;
    }

    private function countEntryConflicts($entry, $existing_entries) {
        $conflicts = 0;

        foreach ($existing_entries as $existing) {
            if ($entry['day_hour_id'] !== $existing['day_hour_id']) {
                if ($entry['class'] === $existing['class'] && $entry['subject_id'] === $existing['subject_id']) {
                    $conflicts++;
                }
                continue;
            }

            if ($entry['teacher_id'] === $existing['teacher_id']) {
                $conflicts += 100;
            }

            if ($entry['room_id'] === $existing['room_id']) {
                $conflicts += 100;
            }

            if ($entry['class'] === $existing['class']) {
                $conflicts += 100;
            }
        }

        return $conflicts;
    }

    private function getAllPeriods() {
        $periods = [];
        foreach ($this->day_period_map as $day_periods) {
            foreach ($day_periods as $period) {
                $periods[] = $period;
            }
        }

        return $periods;
    }

    private function countDuplicates($slots) {
        $duplicates = 0;
        foreach ($slots as $count) {
            if ($count > 1) {
                $duplicates += $count - 1;
            }
        }
        return $duplicates;
    }

    private function pickTeacherForSubject($subject) {
        $matches = [];
        foreach ($this->teachers as $teacher) {
            if ($this->teacherFitsSubject($teacher['id'], $subject['id'])) {
                $matches[] = $teacher;
            }
        }

        return !empty($matches) ? $matches[array_rand($matches)] : $this->teachers[array_rand($this->teachers)];
    }

    private function pickDepartmentForSubject($subject) {
        $subjectDept = strtolower((string) ($subject['department'] ?? ''));
        foreach ($this->departments as $department) {
            if ($subjectDept !== '' && strtolower((string) $department['name']) === $subjectDept) {
                return $department;
            }
        }

        return $this->departments[array_rand($this->departments)];
    }

    private function pickRoomForPeriod($period, $classDisplay) {
        $matches = [];
        foreach ($this->rooms as $room) {
            $entry = [
                'room_id' => $room['id'],
                'day_hour_id' => $period['id'],
                'class' => $classDisplay,
            ];

            if (!$this->violatesTimeConstraint($entry) && $this->roomFitsEntry($entry)) {
                $matches[] = $room;
            }
        }

        return !empty($matches) ? $matches[array_rand($matches)] : $this->rooms[array_rand($this->rooms)];
    }

    private function teacherFitsSubject($teacherId, $subjectId) {
        $teacher = $this->findById($this->teachers, $teacherId);
        $subject = $this->findById($this->subjects, $subjectId);

        if (!$teacher || !$subject) {
            return false;
        }

        $subjectDepartment = strtolower(trim((string) ($subject['department'] ?? '')));
        if ($subjectDepartment === '') {
            return true;
        }

        $teacherFields = [
            strtolower(trim((string) ($teacher['department'] ?? ''))),
            strtolower(trim((string) ($teacher['major'] ?? ''))),
            strtolower(trim((string) ($teacher['minor'] ?? ''))),
        ];

        return in_array($subjectDepartment, $teacherFields, true);
    }

    private function roomFitsEntry($entry) {
        $room = $this->findById($this->rooms, $entry['room_id'] ?? null);
        if (!$room) {
            return false;
        }

        $requiredCapacity = $this->classSize($entry['class'] ?? '');
        if ($requiredCapacity > 0 && (int) ($room['capacity'] ?? 0) < $requiredCapacity) {
            return false;
        }

        foreach ($this->space_constraints as $constraint) {
            if (strcasecmp((string) ($constraint['room'] ?? ''), (string) ($room['room_name'] ?? '')) !== 0) {
                continue;
            }

            if (!empty($constraint['capacity']) && (int) $room['capacity'] < (int) $constraint['capacity']) {
                return false;
            }

            if (!empty($constraint['room_type']) && strcasecmp((string) $room['room_type'], (string) $constraint['room_type']) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function violatesTimeConstraint($entry) {
        $room = $this->findById($this->rooms, $entry['room_id'] ?? null);
        $period = $this->findPeriod((int) ($entry['day_hour_id'] ?? 0));

        if (!$room || !$period) {
            return false;
        }

        foreach ($this->time_constraints as $constraint) {
            $sameRoom = strcasecmp((string) ($constraint['room'] ?? ''), (string) ($room['room_name'] ?? '')) === 0;
            $sameDay = strcasecmp((string) ($constraint['day'] ?? ''), (string) ($period['day'] ?? '')) === 0;
            $samePeriod = (int) ($constraint['period'] ?? 0) === (int) ($period['period_number'] ?? $period['id']);

            if ($sameRoom && $sameDay && $samePeriod) {
                return true;
            }
        }

        return false;
    }

    private function classSize($classDisplay) {
        foreach ($this->student_groups as $group) {
            $groupClass = (string) ($group['class'] ?? '');
            if ($groupClass === (string) $classDisplay || ($groupClass !== '' && strpos((string) $classDisplay, $groupClass . ' -') === 0)) {
                return (int) ($group['total_students'] ?? 0);
            }
        }

        return 0;
    }

    private function findPeriod($id) {
        foreach ($this->day_period_map as $day => $periods) {
            foreach ($periods as $period) {
                if ((int) $period['id'] === $id) {
                    $period['day'] = $day;
                    return $period;
                }
            }
        }

        return null;
    }

    private function findById($rows, $id) {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === (int) $id) {
                return $row;
            }
        }

        return null;
    }
}
