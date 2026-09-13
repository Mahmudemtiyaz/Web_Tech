<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

if ($me["role"] == "admin") {
    header("Location: admin-courses.php");
    exit();
}

$page_title = "My Courses";

// ============================================
// STUDENT : the courses I am enrolled in
// ============================================

if ($me["role"] == "student") {

    $stmt = $conn->prepare("SELECT c.*, u.name AS teacher_name, e.enroll_status, e.enrolled_at
                            FROM enrollments e
                            JOIN courses c ON e.course_id = c.course_id
                            LEFT JOIN users u ON c.teacher_id = u.user_id
                            WHERE e.student_id = ?
                            ORDER BY e.enroll_status ASC, c.course_code ASC");
    $stmt->bind_param("i", $me["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    include "header.php";
    ?>

    <div class="page">

        <h1>My Courses</h1>
        <p class="page-info">Every course you have ever enrolled in, including the ones you dropped.</p>

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">You have not enrolled in any course yet.
                <br><a href="courses.php">Browse available courses</a></div>

        <?php } else { ?>

            <table>
                <tr><th>Code</th><th>Course</th><th>Instructor</th><th>Enrolled On</th><th>Status</th><th>Action</th></tr>

                <?php while ($c = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c["course_code"]); ?></td>
                        <td><a href="course-details.php?id=<?php echo $c["course_id"]; ?>"><?php echo htmlspecialchars($c["title"]); ?></a></td>
                        <td><?php echo htmlspecialchars($c["teacher_name"] ?? "Not assigned"); ?></td>
                        <td><?php echo showDate($c["enrolled_at"]); ?></td>
                        <td><?php echo showStatus($c["enroll_status"]); ?></td>
                        <td>
                            <?php if ($c["enroll_status"] == "enrolled") { ?>
                                <a href="drop-request.php?course_id=<?php echo $c["course_id"]; ?>" class="btn btn-small btn-outline">Request Drop</a>
                            <?php } else { ?>
                                <a href="courses.php?action=enroll&id=<?php echo $c["course_id"]; ?>" class="btn btn-small btn-green">Re-enroll</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>

            </table>

        <?php } ?>

    </div>

    <?php
    $stmt->close();
    include "footer.php";
    exit();
}

// ============================================
// TEACHER : the courses I teach
// ============================================

require_once "check_teacher.php";

$stmt = $conn->prepare("SELECT c.*,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.enroll_status = 'enrolled') AS enrolled
                        FROM courses c WHERE c.teacher_id = ? ORDER BY c.course_code ASC");
$stmt->bind_param("i", $me["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>

<div class="page">

    <h1>My Courses</h1>
    <p class="page-info">The courses assigned to you by the admin.</p>

    <?php if ($result->num_rows == 0) { ?>

        <div class="empty">No course has been assigned to you yet. Please contact the admin.</div>

    <?php } else { ?>

        <div class="card-grid">

            <?php while ($c = $result->fetch_assoc()) { ?>

                <div class="course-card">
                    <span class="course-code"><?php echo htmlspecialchars($c["course_code"]); ?></span>
                    <h3><?php echo htmlspecialchars($c["title"]); ?></h3>
                    <p class="item-text"><?php echo htmlspecialchars($c["description"]); ?></p>
                    <p class="item-info"><?php echo $c["enrolled"]; ?> / <?php echo $c["capacity"]; ?> students &middot; <?php echo $c["credit_hour"]; ?> credit hours</p>
                    <a href="course-details.php?id=<?php echo $c["course_id"]; ?>" class="btn btn-small">Manage Course</a>
                </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

<?php
$stmt->close();
include "footer.php";
?>
