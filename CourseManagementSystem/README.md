# CourseHub — Course Management System

A PHP + MySQL web application built for the **Web Technologies** final project
(Part 2). Three roles — **Admin**, **Teacher**, **Student** — share one
platform for course enrollment, course materials, grading and a two-step
course-drop approval flow.

---

## 1. Demo Accounts

| Role    | Email                    | Password  |
|---------|--------------------------|-----------|
| Admin   | admin@coursehub.com      | admin123  |
| Teacher | rahim@coursehub.com      | 123456    |
| Teacher | farah@coursehub.com      | 123456    |
| Teacher | nasima@coursehub.com     | 123456    |
| Student | tasfir@coursehub.com     | 123456    |
| Student | emtiyaz@coursehub.com    | 123456    |
| Student | shariar@coursehub.com    | 123456    |
| Student | mahin@coursehub.com      | 123456    |
| Student | nusrat@coursehub.com     | 123456    |
| Student | ayesha@coursehub.com     | 123456 *(suspended — for testing the suspend feature)* |

---

## 2. Feature Summary

**Student**
- Browse and enroll in courses, with live seat-count and full/enrolled state
- View course materials and announcements for enrolled courses only
- View grade components and running average per course, and across all courses
- Submit a drop request (with reason), track its approval status, cancel while pending
- Edit profile, change password, delete own account

**Teacher**
- See only the courses assigned by the admin
- Upload / remove course materials, post announcements
- View the enrolled student roster per course
- Enter or update grade components (Assignment / Quiz / Midterm / Final / Project / Attendance) per student
- Approve or reject student drop requests for their own courses (first approval step)

**Admin**
- Dashboard with system-wide stats and recent activity feed
- Create / suspend / activate / delete any user (student, teacher, or another admin)
- Create / edit / delete courses, assign or reassign a teacher, open/close a course
- Give the final approval or rejection on drop requests already approved by a teacher
- Reports page: enrollment-per-course bar chart, user and drop-request breakdowns

**Shared**
- Register / Login / Forgot-password / Logout
- "Remember my email" cookie on the login page
- A notification bell with unread-count badge, tied to real events (new material, new grade, drop-request updates, new enrollment)
- An `activities` log recorded quietly in the background for the admin's activity feed

---

## 3. What Was Reused From Part 1, And What Changed

This project follows the same **PHP + MySQLi + Sessions + Cookies + JSON/AJAX**
conventions as the previous KaajKoro project you shared, on purpose, so the
code style is consistent with what's already been graded:

- Same file layout: one `config.php`, one `functions.php`, `header.php` /
  `footer.php` includes, a `check_*.php` guard included at the top of every
  protected page.
- Same security habits: **prepared statements everywhere**, `password_hash()`
  / `password_verify()`, `htmlspecialchars()` on every echoed value,
  `intval()` / `in_array()` whitelisting before anything touches SQL,
  ownership checks before any update or delete (e.g. a teacher can only grade
  students in *their own* course).
- Same JSON/AJAX pattern: `check_email.php` returns JSON exactly like the
  original, checked live while a user types their email on the register page.
- Same cookie usage: `remember_email` on the login page, exactly like before.

**One deliberate change:** KaajKoro used two separate tables (`users` and
`admins`) because it only had two roles. This project has **three** roles, so
a single `users` table with a `role` ENUM column was used instead — it avoids
duplicating the login/profile logic three times and is the more standard way
to model multiple roles in an ER diagram. `check_admin.php`, `check_teacher.php`
and `check_student.php` each build on `check_login.php` to guard pages by role,
the same layered idea as the original `check_login.php` / `check_admin.php` pair.

**Do not** mix in file uploads, REST APIs, or a frontend framework (React,
Vue, etc.) — none of that fits a two-table-per-week, session-based PHP course
project, and it would look inconsistent with the rest of the code style.

---

## 4. Database Design (9 tables)

| Table            | Purpose |
|------------------|---------|
| `users`          | Every account — admin, teacher, student — with a `role` column |
| `courses`        | One row per course, `teacher_id` nullable until an admin assigns one |
| `enrollments`    | The M:N link between students and courses, with a status (enrolled/dropped) |
| `materials`      | Course materials, `course_id` → `courses` |
| `grades`         | Weak entity depending on both a student and a course; unique per component |
| `drop_requests`  | The two-step approval flow: `teacher_status` then `admin_status` |
| `announcements`  | Global (`course_id` NULL) or per-course announcements |
| `notifications`  | Per-user notification feed, read/unread |
| `activities`     | Background activity log shown on the admin dashboard |

All foreign keys use `ON DELETE CASCADE` (or `SET NULL` for `courses.teacher_id`)
so deleting a user or a course cleans up correctly, the same approach used in
the KaajKoro schema.

---

## 5. How To Run It In XAMPP

### Step 1 — Copy the project folder
Copy the entire `CourseManagementSystem` folder into your XAMPP `htdocs` folder:

- **Windows:** `C:\xampp\htdocs\CourseManagementSystem`
- **macOS:** `/Applications/XAMPP/htdocs/CourseManagementSystem`
- **Linux:** `/opt/lampp/htdocs/CourseManagementSystem`

### Step 2 — Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both
**Apache** and **MySQL**. Both rows should turn green.

### Step 3 — Create the database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **Import** in the top menu
3. Click **Choose File**, select `database.sql` from the project folder
4. Scroll down and click **Go**

phpMyAdmin will create the `cms_db` database with all 9 tables and the sample
data automatically — you do **not** need to create the database by hand first,
`database.sql` does that with `CREATE DATABASE IF NOT EXISTS`.

### Step 4 — Check the database connection
Open `config.php` in the project folder. The defaults already match a fresh
XAMPP install:

```php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";              // default XAMPP password is empty
$db_name = "cms_db";
```

If you ever set a MySQL root password in XAMPP, update `$db_pass` to match it.

### Step 5 — Open the site
Go to:

```
http://localhost/CourseManagementSystem/index.php
```

Log in with any of the demo accounts from the table above, or click
**Register** to create a new student or teacher account.

### Troubleshooting
- **"Database connection failed"** → MySQL is not running, or `$db_name` /
  `$db_user` / `$db_pass` in `config.php` don't match your MySQL setup.
- **A blank white page** → open `http://localhost/phpmyadmin` and check the
  `cms_db` database actually has tables; re-import `database.sql` if not.
- **Login says "No account found"** → make sure the import in Step 3
  finished without errors (scroll up on the phpMyAdmin import result page).

---

## 6. Folder Contents

```
CourseManagementSystem/
├── config.php                 Database connection
├── functions.php               Shared helper functions
├── database.sql                 Full schema + sample data
├── style.css                    Site-wide stylesheet
├── header.php / footer.php      Layout includes
├── check_login.php               Guard: any logged-in user
├── check_admin.php               Guard: admin only
├── check_teacher.php             Guard: teacher only
├── check_student.php             Guard: student only
│
├── index.php, register.php, login.php, forgot.php, logout.php
├── register.js, login.js, check_email.php   (AJAX email check)
│
├── dashboard.php                 Role-based dashboard (student / teacher)
├── profile.php, notifications.php
│
├── courses.php, course-details.php, my-courses.php
├── grades.php, add-grade.php
├── drop-request.php, drop-requests.php
│
├── admin-dashboard.php, admin-users.php, admin-courses.php
├── add-course.php, edit-course.php
├── admin-drop-requests.php, admin-reports.php
```

Every page in this project was syntax-checked with `php -l` and functionally
tested end-to-end (login for all three roles, enrolling, grading, and the
full two-step drop-request approval) against a real MySQL database before
being handed to you.
