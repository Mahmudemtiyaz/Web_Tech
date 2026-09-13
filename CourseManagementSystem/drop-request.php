<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_student.php";

$course_id = isset($_GET["course_id"]) ? intval($_GET["course_id"]) : 0;
$course    = getCourseWithTeacher($conn, $course_id);
$enrollment = getEnrollment($conn, $me["user_id"], $course_id);

// the student must actually be enrolled in this course right now
if ($course == null || $enrollment == null || $enrollment["enroll_status"] != "enrolled") {
    header("Location: my-courses.php");
    exit();
}

// only one open drop request per course at a time
$existing_stmt = $conn->prepare("SELECT * FROM drop_requests WHERE student_id = ? AND course_id = ?
                                 AND (admin_status = 'pending') ORDER BY request_id DESC LIMIT 1");
$existing_stmt->bind_param("ii", $me["user_id"], $course_id);
$existing_stmt->execute();
$existing = $existing_stmt->get_result()->fetch_assoc();
$existing_stmt->close();

$errors = array();
$reason = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $existing == null) {

    $reason = trim($_POST["reason"]);

    if ($reason == "") {
        $errors["reason"] = "Please explain why you want to drop this course.";
    }
    else if (strlen($reason) < 10) {
        $errors["reason"] = "Please write at least 10 characters.";
    }

    if (count($errors) == 0) {

        $today = date("Y-m-d");
        $stmt = $conn->prepare("INSERT INTO drop_requests (student_id, course_id, reason, teacher_status, admin_status, created_at)
                                VALUES (?, ?, ?, 'pending', 'pending', ?)");
        $stmt->bind_param("iiss", $me["user_id"], $course_id, $reason, $today);
        $stmt->execute();
        $stmt->close();

        addActivity($conn, $me["user_id"], "Submitted a drop request for " . $course["title"]);

        if ($course["teacher_id"]) {
            addNotification($conn, $course["teacher_id"], $me["name"] . " requested to drop " . $course["title"] . ".", "drop-requests.php");
        }

        header("Location: drop-requests.php");
        exit();
    }
}

$page_title = "Request To Drop";
include "header.php";
?>

    <div class="small-page">

        <div class="box">

            <h2 class="center">Request To Drop A Course</h2>
            <p class="page-info center">
                <?php echo htmlspecialchars($course["course_code"] . " - " . $course["title"]); ?>
            </p>

            <?php if ($existing != null) { ?>

                <div class="result">
                    You already have a pending drop request for this course. It needs the
                    teacher's approval and then the admin's final approval.
                    <br><br>
                    <a href="drop-requests.php">View my drop requests</a>
                </div>

            <?php } else { ?>

                <div class="result">
                    A drop request first needs your teacher's approval, and then the admin gives
                    the final approval. You stay enrolled until it is fully approved.
                </div>

                <form method="POST" action="drop-request.php?course_id=<?php echo $course_id; ?>">

                    <label for="reason">Reason For Dropping</label>
                    <textarea id="reason" name="reason" placeholder="Explain why you want to drop this course..."><?php echo htmlspecialchars($reason); ?></textarea>
                    <small class="error"><?php if (isset($errors["reason"])) { echo $errors["reason"]; } ?></small>

                    <button type="submit" class="btn-full">Submit Request</button>

                </form>

            <?php } ?>

            <p class="center" style="margin-top:15px">
                <a href="course-details.php?id=<?php echo $course_id; ?>">Back to course</a>
            </p>

        </div>

    </div>

<?php include "footer.php"; ?>
