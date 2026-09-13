<?php
session_start();
require_once "config.php";
require_once "functions.php";

// somebody who is already logged in does not need the landing page
if (isset($_SESSION["user_logged_in"])) {
    header("Location: dashboard.php");
    exit();
}

$total_courses  = countAll($conn, "SELECT COUNT(*) AS total FROM courses WHERE course_status = 'active'");
$total_teachers = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'");
$total_students = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'student'");

$page_title = "Home";
include "header.php";
?>

    <!-- Hero -->

    <div class="hero">
        <div class="container">
            <h1>Manage every course from one clean dashboard.</h1>
            <p>CourseHub brings admins, teachers and students together — enrollment, course
               materials, grades and drop requests, all in one organised platform.</p>

            <div class="buttons">
                <a href="register.php" class="btn">Get Started</a>
                <a href="login.php" class="btn btn-outline">Login</a>
            </div>
        </div>
    </div>

    <div class="page">

        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-name">Active Courses</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_teachers; ?></div>
                <div class="stat-name">Teachers</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-name">Students</div>
            </div>
        </div>

        <h2 class="center" style="margin-bottom:6px">Everything each role needs</h2>
        <p class="page-info center">One platform, three dashboards.</p>

        <div class="features">

            <div class="feature-box">
                <div class="icon">🛠️</div>
                <h3>Admin</h3>
                <p class="item-text">Manage users and courses, and give the final approval on
                   course drop requests.</p>
            </div>

            <div class="feature-box">
                <div class="icon">📚</div>
                <h3>Teacher</h3>
                <p class="item-text">Upload course materials, manage student grades, and review
                   drop requests for your own courses.</p>
            </div>

            <div class="feature-box">
                <div class="icon">🎓</div>
                <h3>Student</h3>
                <p class="item-text">Enroll in courses, access materials and grades, and submit a
                   drop request when you need to.</p>
            </div>

        </div>

    </div>

<?php include "footer.php"; ?>
