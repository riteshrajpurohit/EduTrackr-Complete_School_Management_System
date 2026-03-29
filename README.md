# EduTrackr - Complete School Management System

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](#)
[![Role Based Access](https://img.shields.io/badge/Auth-RBAC-success)](#)
[![Google OAuth](https://img.shields.io/badge/Login-Google%20OAuth-4285F4?logo=google&logoColor=white)](#)

EduTrackr is a role-based School Management System built with core PHP + MySQL.
It covers daily school operations from one dashboard: academics, attendance, exams, announcements, timetable, and a full fee payment workflow with receipts.

## Table of Contents

- [System Screenshots & Gallery](#system-screenshots--gallery)
- [What This Project Includes](#what-this-project-includes)
- [User Roles and Access](#user-roles-and-access)
- [Feature Modules](#feature-modules)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Database Setup (Important)](#database-setup-important)
- [Local Installation](#local-installation)
- [Google OAuth Configuration](#google-oauth-configuration)
- [Demo Credentials](#demo-credentials)
- [How to Use (Quick Flow)](#how-to-use-quick-flow)
- [Security Highlights](#security-highlights)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

## System Screenshots & Gallery

Get a visual tour of EduTrackr features:

### 1. 🔐 Authentication - Login Portal

![Login Screen](images/01-login-screen.png)
_Secure login portal with role selection (Admin, Teacher, Student) and Google OAuth integration for quick access._

---

### 2. 👨‍💼 Super Admin - Fees Management Dashboard

![Fees Management](images/02-fees-management.png)
_Complete fee module control center with quick access to manage fee groups, installments, extra fees, student ledger, payment records, and comprehensive reports._

---

### 3. 📊 Super Admin - Principal Console

![Principal Console](images/03-principal-console.png)
_Executive dashboard showing key metrics: total classes, subjects, teachers, and students with quick action buttons for class and subject management._

---

### 4. 📅 Student - Attendance Tracking

![Student Attendance](images/04-student-attendance.png)
_Personal attendance dashboard for students to view attendance records, percentage, and historical attendance data with visual charts._

---

### 5. 🎓 Student - Welcome Dashboard

![Student Dashboard](images/05-student-dashboard.png)
_Student home screen displaying class information, marks overview, quick action links to view subjects, marks, and profile settings._

---

## What This Project Includes

- Multi-role authentication system (Super Admin, Teacher, Student)
- Role-based dashboard and page protection
- Class, section, and academic session management
- Subject assignment and marks management
- Attendance tracking
- Exam and exam-result management
- Announcements and timetable publishing
- Advanced fee module:
  - Fee groups
  - Installments per class
  - Extra fees
  - Student ledger
  - Payment records
  - Transaction IDs and receipt numbers
  - Downloadable receipts

## User Roles and Access

### 1) Super Admin

Primary panel: `superadmin/`

- Manage users and approve pending accounts
- Manage classes, subjects, and subject assignments
- Manage exam setup and timetables
- Post announcements
- Manage complete fees workflow under `superadmin/fees/`
- View reports and student-level fee ledger

### 2) Teacher

Primary panel: `teacher/`

- View assigned classes and subjects
- Mark attendance
- Enter and view marks/exam marks
- View student list
- View fee status (read-only)

### 3) Student

Primary panel: `student/`

- View dashboard, profile, subjects, marks, attendance, and exam results
- View announcements and timetable
- View fee breakdown and payment history
- Make fee payment requests and download receipts

## Feature Modules

### Authentication and Authorization

- Email/password login and registration
- Google OAuth sign-in
- Session-based authentication
- RBAC using `role_id` + fallback role string support

### Academic Management

- Classes, sections, and session mapping
- Subject setup and assignment to teachers/classes
- Student marks and grade calculations

### Attendance

- Attendance status support: present, absent, late, excused
- Class-wise attendance workflows

### Exams

- Exam creation and scheduling
- Exam marks entry and student result views

### Announcements and Timetable

- Targeted announcements by role/class
- Timetable file upload and student/teacher visibility

### Fees and Payments (Complete Workflow)

- Fee groups: tuition, library, transport, etc.
- Installment plans per class
- Extra fees for class-level or student-level assignment
- Auto-assignment of applicable fees to students
- Payment statuses: pending, partial, paid
- Payment modes: cash, UPI, card, net banking
- Auto-generated `transaction_id` and `receipt_number`
- Receipt page and download flow

For full fee-module notes, see:

- `FEES_SYSTEM_README.md`
- `FEES_PAYMENT_SYSTEM_COMPLETE.md`

## Tech Stack

- Backend: Core PHP
- Database: MySQL (InnoDB, utf8mb4)
- Frontend: HTML, Tailwind-style utility classes, vanilla JS
- Auth: Session auth + Google OAuth

## Project Structure

```text
SCHOOL MGMT/
|- index.php
|- login.php
|- register.php
|- logout.php
|- error.php
|- database.sql
|- database_fees_migration.sql
|- database_fees_payment_upgrade.sql
|- FEES_SYSTEM_README.md
|- FEES_PAYMENT_SYSTEM_COMPLETE.md
|- auth/
|  |- google.php
|  |- google_callback.php
|- includes/
|  |- db.php
|  |- functions.php
|  |- payment_processor.php
|  |- google_config.php
|  |- header.php
|  |- footer.php
|  |- sidebar.php
|- superadmin/
|  |- dashboard.php
|  |- manage_users.php
|  |- pending_approvals.php
|  |- classes.php
|  |- subjects.php
|  |- subject_assignments.php
|  |- exams.php
|  |- timetables.php
|  |- announcements.php
|  |- fees/
|     |- index.php
|     |- manage_groups.php
|     |- manage_installments.php
|     |- manage_extra_fees.php
|     |- student_ledger.php
|     |- payments.php
|     |- reports.php
|- teacher/
|  |- dashboard.php
|  |- my_classes.php
|  |- my_subjects.php
|  |- students_list.php
|  |- attendance.php
|  |- marks.php
|  |- exam_marks.php
|  |- fees_view.php
|- student/
|  |- dashboard.php
|  |- profile.php
|  |- classes.php
|  |- subjects.php
|  |- marks.php
|  |- attendance.php
|  |- exam_results.php
|  |- announcements.php
|  |- timetable.php
|  |- fees.php
|  |- process_payment.php
|  |- receipt.php
|  |- download_receipt.php
```

## Database Setup (Important)

Run SQL files in this order:

1. `database.sql`
2. `database_fees_migration.sql`
3. `database_fees_payment_upgrade.sql`

This sequence creates base tables first, then new fee-system tables, then payment-mode and receipt upgrades.

## Local Installation

### 1. Put project in web root

Example for MAMP:

```bash
cd /Applications/MAMP/htdocs
# Place folder: SCHOOL MGMT
```

### 2. Create and import database

Using phpMyAdmin or MySQL CLI, import SQL files in the order listed above.

### 3. Configure DB credentials

Update `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'edutrackr');
```

### 4. Start server

- Start Apache + MySQL from MAMP/XAMPP/WAMP
- Open project URL, for example:
  - `http://localhost/SCHOOL%20MGMT/`

## Google OAuth Configuration

If you want Google login to work, update Google Cloud OAuth redirect URI:

- `http://localhost/SCHOOL%20MGMT/auth/google_callback.php`

For production:

- `https://yourdomain.com/auth/google_callback.php`

Then verify values in `includes/google_config.php`.

## Demo Credentials

After running `database.sql` sample seed:

### Super Admin

- Email: `admin@edutrackr.com`
- Password: `password`

### Teacher

- Email: `teacher@edutrackr.com`
- Password: `password`

### Student

- Email: `student@edutrackr.com`
- Password: `password`

Important: Change all default passwords before production use.

## How to Use (Quick Flow)

1. Login as Super Admin.
2. Approve users (if pending) and complete class/session/section setup.
3. Create subjects and assign them to teachers/classes.
4. Configure fee groups + installments + extra fees.
5. Student logs in to view academics and fee status.
6. Payments are recorded and receipts are generated.

## Security Highlights

- Prepared statements in database operations
- Password hashing (bcrypt)
- Session-based access checks
- Role guards for route/page-level authorization
- Input sanitization helpers in `includes/functions.php`

## Troubleshooting

- If login fails, verify DB credentials and imported tables.
- If fees pages fail, ensure both fee migration files are imported.
- If Google login fails, verify redirect URI and OAuth client config.
- If role access breaks, verify `users.role_id` values:
  - `1 = super_admin`
  - `2 = teacher`
  - `3 = student`

## Contributing

1. Fork the repo
2. Create a feature branch
3. Commit your changes
4. Open a pull request

## License

This project is intended for educational and internal school-management use.
