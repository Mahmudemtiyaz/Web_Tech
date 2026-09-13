<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_teacher.php";

$course_id  = isset($_GET["course_id"])  ? intval($_GET["course_id"])  : 0;
$student_id = isset($_GET["student_id"]) ? intval($_GET["student_id"]) : 0;

$course = getCourse($conn, $course_id);

// this teacher must own the course, and the student must actually be enrolled
if ($course == null || $course["teacher_id"] != $me["user_id"]) {
    header("Location: my-courses.php");
    exit();
}

$enrollment = getEnrollment($conn, $student_id, $course_id);

if ($enrollment == null) {
    header("Location: course-details.php?id=" . $course_id);
    exit();
}

$student = getUser($conn, $student_id);
$errors  = array();

$component = "";
$marks     = "";
$remarks   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $component = trim($_POST["component"]);
    $marks     = trim($_POST["marks"]);
    $remarks   = trim($_POST["remarks"]);

    $allowed_components = array("Assignment", "Quiz", "Midterm", "Final", "Project", "Attendance");

    if (!in_array($component, $allowed_components)) {
        $errors["component"] = "Please choose a valid component.";
    }

    if ($marks == "" || !is_numeric($marks)) {
        $errors["marks"] = "Enter a valid number.";
    }
    else if ($marks < 0 || $marks > 100) {
        $errors["marks"] = "Marks must be between 0 and 100.";
    }

    if (count($errors) == 0) {

        $today = date("Y-m-d");

        // ON DUPLICATE lets a teacher re-save the same component to correct a mistake
        $stmt = $conn->prepare("INSERT INTO grades (student_id, course_id, component, marks, remarks, graded_at)
                                VALUES (?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE marks = VALUES(marks), remarks = VALUES(remarks), graded_at = VALUES(graded_at)");
        $stmt->bind_param("iisdss", $student_id, $course_id, $component, $marks, $remarks, $today);
        $stmt->execute();
        $stmt->close();

        addNotification($conn, $student_id, "You were graded for " . $course["title"] . " - " . $component . ".", "grades.php");
        addActivity($conn, $me["user_id"], "Graded " . $student["name"] . " for " . $course["title"]);

        header("Location: grades.php?course_id=" . $course_id);
        exit();
    }
}

// ---------- existing grades of this student in this course ----------

$gstmt = $conn->prepare("SELECT * FROM grades WHERE student_id = ? AND course_id = ? ORDER BY grade_id");
$gstmt->bind_param("ii", $student_id, $course_id);
$gstmt->execute();
$existing = $gstmt->get_result();

$page_title = "Give Grade";
include "header.php";
?>

    <div class="medium-page">

        <div class="box">

            <h2 class="center">Grade <?php echo htmlspecialchars($student["name"]); ?></h2>
            <p class="page-info center"><?php echo htmlspecialchars($course["course_code"] . " - " . $course["title"]); ?></p>

            <?php if ($existing->num_rows > 0) { ?>

                <h3>Already Graded</h3>
                <table>
                    <tr><th>Component</th><th>Marks</th><th>Remarks</th></tr>
                    <?php while ($g = $existing->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($g["component"]); ?></td>
                            <td><?php echo $g["marks"]; ?></td>
                            <td><?php echo htmlspecialchars($g["remarks"]); ?></td>
                        </tr>
                    <?php } ?>
                </table>

            <?php } ?>

            <h3 style="margin-top:20px">Add / Update A Component</h3>

            <form method="POST" action="add-grade.php?course_id=<?php echo $course_id; ?>&student_id=<?php echo $student_id; ?>">

                <label for="component">Component</label>
                <select id="component" name="component">
                    <option value="">-- Select --</option>
                    <?php foreach (array("Assignment", "Quiz", "Midterm", "Final", "Project", "Attendance") as $c) { ?>
                        <option value="<?php echo $c; ?>" <?php if ($component == $c) { echo "selected"; } ?>><?php echo $c; ?></option>
                    <?php } ?>
                </select>
                <small class="error"><?php if (isset($errors["component"])) { echo $errors["component"]; } ?></small>

                <label for="marks">Marks (out of 100)</label>
                <input type="number" id="marks" name="marks" step="0.01" min="0" max="100"
                       value="<?php echo htmlspecialchars($marks); ?>">
                <small class="error"><?php if (isset($errors["marks"])) { echo $errors["marks"]; } ?></small>

                <label for="remarks">Remarks (optional)</label>
                <textarea id="remarks" name="remarks" placeholder="A short comment for the student"><?php echo htmlspecialchars($remarks); ?></textarea>

                <div class="buttons">
                    <button type="submit">Save Grade</button>
                    <a href="course-details.php?id=<?php echo $course_id; ?>" class="btn btn-gray">Back to Course</a>
                </div>

            </form>

        </div>

    </div>

<?php
$gstmt->close();
include "footer.php";
?>
