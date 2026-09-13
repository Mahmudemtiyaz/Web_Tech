<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

if ($me["role"] == "admin") {
    header("Location: admin-dashboard.php");
    exit();
}

$page_title = "Grades";

// ============================================
// STUDENT : my own grades, grouped by course
// ============================================

if ($me["role"] == "student") {

    $stmt = $conn->prepare("SELECT c.course_id, c.course_code, c.title
                            FROM enrollments e JOIN courses c ON e.course_id = c.course_id
                            WHERE e.student_id = ? AND e.enroll_status = 'enrolled'
                            ORDER BY c.course_code ASC");
    $stmt->bind_param("i", $me["user_id"]);
    $stmt->execute();
    $courses = $stmt->get_result();

    include "header.php";
    ?>

    <div class="page">

        <h1>My Grades</h1>
        <p class="page-info">Your grade components across every course you are enrolled in.</p>

        <?php if ($courses->num_rows == 0) { ?>

            <div class="empty">You are not enrolled in any course yet.</div>

        <?php } else { while ($course = $courses->fetch_assoc()) {

            $gstmt = $conn->prepare("SELECT * FROM grades WHERE student_id = ? AND course_id = ? ORDER BY grade_id");
            $gstmt->bind_param("ii", $me["user_id"], $course["course_id"]);
            $gstmt->execute();
            $grades = $gstmt->get_result();

            $total = 0;
            $count = 0;
            $rows  = array();

            while ($g = $grades->fetch_assoc()) {
                $rows[] = $g;
                $total  = $total + $g["marks"];
                $count  = $count + 1;
            }

            $average = $count > 0 ? round($total / $count, 2) : 0;
            ?>

            <div class="box">
                <h2 class="box-title">
                    <?php echo htmlspecialchars($course["course_code"]); ?> - <?php echo htmlspecialchars($course["title"]); ?>
                </h2>

                <?php if (count($rows) == 0) { ?>

                    <div class="empty">No grade has been posted yet for this course.</div>

                <?php } else { ?>

                    <table>
                        <tr><th>Component</th><th>Marks</th><th>Remarks</th><th>Date</th></tr>
                        <?php foreach ($rows as $g) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($g["component"]); ?></td>
                                <td><?php echo $g["marks"]; ?></td>
                                <td><?php echo htmlspecialchars($g["remarks"]); ?></td>
                                <td><?php echo showDate($g["graded_at"]); ?></td>
                            </tr>
                        <?php } ?>
                    </table>

                    <p style="margin-top:12px">
                        <b>Average : <?php echo $average; ?></b> &nbsp;
                        <span class="tag tag-blue">Grade <?php echo markToLetter($average); ?></span>
                    </p>

                <?php } ?>
            </div>

        <?php } } ?>

    </div>

    <?php include "footer.php"; exit(); ?>

<?php
}

// ============================================
// TEACHER : pick a course, manage grades of every enrolled student
// ============================================

require_once "check_teacher.php";

$course_id = isset($_GET["course_id"]) ? intval($_GET["course_id"]) : 0;

$courses_stmt = $conn->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_code ASC");
$courses_stmt->bind_param("i", $me["user_id"]);
$courses_stmt->execute();
$my_courses = $courses_stmt->get_result();

$selected_course = null;
$students        = null;

if ($course_id > 0) {

    $selected_course = getCourse($conn, $course_id);

    // an outsider teacher cannot look into a course that is not theirs
    if ($selected_course == null || $selected_course["teacher_id"] != $me["user_id"]) {
        $selected_course = null;
    }
    else {

        $sstmt = $conn->prepare("SELECT u.user_id, u.name FROM enrollments e
                                 JOIN users u ON e.student_id = u.user_id
                                 WHERE e.course_id = ? AND e.enroll_status = 'enrolled'
                                 ORDER BY u.name ASC");
        $sstmt->bind_param("i", $course_id);
        $sstmt->execute();
        $students = $sstmt->get_result();
    }
}

include "header.php";
?>

<div class="page">

    <h1>Grades</h1>
    <p class="page-info">Select one of your courses to manage the grades of every enrolled student.</p>

    <div class="box">
        <form method="GET" action="grades.php">
            <label for="course_id">Course</label>
            <select id="course_id" name="course_id" onchange="this.form.submit()">
                <option value="">-- Select a course --</option>
                <?php while ($c = $my_courses->fetch_assoc()) { ?>
                    <option value="<?php echo $c["course_id"]; ?>" <?php if ($course_id == $c["course_id"]) { echo "selected"; } ?>>
                        <?php echo htmlspecialchars($c["course_code"] . " - " . $c["title"]); ?>
                    </option>
                <?php } ?>
            </select>
        </form>
    </div>

    <?php if ($selected_course != null) { ?>

        <div class="box">
            <h2 class="box-title"><?php echo htmlspecialchars($selected_course["title"]); ?> - Student Grades</h2>

            <?php if ($students->num_rows == 0) { ?>

                <div class="empty">No student is enrolled in this course yet.</div>

            <?php } else { ?>

                <table>
                    <tr><th>Student</th><th>Components Graded</th><th>Average</th><th>Action</th></tr>

                    <?php while ($s = $students->fetch_assoc()) {

                        $gstmt = $conn->prepare("SELECT AVG(marks) AS average, COUNT(*) AS total FROM grades WHERE student_id = ? AND course_id = ?");
                        $gstmt->bind_param("ii", $s["user_id"], $course_id);
                        $gstmt->execute();
                        $g = $gstmt->get_result()->fetch_assoc();
                        $gstmt->close();

                        $avg = $g["average"] == null ? 0 : round($g["average"], 2);
                        ?>

                        <tr>
                            <td><?php echo htmlspecialchars($s["name"]); ?></td>
                            <td><?php echo $g["total"]; ?></td>
                            <td><?php echo $avg; ?> <?php if ($g["total"] > 0) { echo "(" . markToLetter($avg) . ")"; } ?></td>
                            <td><a href="add-grade.php?course_id=<?php echo $course_id; ?>&student_id=<?php echo $s["user_id"]; ?>" class="btn btn-small">Add / Edit Grade</a></td>
                        </tr>

                    <?php } ?>

                </table>

            <?php } ?>
        </div>

    <?php } ?>

</div>

<?php
include "footer.php";
?>
