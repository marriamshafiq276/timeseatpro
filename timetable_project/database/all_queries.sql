/* =========================================================
   Timetable Project database setup script.
   Rebuilds the local MySQL database, creates all application tables,
   adds indexes, and seeds default/demo accounts for XAMPP development.
   ========================================================= */

/* =========================================================
   RESET DATABASE
   ========================================================= */
DROP DATABASE IF EXISTS timetable_db;
CREATE DATABASE timetable_db;
USE timetable_db;


/* =========================================================
   USERS TABLE (ADMIN + TEACHER + STUDENT)
   ========================================================= */
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    linked_id INT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default admin user
INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$U.rLzU/hql0sXtV4uQToe.2A7mryS95JwpXcAHDDpW1rSSXPVS2xy', 'admin');

-- Indexes
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_users_linked_role ON users(role, linked_id);


/* =========================================================
   INSTITUTION TABLE
   ========================================================= */
CREATE TABLE institution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_name VARCHAR(150),
    address TEXT,
    phone VARCHAR(30),
    email VARCHAR(100),
    website VARCHAR(100),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


/* =========================================================
   DAYS & HOURS TABLE
   ========================================================= */
CREATE TABLE days_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day VARCHAR(20),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    time_slots TEXT,
    class_type VARCHAR(20) DEFAULT 'Theory',
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_days_hours_slot (day, start_time, end_time, class_type)
);


/* =========================================================
   SUBJECTS TABLE
   ========================================================= */
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    credit_hours VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subjects_code (code)
);


/* =========================================================
   ACTIVITY TAGS TABLE
   ========================================================= */
CREATE TABLE activity_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100),
    description TEXT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_activity_tags_name (tag_name)
);


/* =========================================================
   TEACHERS TABLE
   ========================================================= */
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    father_name VARCHAR(100),
    cnic VARCHAR(20),
    email VARCHAR(100),
    phone VARCHAR(20),
    qualification VARCHAR(100),
    visiting_status ENUM('Permanent','Visiting') DEFAULT 'Permanent',
    designation VARCHAR(100),
    major VARCHAR(100),
    minor VARCHAR(100),
    department VARCHAR(100),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_teachers_cnic (cnic),
    UNIQUE KEY uniq_teachers_email (email)
);


/* =========================================================
   STUDENTS / BATCHES TABLE
   ========================================================= */
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150) NOT NULL,
    registration_no VARCHAR(50),
    batch VARCHAR(50),
    class VARCHAR(50),
    total_students INT,
    groups INT,
    students_per_group INT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_students_registration_no (registration_no)
);


/* =========================================================
   ACTIVITIES TABLE
   ========================================================= */
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_activities_name (name)
);


/* =========================================================
   SUBACTIVITIES TABLE
   ========================================================= */
CREATE TABLE subactivities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT,
    name VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subactivities_name_per_activity (activity_id, name),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);


/* =========================================================
   BUILDINGS TABLE
   ========================================================= */
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    location VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_buildings_name (name)
);


/* =========================================================
   ROOMS TABLE
   ========================================================= */
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT DEFAULT NULL,
    room_name VARCHAR(100),
    capacity INT,
    room_type VARCHAR(50),
    floor ENUM('Ground Floor','1st Floor','2nd Floor') DEFAULT 'Ground Floor',
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rooms_building_name_floor (building_id, room_name, floor),
    INDEX idx_rooms_building_id (building_id),
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE SET NULL
);


/* =========================================================
   TIME CONSTRAINTS TABLE
   ========================================================= */
CREATE TABLE time_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room VARCHAR(100),
    day VARCHAR(50),
    period INT,
    note TEXT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY uniq_time_constraints_room_day_period (room, day, period)
);


/* =========================================================
   SPACE CONSTRAINTS TABLE
   ========================================================= */
CREATE TABLE space_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room VARCHAR(100),
    capacity INT,
    room_type VARCHAR(50),
    note TEXT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY uniq_space_constraints_room_type (room, room_type)
);


/* =========================================================
   FACULTIES TABLE
   ========================================================= */
CREATE TABLE faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_faculties_name (name)
);


/* =========================================================
   CLASSES TABLE
   ========================================================= */
CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT NOT NULL,
  class_name VARCHAR(255),
  semester VARCHAR(50),
  section VARCHAR(50),
  status VARCHAR(20),
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE KEY uniq_classes_combo (faculty_id, class_name, semester, section),
  FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);


/* =========================================================
   DEPARTMENTS TABLE
   ========================================================= */
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_departments_name_per_faculty (faculty_id, name),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);


/* =========================================================
   TIMETABLE TABLE
   ========================================================= */
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    room_id INT NOT NULL,
    room_display VARCHAR(100) DEFAULT NULL,
    day_hour_id INT NOT NULL,
    subject_id INT NOT NULL,
    subject_display VARCHAR(150) DEFAULT NULL,
    activity_id INT DEFAULT NULL,
    class_display VARCHAR(150) DEFAULT NULL,
    class VARCHAR(50) DEFAULT NULL,
    faculty_id INT NOT NULL,
    department_id INT NOT NULL,
    version_id INT DEFAULT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (day_hour_id) REFERENCES days_hours(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);


/* =========================================================
   TIMETABLE VERSIONS TABLE
   ========================================================= */
CREATE TABLE timetable_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_name VARCHAR(100) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by VARCHAR(100) DEFAULT NULL,
    total_entries INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active'
);


/* =========================================================
   SEATING VERSIONS TABLE
   ========================================================= */
CREATE TABLE seating_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_name VARCHAR(255),
    generated_by VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_entries INT DEFAULT 0
);


/* =========================================================
   SEAT ALLOCATION TABLE
   ========================================================= */
CREATE TABLE seat_allocation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    teacher_id INT DEFAULT NULL,
    subject_id INT DEFAULT NULL,
    day_hour_id INT DEFAULT NULL,
    seat_no INT NOT NULL,
    version_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (day_hour_id) REFERENCES days_hours(id) ON DELETE SET NULL,
    FOREIGN KEY (version_id) REFERENCES seating_versions(id) ON DELETE SET NULL
);


/* =========================================================
   CONTACTS / SUPPORT MESSAGES TABLE
   ========================================================= */
CREATE TABLE contacts (
   id INT AUTO_INCREMENT PRIMARY KEY,
   name VARCHAR(150) DEFAULT NULL,
   email VARCHAR(150) DEFAULT NULL,
   message TEXT,
   ip_address VARCHAR(45) DEFAULT NULL,
   user_agent VARCHAR(255) DEFAULT NULL,
   status VARCHAR(20) DEFAULT 'new',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_contacts_email_status ON contacts(email, status);

