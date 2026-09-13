<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

// this page is for browsing / enrolling, so it is meant for students
// (teachers and admins are redirected to the page that fits their role)

if ($me["role"] == "teacher") {
    header("Location: my-courses.php");
    exit();
}

if ($me["role"] == "admin") {
    header("Location: admin-courses.php");
    exit();
}

$action = isset($_GET["action"]) ? $_GET["action"] : "";
$id     = isset($_GET["id"])     ? intval($_GET["id"]) : 0;

// ---------- enroll in a course ----------

if ($action == "enroll" && $id > 0) {

    $course = getCourse($conn, $id);
    $already = getEnrollment($conn, $me["user_id"], $id);

    if ($course == null) {
        // course does not exist, nothing to do
    }
    else if ($already != null && $already["enroll_status"] == "enrolled") {
        // already enrolled, nothing to do
    }
    else if (countEnrolled($conn, $id) >= $course["capacity"]) {
        // course is full, nothing to do
    }
    else if ($already != null) {

        // was dropped earlier, re-enroll
        $stmt = $conn->prepare("UPDATE enrollments SET enroll_status = 'enrolled', enrolled_at = ? WHERE student_id = ? AND course_id = ?");
        $today = date("Y-m-d");
        $stmt->bind_param("sii", $today, $me["user_id"], $id);
        $stmt->execute();
        $stmt->close();

        addActivity($conn, $me["user_id"], "Enrolled in " . $course["title"]);
    }
    else {

        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enroll_status, enrolled_at) VALUES (?, ?, 'enrolled', ?)");
        $today = date("Y-m-d");
        $stmt->bind_param("iis", $me["user_id"], $id, $today);
        $stmt->execute();
        $stmt->close();

        addActivity($conn, $me["user_id"], "Enrolled in " . $course["title"]);

        if ($course["teacher_id"]) {
            addNotification($conn, $course["teacher_id"], $me["name"] . " enrolled in " . $course["title"] . ".", "course-details.php?id=" . $id);
        }
    }

    header("Location: courses.php");
    exit();
}

// ---------- search and filter ----------

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

$sql = "SELECT c.*, u.name AS teacher_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.enroll_status = 'enrolled') AS enrolled
        FROM courses c
        LEFT JOIN users u ON c.teacher_id = u.user_id
        WHERE c.course_status = 'active'";

if ($search != "") {
    $sql = $sql . " AND (c.title LIKE ? OR c.course_code LIKE ?)";
}

$sql = $sql . " ORDER BY c.course_code ASC";

$stmt = $conn->prepare($sql);

if ($search != "") {
    $like = "%" . $search . "%";
    $stmt->bind_param("ss", $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();

$page_title = "Browse Courses";
include "header.php";
?>

    <div class="page">

        <h1>Browse Courses</h1>
        <p class="page-info">Find a course and enroll in one click.</p>

        <div class="box">

            <form method="GET" action="courses.php">
                <div class="row">
                    <div class="col-left">
                        <label for="search">Search</label>
                        <input type="search" id="search" name="search" placeholder="Search by course title or code"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-right" style="display:flex;align-items:flex-end">
                        <div class="buttons" style="margin-top:0">
                            <button type="submit">Search</button>
                            <button type="button" class="btn-gray" onclick="window.location.href='courses.php'">Clear</button>
                        </div>
                    </div>
                </div>
            </form>

        </div>

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">No course found for this search.</div>

        <?php } else { ?>

            <div class="card-grid">

                <?php while ($course = $result->fetch_assoc()) {

                    $my_enrollment = getEnrollment($conn, $me["user_id"], $course["course_id"]);
                    $is_enrolled   = $my_enrollment != null && $my_enrollment["enroll_status"] == "enrolled";
                    $is_full       = $course["enrolled"] >= $course["capacity"];
                    ?>

                    <div class="course-card">
                        <span class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></span>
                        <h3><?php echo htmlspecialchars($course["title"]); ?></h3>
                        <p class="item-text"><?php echo htmlspecialchars($course["description"]); ?></p>
                        <p class="item-info">
                            Instructor : <?php echo htmlspecialchars($course["teacher_name"] ?? "Not assigned"); ?><br>
                            <?php echo $course["credit_hour"]; ?> credit hours &middot; <?php echo $course["enrolled"]; ?> / <?php echo $course["capacity"]; ?> seats filled
                        </p>

                        <div class="buttons">
                            <a href="course-details.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-small btn-outline">View Details</a>

                            <?php if ($is_enrolled) { ?>
                                <span class="tag tag-green" style="align-self:center">Enrolled</span>
                            <?php } else if ($is_full) { ?>
                                <span class="tag tag-red" style="align-self:center">Full</span>
                            <?php } else { ?>
                                <a href="courses.php?action=enroll&id=<?php echo $course["course_id"]; ?>" class="btn btn-small btn-green">Enroll</a>
                            <?php } ?>
                        </div>
                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

<?php
$stmt->close();
include "footer.php";
?>
