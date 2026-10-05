"# Attendance Management System

A PHP and MySQL attendance application for teachers and students. It uses plain PHP, PDO, HTML, CSS, and JavaScript; it does not require Composer, PHPMailer, or image assets.

## Features

- Role-based sign-in using an account ID or username and password.
- Account profile and secure self-service password changes.
- Admin account CRUD for administrators, teachers, and students.
- The admin account directory refreshes from MySQL every 15 seconds, so student records inserted directly into the database appear without a manual page reload.
- Admin attendance overview and status correction across all classes.
- Admin view of teacher attendance records.
- Teachers see the XII RPL 2 student roster and daily submitted attendance.
- Teachers can submit their own daily attendance.
- Students see their own profile and attendance history and can submit one attendance record per day.
- Teachers and students manually submit their own attendance from **00:00 to 06:45 WIB**. Attendance is never generated automatically when someone does not submit.
- Passwords are hashed, database access uses prepared statements, and state-changing forms use CSRF tokens.

Database credentials are read by PHP from server environment variables (when set) or `.env`; they are never sent to the browser. A browser-facing API key would not secure the database because users can inspect browser code and network requests. `.env` is a protected configuration file, not an encrypted vault: use a dedicated least-privilege MySQL account, restrict filesystem access, and use your hosting provider's secret manager or server environment variables for production secrets. Apache serves a generic error message for uncaught exceptions and writes a minimal diagnostic location to the server error log instead of showing a stack trace.

## Requirements

- PHP 8.1+ with PDO MySQL and `mbstring` enabled.
- MySQL 8+ or a compatible MariaDB release.
- XAMPP is suitable for local development.

## Setup with XAMPP

1. Place this project in `C:\xampp\htdocs\Attendance-Management-System`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import [`database/schema.sql`](database/schema.sql) using phpMyAdmin, or run:

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\Attendance-Management-System\database\schema.sql"
   ```

4. Copy `.env.example` to `.env` and set the MySQL host, database, username, and password. The local `.env` file is excluded from Git and blocked from direct Apache requests. The included `.env` starter matches a typical local XAMPP install (`127.0.0.1`, database `ams_db`, user `root`, and a blank password); replace these values with a dedicated MySQL account and password outside local development.
5. Create the first administrator from a PowerShell prompt:

   ```powershell
   Set-Location "C:\xampp\htdocs\Attendance-Management-System"
   C:\xampp\php\php.exe scripts\create-admin.php
   ```

   The script securely hashes the password and prints the new account ID. Use that ID or the username to sign in.
6. Visit `http://localhost/Attendance-Management-System/`.
7. Sign in as the administrator and create teacher and student accounts in People & accounts. Optional fictional demo accounts are in a commented block near the end of `database/schema.sql`.

## Customize branding and demo data

- Change `APP_NAME` in `config.php` to update the browser title, top navigation, and sign-in heading.
- Replace `img/icon.svg` with your own icon, or set `APP_ICON_PATH` in `config.php` to another image path. The image is used for the browser tab, top navigation, and sign-in page.
- Change page-specific headings in the matching PHP page; each page passes its title to `page_header()`.
- The optional fictional teacher/student SQL values at the end of `database/schema.sql` are inside a block comment. To create those demo accounts, edit their usernames, names, class, subject, and password hash, then remove the opening `/*` and closing `*/` markers before importing. Otherwise, import the schema as-is to create empty tables and add your own accounts through the admin page.
- The example password hash in that block is for `123456789`. For a different password, generate a hash with PHP `password_hash('your-password', PASSWORD_DEFAULT)` rather than storing a plain-text password.

## Attendance behavior

The configured timezone is `Asia/Jakarta`; change `APP_TIMEZONE` in `config.php` if needed. Teachers and students submit Hadir, Izin, or Sakit through their own forms from midnight to 06:45 WIB. Duplicate submissions are prevented by database unique keys. Attendance is not generated for anyone who did not submit. On the teacher attendance page, select the Present, Absent, or Awaiting overview card to filter the roster below.

Admins can view attendance records and manage account names, usernames, roles, class assignments, subjects, and active status. Editing a password is optional; new passwords must be at least 10 characters. Administrators cannot delete or deactivate their own account, and the last active administrator cannot be removed or deactivated.

## Database

`ams_db` contains:

- `users`: account ID, username, password hash, role, name, class/subject, and active state.
- `student_attendance`: one status per student per date, with timestamps and referential cleanup when a student is deleted.
- `teacher_attendance`: one daily status per teacher, with referential cleanup when a teacher is deleted."

Importing `database/schema.sql` creates the database tables only. It does not create users or reset any existing passwords. No administrator is seeded; create one with `scripts/create-admin.php`. The optional commented seed block contains fictional example names that can be customized before use.
