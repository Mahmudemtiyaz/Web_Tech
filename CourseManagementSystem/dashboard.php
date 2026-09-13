<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

// the admin has its own dashboard page
if ($me["role"] == "admin") {
    header("Location: admin-dashboard.php");
    exit();
}

$page_title = "Dashboard";

// ============================================
// STUDENT DASHBOARD
// ============================================

if ($me["role"] == "student") {

    $enrolled_count = countRows($conn,
        "SELECT COUNT(*) AS total FROM enrollments WHERE student_id = ? AND enroll_status = 'enrolled'", $me["user_id"]);

    $pending_drops = countRows($conn,
        "SELECT COUNT(*) AS total FROM drop_requests WHERE student_id = ? AND admin_status = 'pending'", $me["user_id"]);

    $avg_result = $conn->prepare("SELECT AVG(marks) AS average FROM grades WHERE student_id = ?");
    $avg_result->bind_param("i", $me["user_id"]);
    $avg_result->execute();
    $avg_row = $avg_result->get_result()->fetch_assoc();
    $avg_result->close();
    $average_marks = $avg_row["average"] == null ? 0 : round($avg_row["average"], 2);

    // enrolled courses, latest first
    $stmt = $conn->prepare("SELECT c.*, u.name AS teacher_name
                            FROM enrollments e
                            JOIN courses c ON e.course_id = c.course_id
                            LEFT JOIN users u ON c.teacher_id = u.user_id
                            WHERE e.student_id = ? AND e.enroll_status = 'enrolled'
                            ORDER BY e.enrollment_id DESC LIMIT 4");
    $stmt->bind_param("i", $me["user_id"]);
    $stmt->execute();
    $my_courses = $stmt->get_result();

    // announcements : global ones, plus ones from the courses this student is enrolled in
    $announce_stmt = $conn->prepare("SELECT a.*, u.name AS poster_name, c.title AS course_title
                                     FROM announcements a
                                     JOIN users u ON a.posted_by = u.user_id
                                     LEFT JOIN courses c ON a.course_id = c.course_id
                                     WHERE a.course_id IS NULL
                                        OR a.course_id IN (SELECT course_id FROM enrollments WHERE student_id = ? AND enroll_status = 'enrolled')
                                     ORDER BY a.created_at DESC LIMIT 4");
    $announce_stmt->bind_param("i", $me["user_id"]);
    $announce_stmt->execute();
    $announcements = $announce_stmt->get_result();

    include "header.php";
    ?>

    <div class="page">

        <h1>Welcome back, <?php echo htmlspecialchars(explode(" ", $me["name"])[0]); ?> 👋</h1>
        <p class="page-info">Here is what is happening in your courses.</p>

        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?php echo $enrolled_count; ?></div>
                <div class="stat-name">Enrolled Courses</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $average_marks; ?></div>
                <div class="stat-name">Average Marks</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $pending_drops; ?></div>
                <div class="stat-name">Pending Drop Requests</div>
            </div>
        </div>

        <div class="row">

            <div class="col-left">

                <div class="box">
                    <h2 class="box-title">My Courses</h2>

                    <?php if ($my_courses->num_rows == 0) { ?>

                        <div class="empty">You are not enrolled in any course yet.
                            <br><a href="courses.php">Browse available courses</a></div>

                    <?php } else { while ($course = $my_courses->fetch_assoc()) { ?>

                        <div class="item">
                            <span class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></span>
                            <h3><a href="course-details.php?id=<?php echo $course["course_id"]; ?>"><?php echo htmlspecialchars($course["title"]); ?></a></h3>
                            <div class="item-info">Instructor : <?php echo htmlspecialchars($course["teacher_name"] ?? "Not assigned"); ?></div>
                        </div>

                    <?php } } ?>

                    <a href="my-courses.php" class="btn-outline btn btn-small">View all my courses</a>

                </div>

            </div>

            <div class="col-right">

                <div class="box">
                    <h2 class="box-title">Announcements</h2>

                    <?php if ($announcements->num_rows == 0) { ?>

                        <div class="empty">No announcements yet.</div>

                    <?php } else { while ($a = $announcements->fetch_assoc()) { ?>

                        <div class="notif">
                            <div>
                                <div class="item-text"><?php echo htmlspecialchars($a["message"]); ?></div>
                                <span class="gray-text">
                                    <?php echo $a["course_title"] ? htmlspecialchars($a["course_title"]) : "System-wide"; ?>
                                    &middot; <?php echo showDateTime($a["created_at"]); ?>
                                </span>
                            </div>
                        </div>

                    <?php } } ?>

                </div>

            </div>

        </div>

    </div>

    <?php
    include "footer.php";
    exit();
}

// ============================================
// TEACHER DASHBOARD
// ============================================

$course_count = countRows($conn,
    "SELECT COUNT(*) AS total FROM courses WHERE teacher_id = ?", $me["user_id"]);

$student_count = countRows($conn,
    "SELECT COUNT(DISTINCT e.student_id) AS total
     FROM enrollments e JOIN courses c ON e.course_id = c.course_id
     WHERE c.teacher_id = ? AND e.enroll_status = 'enrolled'", $me["user_id"]);

$pending_review = countRows($conn,
    "SELECT COUNT(*) AS total FROM drop_requests d
     JOIN courses c ON d.course_id = c.course_id
     WHERE c.teacher_id = ? AND d.teacher_status = 'pending'", $me["user_id"]);

$stmt = $conn->prepare("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.enroll_status = 'enrolled') AS enrolled
                        FROM courses c WHERE c.teacher_id = ? ORDER BY c.course_id DESC");
$stmt->bind_param("i", $me["user_id"]);
$stmt->execute();
$my_courses = $stmt->get_result();

include "header.php";
?>

<div class="page">

    <h1>Welcome back, <?php echo htmlspecialchars(explode(" ", $me["name"])[0]); ?> 👋</h1>
    <p class="page-info">Here is an overview of the courses you teach.</p>

    <div class="stats">
        <div class="stat">
            <div class="stat-number"><?php echo $course_count; ?></div>
            <div class="stat-name">Courses You Teach</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $student_count; ?></div>
            <div class="stat-name">Total Students</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $pending_review; ?></div>
            <div class="stat-name">Drop Requests To Review</div>
        </div>
    </div>

    <div class="box">
        <h2 class="box-title">My Courses</h2>

        <?php if ($my_courses->num_rows == 0) { ?>

            <div class="empty">No course has been assigned to you yet. Please contact the admin.</div>

        <?php } else { while ($course = $my_courses->fetch_assoc()) { ?>

            <div class="item">
                <span class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></span>
                <h3><a href="course-details.php?id=<?php echo $course["course_id"]; ?>"><?php echo htmlspecialchars($course["title"]); ?></a></h3>
                <div class="item-info"><?php echo $course["enrolled"]; ?> students enrolled &middot; <?php echo $course["credit_hour"]; ?> credit hours</div>
                <div class="item-text"><?php echo htmlspecialchars($course["description"]); ?></div>
                <a href="course-details.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-small">Manage Course</a>
            </div>

        <?php } } ?>

    </div>

</div>

<?php
$stmt->close();
include "footer.php";
?>
