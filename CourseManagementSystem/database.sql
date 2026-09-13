CREATE DATABASE IF NOT EXISTS cms_db;
USE cms_db;

DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS drop_requests;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

-- ---------- USER ----------
-- one table, one "role" column, instead of a separate admin table.
-- this is the one place Part-2 style is different from a 2-table login,
-- because here we have three roles instead of two.

CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    phone       VARCHAR(20),
    department  VARCHAR(100),
    bio         TEXT,
    status      VARCHAR(20)  DEFAULT 'active',
    joined      DATE
);

-- ---------- COURSE ----------

CREATE TABLE courses (
    course_id     INT AUTO_INCREMENT PRIMARY KEY,
    course_code   VARCHAR(20)  NOT NULL UNIQUE,
    title         VARCHAR(150) NOT NULL,
    description   TEXT,
    credit_hour   DECIMAL(2,1) NOT NULL DEFAULT 3.0,
    teacher_id    INT DEFAULT NULL,
    capacity      INT NOT NULL DEFAULT 40,
    course_status VARCHAR(20) DEFAULT 'active',
    created_at    DATE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ---------- ENROLLMENT (student  M : N  course, with extra attributes) ----------

CREATE TABLE enrollments (
    enrollment_id  INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    course_id      INT NOT NULL,
    enroll_status  VARCHAR(20) DEFAULT 'enrolled',
    enrolled_at    DATE,
    UNIQUE (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ---------- MATERIAL (one course  1 : M  material) ----------

CREATE TABLE materials (
    material_id  INT AUTO_INCREMENT PRIMARY KEY,
    course_id    INT NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    link_url     VARCHAR(255),
    uploaded_at  DATE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ---------- GRADE (weak entity : depends on one student AND one course) ----------

CREATE TABLE grades (
    grade_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    course_id   INT NOT NULL,
    component   VARCHAR(50) NOT NULL,
    marks       DECIMAL(5,2) NOT NULL,
    remarks     VARCHAR(255),
    graded_at   DATE,
    UNIQUE (student_id, course_id, component),
    FOREIGN KEY (student_id) REFERENCES users(user_id)     ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ---------- DROP REQUEST ----------
-- two approval columns, because the flow needs the teacher first
-- and then the admin gives the final approval (this is the ER diagram's
-- "process" entity between a student and a course).

CREATE TABLE drop_requests (
    request_id     INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    course_id      INT NOT NULL,
    reason         TEXT NOT NULL,
    teacher_status VARCHAR(20) DEFAULT 'pending',
    admin_status   VARCHAR(20) DEFAULT 'pending',
    created_at     DATE,
    FOREIGN KEY (student_id) REFERENCES users(user_id)     ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ---------- ANNOUNCEMENT ----------
-- course_id is NULL for a system-wide notice posted by the admin.

CREATE TABLE announcements (
    announcement_id  INT AUTO_INCREMENT PRIMARY KEY,
    course_id        INT DEFAULT NULL,
    posted_by        INT NOT NULL,
    message          TEXT NOT NULL,
    created_at       DATETIME,
    FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (posted_by)  REFERENCES users(user_id)     ON DELETE CASCADE
);

-- ---------- NOTIFICATION ----------

CREATE TABLE notifications (
    notif_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    text        TEXT NOT NULL,
    link        VARCHAR(100),
    is_read     TINYINT DEFAULT 0,
    created_at  DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------- ACTIVITY LOG ----------

CREATE TABLE activities (
    activity_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    text         TEXT NOT NULL,
    created_at   DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- ============================================
-- SAMPLE DATA
-- Every password below is hashed with password_hash().
-- Plain text passwords are written next to each one so you can log in.
-- ============================================

-- ---------- USERS ----------
-- admin123   -> $2b$10$vAWneX38HxWyf2nxrmbMqu.5GXWpoKYAklD4q/m59wS08Rh5GSL9C
-- 123456     -> $2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC

INSERT INTO users (user_id, name, email, password, role, phone, department, bio, status, joined) VALUES
(1,  'Md. Kamrul Hasan',      'admin@coursehub.com',   '$2b$10$vAWneX38HxWyf2nxrmbMqu.5GXWpoKYAklD4q/m59wS08Rh5GSL9C', 'admin',   '01711-000001', 'Administration',        'System administrator of CourseHub.', 'active', '2025-01-01'),
(2,  'Dr. Rahim Uddin',       'rahim@coursehub.com',   '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'teacher', '01711-000002', 'Computer Science',      'Teaches introductory and data structure courses for 8 years.', 'active', '2025-01-05'),
(3,  'Dr. Farah Islam',       'farah@coursehub.com',   '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'teacher', '01711-000003', 'Computer Science',      'Database and web technology instructor.', 'active', '2025-01-06'),
(4,  'Dr. Nasima Akter',      'nasima@coursehub.com',  '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'teacher', '01711-000004', 'Electrical Engineering', 'Digital logic and electronics instructor.', 'active', '2025-01-07'),
(5,  'Tasfir Ul Alam Shofnil','tasfir@coursehub.com',  '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000005', 'Computer Science',      'Third year CSE student.', 'active', '2025-02-10'),
(6,  'Emtiyaz Mahmud Tamim',  'emtiyaz@coursehub.com', '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000006', 'Computer Science',      'Interested in backend development.', 'active', '2025-02-11'),
(7,  'Shariar Hasnat',        'shariar@coursehub.com', '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000007', 'Computer Science',      'Enjoys competitive programming.', 'active', '2025-02-12'),
(8,  'Md. Mahin',             'mahin@coursehub.com',   '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000008', 'Electrical Engineering','Second year EEE student.', 'active', '2025-02-13'),
(9,  'Nusrat Jahan',          'nusrat@coursehub.com',  '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000009', 'Computer Science',      'Loves UI/UX and front-end design.', 'active', '2025-02-14'),
(10, 'Ayesha Siddika',        'ayesha@coursehub.com',  '$2b$10$Lyh1q1WVbEcWlOW7yHuV4.oKE5ek/MkD4qvdhwmx2URU5jPSLbgHC', 'student', '01911-000010', 'Computer Science',      'First year CSE student.', 'suspended', '2025-02-15');

-- ---------- COURSES ----------

INSERT INTO courses (course_id, course_code, title, description, credit_hour, teacher_id, capacity, course_status, created_at) VALUES
(1, 'CSC101', 'Introduction to Programming', 'Basics of programming using C : variables, loops, functions and arrays.', 3.0, 2, 40, 'active', '2025-05-01'),
(2, 'CSC205', 'Data Structures', 'Arrays, linked lists, stacks, queues, trees and sorting algorithms.', 3.0, 2, 35, 'active', '2025-05-01'),
(3, 'CSC310', 'Database Systems', 'ER modelling, normalization, SQL and transactions.', 3.0, 3, 40, 'active', '2025-05-02'),
(4, 'CSC315', 'Web Technologies', 'HTML, CSS, JavaScript, PHP, MySQL and building full web applications.', 3.0, 3, 45, 'active', '2025-05-02'),
(5, 'EEE201', 'Digital Logic Design', 'Boolean algebra, logic gates, combinational and sequential circuits.', 3.0, 4, 30, 'active', '2025-05-03'),
(6, 'CSC410', 'Software Engineering', 'SDLC, requirement analysis, design patterns and project management.', 3.0, 2, 35, 'active', '2025-05-04'),
(7, 'CSC450', 'Artificial Intelligence', 'Search algorithms, knowledge representation and an introduction to machine learning.', 3.0, NULL, 30, 'active', '2025-05-05');

-- ---------- ENROLLMENTS ----------

INSERT INTO enrollments (enrollment_id, student_id, course_id, enroll_status, enrolled_at) VALUES
(1, 5, 1, 'enrolled', '2025-05-10'),
(2, 5, 2, 'enrolled', '2025-05-10'),
(3, 5, 4, 'enrolled', '2025-05-11'),
(4, 6, 1, 'enrolled', '2025-05-10'),
(5, 6, 3, 'enrolled', '2025-05-12'),
(6, 6, 4, 'dropped',  '2025-05-10'),
(7, 7, 2, 'enrolled', '2025-05-11'),
(8, 7, 4, 'enrolled', '2025-05-11'),
(9, 7, 6, 'enrolled', '2025-05-13'),
(10, 8, 5, 'enrolled', '2025-05-12'),
(11, 9, 3, 'enrolled', '2025-05-13'),
(12, 9, 4, 'enrolled', '2025-05-13');

-- ---------- COURSE MATERIALS ----------

INSERT INTO materials (material_id, course_id, title, description, link_url, uploaded_at) VALUES
(1, 1, 'Week 1 Slides - Introduction to C', 'Basic syntax, compiling and running your first program.', 'https://drive.google.com/example-c-intro', '2025-05-15'),
(2, 1, 'Lab Sheet 1 - Variables and Loops', 'Practice problems on for and while loops.', 'https://drive.google.com/example-c-lab1', '2025-05-18'),
(3, 2, 'Week 1 Slides - Arrays and Linked Lists', 'Introduction to linear data structures.', 'https://drive.google.com/example-ds-week1', '2025-05-16'),
(4, 2, 'Assignment 1 - Stack Implementation', 'Implement a stack using an array in C.', 'https://drive.google.com/example-ds-a1', '2025-05-20'),
(5, 3, 'Week 1 Slides - ER Modelling', 'Entities, attributes and relationships.', 'https://drive.google.com/example-db-week1', '2025-05-17'),
(6, 4, 'Week 1 Slides - HTML and CSS Basics', 'Structure and styling of a web page.', 'https://drive.google.com/example-web-week1', '2025-05-17'),
(7, 4, 'Week 2 Slides - PHP and Sessions', 'Server side scripting and login systems.', 'https://drive.google.com/example-web-week2', '2025-05-24'),
(8, 5, 'Week 1 Slides - Boolean Algebra', 'Logic gates and truth tables.', 'https://drive.google.com/example-eee-week1', '2025-05-18');

-- ---------- GRADES ----------

INSERT INTO grades (grade_id, student_id, course_id, component, marks, remarks, graded_at) VALUES
(1, 5, 1, 'Assignment', 18.00, 'Good understanding of loops.', '2025-06-10'),
(2, 5, 1, 'Midterm',    38.00, 'Well prepared, minor mistakes in arrays.', '2025-07-01'),
(3, 5, 2, 'Assignment', 16.00, 'Stack implementation was correct.', '2025-06-15'),
(4, 6, 1, 'Assignment', 15.00, 'Needs more practice with functions.', '2025-06-10'),
(5, 6, 3, 'Midterm',    33.00, 'Good grasp of normalization.', '2025-07-02'),
(6, 7, 2, 'Assignment', 19.00, 'Excellent work.', '2025-06-15'),
(7, 7, 4, 'Assignment', 17.00, 'Neat and well commented code.', '2025-06-20'),
(8, 9, 3, 'Assignment', 14.00, 'Submit ER diagram next time as well.', '2025-06-16'),
(9, 9, 4, 'Midterm',    35.00, 'Very good performance.', '2025-07-03');

-- ---------- DROP REQUESTS ----------

INSERT INTO drop_requests (request_id, student_id, course_id, reason, teacher_status, admin_status, created_at) VALUES
(1, 6, 4, 'This section clashes with my lab schedule for another course.', 'approved', 'pending', '2025-07-10'),
(2, 7, 6, 'I am finding the course too advanced for this semester.', 'pending',  'pending', '2025-07-12'),
(3, 5, 2, 'I want to retake this course next semester with a lighter load.', 'rejected', 'pending', '2025-07-08');

-- ---------- ANNOUNCEMENTS ----------

INSERT INTO announcements (announcement_id, course_id, posted_by, message, created_at) VALUES
(1, NULL, 1, 'Welcome to CourseHub! The Summer 2025-26 semester officially begins today.', '2025-05-08 09:00:00'),
(2, 1,    2, 'The first lab session for CSC101 will be held online this Friday.', '2025-05-14 10:00:00'),
(3, 4,    3, 'Assignment 2 deadline has been extended by three days.', '2025-06-25 14:30:00'),
(4, NULL, 1, 'Midterm examination routine has been published. Please check your course pages.', '2025-06-28 08:00:00');

-- ---------- NOTIFICATIONS ----------

INSERT INTO notifications (notif_id, user_id, text, link, is_read, created_at) VALUES
(1, 5, 'You were graded for CSC101 - Assignment.', 'grades.php', 0, '2025-06-10 12:00:00'),
(2, 5, 'New material was uploaded in Data Structures.', 'course-details.php?id=2', 0, '2025-05-16 09:00:00'),
(3, 6, 'Your drop request for Web Technologies was approved by the teacher and is waiting for admin approval.', 'drop-requests.php', 0, '2025-07-10 11:00:00'),
(4, 2, 'A new student enrolled in Introduction to Programming.', 'course-details.php?id=1', 1, '2025-05-10 09:15:00'),
(5, 1, 'A new drop request needs your final approval.', 'admin-drop-requests.php', 0, '2025-07-10 11:05:00');

-- ---------- ACTIVITY LOG ----------

INSERT INTO activities (activity_id, user_id, text, created_at) VALUES
(1, 5, 'Logged in to CourseHub', '2025-07-20 09:02:00'),
(2, 5, 'Enrolled in Web Technologies', '2025-05-11 10:00:00'),
(3, 2, 'Uploaded a new material in Introduction to Programming', '2025-05-15 08:00:00'),
(4, 1, 'Created a new course : Artificial Intelligence', '2025-05-05 09:00:00'),
(5, 6, 'Submitted a drop request for Web Technologies', '2025-07-10 10:55:00');
