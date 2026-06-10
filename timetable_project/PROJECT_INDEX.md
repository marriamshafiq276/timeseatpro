# Project Index - Timetable Project

## Overview
- Stack: PHP, MySQL/MariaDB, Tailwind CSS, jQuery, DataTables.
- Runtime target: XAMPP on Windows, served from `htdocs/timetable_project`.
- Purpose: University timetable and seating plan management with role-based access, public navigation, and export capabilities.
- Authentication: Session-based login with `admin`, `teacher`, and `student` roles.
- Buildings: Rooms can be linked to buildings; generated timetable and seating views show building context through the selected room.
- Documentation: Each text/source file now has a brief purpose comment or section note; `assets/logo.png` is the only binary file and is not commentable.

## Directory Map
- `assets/`
  - `logo.png`: Project branding image.
- `classes/`
  - `TimetableGA.php`: Timetable generation engine.
  - `SeatingGenerator.php`: Seating plan generation engine.
- `data/`
  - Master-data CRUD pages for activities, buildings, classes, days/hours, departments, faculties, institution, rooms, students, subjects, teachers, and constraints.
- `database/`
  - `all_queries.sql`: Database reset script, schema, and default admin seed.
- `includes/`
  - `auth.php`: Session guard for authenticated pages.
  - `auth_check.php`: Role/session guard used by protected pages.
  - `config.php`: Main MySQL connection file.
  - `crud_page.php`: Shared CRUD renderer, AJAX handlers, and CSV upload for data pages.
  - `duplicate_helpers.php`: Shared duplicate detection and AJAX duplicate responses.
  - `export_helpers.php`: Shared Excel, PDF, and print export helpers.
  - `footer.php`: Common authenticated page footer.
  - `header.php`: Common authenticated HTML head/assets and top layout.
  - `nav.php`: Main authenticated navigation without About Us and with a green logout confirmation button.
  - `public_header.php`: Public landing page header and navigation.
  - `public_footer.php`: Public landing page footer and contact links.
  - `schema_helpers.php`: Lightweight schema upgrades for existing local databases, including `rooms.building_id`.
  - `security.php`: CSRF, role, JSON error, redirect, and safe HTML helpers.
  - `user_helpers.php`: User/account helper functions.

## Root Pages
- `index.php`: Public home page.
- `about.php`: Public about page.
- `support.php`: Public support page with working hours and contact emails.
- `contact.php`: Public contact page.
- `authenticate.php`: Login POST handler and session setup.
- `generate_seating.php`: Generates seating plans.
- `generate_timetable.php`: Generates timetable entries and versions.
- `guide.php`: In-app quick guide with demo credentials and first-run checklist.
- `dashboard.php`: Authenticated dashboard.
- `login.php`: Login page with public navigation links for Home, About, Features, Support, and Contact.
- `logout.php`: Logout page.
- `logout_action.php`: Destroys session and redirects to login.
- `register.php`: User registration/account creation page.
- `seating_history.php`: Seating version history.
- `student_timetable.php`: Student role timetable view and exports.
- `teacher_timetable.php`: Teacher role timetable view and exports.
- `timetable_display.php`: Filtered timetable display by dropdown selections with Excel/PDF/print export.
- `timetable_history.php`: Timetable version history.
- `user_management.php`: Admin user management.
- `view_seating_plan.php`: Admin seating plan view, full-field edit/delete, and exports.
- `view_timetable.php`: Admin timetable view, full-field edit/delete, conflict summary, and exports without displaying internal IDs.

## Main Application Flow
1. Public visitors browse `index.php`, `about.php`, `features.php`, `support.php`, and `contact.php`.
2. Users register from `register.php` or log in from `login.php`.
3. Login submits to `authenticate.php`.
4. Admin, teacher, or student users are redirected to role-appropriate screens with logout confirmation available from the header/navigation.
5. Admin manages master data in `data/`.
6. Admin adds buildings and assigns rooms to buildings from `data/buildings.php` and `data/rooms.php`.
7. Admin generates a timetable with `generate_timetable.php` and reviews it in `view_timetable.php`.
8. Timetable queries and exports are available in `timetable_display.php`, including building-based display/export.
9. Admin generates seating plans with `generate_seating.php` and reviews them in `view_seating_plan.php`.
10. Teachers and students access their own schedule views in `teacher_timetable.php` and `student_timetable.php`.

## Export System
- Shared helper: `includes/export_helpers.php`.
- Excel exports are generated as native `.xlsx` files when PHP `ZipArchive` is available.
- If the PHP `zip` extension is disabled, Excel exports fall back to an Excel-compatible `.xls` file so downloads still work on typical XAMPP installs.
- PDF exports are generated as real PDF files.
- Print exports render a browser-printable HTML page.
- `timetable_display.php` exports only the current filtered result.
- Timetable, teacher, student, and seating exports include building data when a room has a linked building.

## CSV Import
- Shared helper: `includes/crud_page.php`.
- CRUD data pages show a CSV upload panel above the add form.
- CSV files can use the field names shown on the page as headers, or values can be supplied in form-field order.
- Duplicate checks are reused during upload where a page defines a duplicate rule.

## Contact & Support
- Working Hours: Monday-Friday 8:00am - 16:00pm
- Phone: `+92419200161-70`
- Admissions Office: `admissionsupport@uaf.edu.pk`
- Controller of Examinations: `controller.examinations@uaf.edu.pk`
- Student Affairs: `stuaf@uaf.edu.pk`
- IT Resource Center: `director.it@uaf.edu.pk`

## Database Tables
- `users`
- `institution`
- `days_hours`
- `subjects`
- `activity_tags`
- `teachers`
- `students`
- `activities`
- `subactivities`
- `buildings`
- `rooms`
- `time_constraints`
- `space_constraints`
- `classes`
- `faculties`
- `departments`
- `timetable`
- `timetable_versions`
- `seating_versions`
- `seat_allocation`

## Default Login
- Username: `admin`
- Password: `admin123`
- Role: `admin`

## Teacher And Student Logins
- Teacher and student users register from `register.php`.
- Admin approves and links those accounts from `user_management.php`.

## Maintenance Notes
- Keep include files free of accidental whitespace before `<?php` to avoid corrupting binary exports.
- Main DB connection is configured in `includes/config.php`.
- Existing local databases are upgraded with `rooms.building_id` by `includes/schema_helpers.php` when building-aware pages are opened.
- Legacy MD5 password records are migrated on successful login.
- Removed unused legacy files: `db_connect.php` and `upload_handler.php`.
- Keep file-level comments concise and update them when a file's responsibility changes.
- Re-run `C:\xampp\php\php.exe -l <file>` after PHP edits to catch syntax errors early.
- Enable PHP's `zip` extension if native `.xlsx` output is required; otherwise the built-in `.xls` fallback is used.
- Current status: feature-complete for local/demo use, but not proven 100% complete for production without full browser testing, database import testing, role-permission review, security review, and deployment hardening.
