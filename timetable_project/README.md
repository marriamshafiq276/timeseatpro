# Timetable Project

A PHP/MySQL web application for managing university timetable data, generating timetables, creating seating plans, and exporting schedules to Excel, PDF, or print.

## Project Notes
- The project is designed for local XAMPP use from `C:\xampp\htdocs\timetable_project`.
- PHP files include short file-level comments that describe each page, helper, generator, or data-management screen.
- `PROJECT_INDEX.md` is the companion map for quickly finding project files and understanding the main flow.
- `database/all_queries.sql` is a reset/setup script; importing it will recreate `timetable_db`.

## Features
- Public landing pages: Home, About, Features, Support, and Contact.
- Public pages use clean filenames: `index.php`, `about.php`, `features.php`, `support.php`, and `contact.php`.
- The login page includes public navigation links for Home, About, Features, Support, and Contact.
- Secure login for admin, teacher, and student users.
- User registration for teacher and student accounts.
- Role-based views: admin dashboard, teacher timetable, and student timetable.
- Authenticated navigation omits the About Us page and uses green logout buttons with confirmation alerts.
- Master-data management for teachers, students, subjects, rooms, faculties, departments, classes, activities, and constraints.
- Building-aware room management so timetable and seating views can show where each room is located.
- Shared CSV upload on CRUD data pages using each page's configured field list.
- Timetable generation with version history and conflict summaries.
- Seating plan generation with version history and capacity-aware room assignment.
- Admin timetable and seating-plan views use matching edit/delete modal workflows for generated records.
- Filtered timetable display with Excel/PDF/print export of current search results.
- Support page with working hours and department contact emails.
- CSRF helpers, safe-output helpers, and shared duplicate checks for safer forms.
- Admin timetable views hide internal database IDs from the displayed table and exports.

## Requirements
- XAMPP or similar PHP/MySQL environment.
- PHP with `mysqli` and `mbstring` extensions enabled.
- Optional: PHP `zip` extension for native `.xlsx` downloads. Without it, Excel exports automatically download an Excel-compatible `.xls` file.
- MySQL or MariaDB.
- Browser with internet access for CDN assets such as Tailwind, jQuery, and DataTables.

## Setup
1. Place the project folder in XAMPP's document root:

   ```text
   C:\xampp\htdocs\timetable_project
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Import the database:

   ```text
   database/all_queries.sql
   ```

   This script creates the `timetable_db` database, all tables, and the default admin user.

4. Review the database connection in:

   ```text
   includes/config.php
   ```

   Default XAMPP settings are:

   ```text
   host: localhost
   user: root
   password:
   database: timetable_db
   ```

5. Open the application in your browser:

   ```text
   http://localhost/timetable_project/index.php
   ```

   You can also go directly to `http://localhost/timetable_project/login.php` to sign in.

## Default Admin
```text
Username: admin
Password: admin123
```

## Teacher And Student Accounts
Teacher and student accounts are created from `register.php`, then approved and linked by the admin from `user_management.php`.

## Common Workflow
1. Open `login.php` and log in as admin.
2. Add or update master data under the `data/` section.
3. Add buildings first, then assign rooms to buildings from `data/rooms.php`.
4. Generate a timetable from `generate_timetable.php`.
5. Review and edit generated timetables in `view_timetable.php`.
6. Use `timetable_display.php` for filtered timetable views and exports, including building-based display.
7. Generate seating plans with `generate_seating.php` and review them in `view_seating_plan.php`.

## Important Files
- `login.php` / `authenticate.php`: Role-aware sign-in and session setup.
- `index.php`: Public home page.
- `dashboard.php`: Admin dashboard with record counts and shortcuts.
- `includes/nav.php`: Authenticated navigation without About Us and with logout confirmation.
- `classes/TimetableGA.php`: Timetable generation engine.
- `classes/SeatingGenerator.php`: Seating-plan generation engine.
- `includes/security.php`: CSRF, role, redirect, JSON, and HTML escaping helpers.
- `includes/export_helpers.php`: Shared Excel, PDF, and print export builders.
- `includes/duplicate_helpers.php`: Duplicate-record checks for admin forms.
- `includes/schema_helpers.php`: Lightweight local schema upgrades, including the room-to-building column for existing databases.
- `data/*.php`: Admin CRUD screens for timetable master data.
- `database/all_queries.sql`: Full database schema and seed data.
- `PROJECT_INDEX.md`: Detailed project map and maintenance notes.

## Maintenance Notes
- Keep `includes/config.php` as the main database connection source.
- Keep all protected admin pages behind the existing session/role checks.
- Avoid whitespace before `<?php` in PHP files that can produce downloads or redirects.
- After editing PHP, run a syntax check with `C:\xampp\php\php.exe -l path\to\file.php`.
- Teachers and students access their own timetable screens after approval.
- CSV upload is available on shared CRUD data pages. Use the field names shown on each page as CSV headers, or provide values in the same order as the form fields.
- Existing local databases are upgraded in place with `rooms.building_id` when building-aware pages are opened.

## Support Contacts
- Admissions Office: `admissionsupport@uaf.edu.pk`
- Controller of Examinations: `controller.examinations@uaf.edu.pk`
- Student Affairs: `stuaf@uaf.edu.pk`
- IT Resource Center: `director.it@uaf.edu.pk`
- Working Hours: Monday-Friday 8:00am - 16:00pm
- Phone: `+92419200161-70`

## Export System
- Excel downloads use native `.xlsx` files when PHP `ZipArchive` is available.
- If the PHP `zip` extension is disabled, Excel downloads automatically fall back to an Excel-compatible `.xls` file instead of failing.
- PDF downloads are real PDF files.
- Print opens a browser-printable table.
- `timetable_display.php` exports only the currently filtered timetable result.
- Timetable and seating exports include building information when rooms are linked to buildings.

## Important Files
- `generate_timetable.php`: Timetable generation entry point.
- `classes/TimetableGA.php`: Timetable generation engine.
- `timetable_display.php`: Filtered timetable display and export page.
- `view_timetable.php`: Admin timetable table with full-field edit/delete and conflict summary.
- `generate_seating.php`: Seating generation entry point.
- `classes/SeatingGenerator.php`: Seating allocation engine.
- `view_seating_plan.php`: Seating plan full-field view/edit/export page.
- `includes/export_helpers.php`: Shared export logic, including the Excel fallback for XAMPP installations without `ZipArchive`.
- `database/all_queries.sql`: Full database schema.
- Removed unused legacy files: `db_connect.php` and `upload_handler.php`.

## Notes
- Keep include files used before exports free of accidental whitespace before `<?php`.
- If Excel downloads as `.xls`, enable the PHP `zip` extension in XAMPP to get native `.xlsx` files.
- Passwords are stored with `password_hash()` and legacy MD5 records are migrated on login.
- This project is optimized for local XAMPP development, not production deployment.
- The project is functionally built for local/demo use, but it should not be considered 100% complete for production until a full browser test, database import test, role-permission audit, security review, and deployment hardening pass are completed.
