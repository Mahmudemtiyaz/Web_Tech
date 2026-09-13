<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$course = getCourseWithTeacher($conn, $id);

if ($course == null) {
    header("Location: courses.php");
    exit();
}

$is_owner_teacher = ($me["role"] == "teacher" && $course["teacher_id"] == $me["user_id"]);
$is_admin         = ($me["role"] == "admin");

$enrollment  = ($me["role"] == "student") ? getEnrollment($conn, $me["user_id"], $id) : null;
$is_enrolled = $enrollment != null && $enrollment["enroll_status"] == "enrolled";

$errors  = array();
$success = "";

// ============================================
// Teacher (owner) actions
// ============================================

if ($is_owner_teacher && $_SERVER["REQUEST_METHOD"] == "POST") {

    $form = isset($_POST["form"]) ? $_POST["form"] : "";

    // ---------- add a material ----------

    if ($form == "add_material") {

        $title       = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $link_url    = trim($_POST["link_url"]);

        if ($title == "") {
            $errors["title"] = "Title is required.";
        }
        if ($link_url == "") {
            $errors["link_url"] = "Link is required.";
        }
        else if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
            $errors["link_url"] = "Enter a valid link (must start with http:// or https://).";
        }

        if (count($errors) == 0) {

            $today = date("Y-m-d");
            $stmt = $conn->prepare("INSERT INTO materials (course_id, title, description, link_url, uploaded_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $id, $title, $description, $link_url, $today);
            $stmt->execute();
            $stmt->close();

            // notify every enrolled student
            $students = $conn->prepare("SELECT student_id FROM enrollments WHERE course_id = ? AND enroll_status = 'enrolled'");
            $students->bind_param("i", $id);
            $students->execute();
            $sres = $students->get_result();
            while ($row = $sres->fetch_assoc()) {
                addNotification($conn, $row["student_id"], "New material was uploaded in " . $course["title"] . ".", "course-details.php?id=" . $id);
            }
            $students->close();

            addActivity($conn, $me["user_id"], "Uploaded a new material in " . $course["title"]);
            $success = "Material added.";
        }
    }

    // ---------- post an announcement ----------

    if ($form == "post_announcement") {

        $message = trim($_POST["message"]);

        if ($message == "") {
            $errors["message"] = "Write a message first.";
        }
        else {

            $now = date("Y-m-d H:i:s");
            $stmt = $conn->prepare("INSERT INTO announcements (course_id, posted_by, message, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id, $me["user_id"], $message, $now);
            $stmt->execute();
            $stmt->close();

            $success = "Announcement posted.";
        }
    }
}

// ---------- remove a material (teacher owner only) ----------

if ($is_owner_teacher && isset($_GET["action"]) && $_GET["action"] == "remove_material") {

    $mid = intval($_GET["mid"]);

    $stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $mid, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: course-details.php?id=" . $id);
    exit();
}

// ---------- the material list (visible to owner teacher, admin, and enrolled students) ----------

$materials = null;

if ($is_owner_teacher || $is_admin || $is_enrolled) {
    $mstmt = $conn->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY uploaded_at DESC, material_id DESC");
    $mstmt->bind_param("i", $id);
    $mstmt->execute();
    $materials = $mstmt->get_result();
}

// ---------- enrolled student list (owner teacher and admin only) ----------

$students_result = null;

if ($is_owner_teacher || $is_admin) {
    $sstmt = $conn->prepare("SELECT u.user_id, u.name, u.email, e.enrolled_at
                             FROM enrollments e JOIN users u ON e.student_id = u.user_id
                             WHERE e.course_id = ? AND e.enroll_status = 'enrolled'
                             ORDER BY u.name ASC");
    $sstmt->bind_param("i", $id);
    $sstmt->execute();
    $students_result = $sstmt->get_result();
}

// ---------- this student's own grades for this course ----------

$my_grades = null;

if ($me["role"] == "student") {
    $gstmt = $conn->prepare("SELECT * FROM grades WHERE student_id = ? AND course_id = ? ORDER BY grade_id");
    $gstmt->bind_param("ii", $me["user_id"], $id);
    $gstmt->execute();
    $my_grades = $gstmt->get_result();
}

// ---------- announcements for this course ----------

$astmt = $conn->prepare("SELECT a.*, u.name AS poster_name FROM announcements a
                         JOIN users u ON a.posted_by = u.user_id
                         WHERE a.course_id = ? ORDER BY a.created_at DESC");
$astmt->bind_param("i", $id);
$astmt->execute();
$announcements = $astmt->get_result();

$page_title = $course["course_code"];
include "header.php";
?>

    <div class="page">

        <span class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></span>
        <h1><?php echo htmlspecialchars($course["title"]); ?></h1>
        <p class="page-info">
            Instructor : <?php echo htmlspecialchars($course["teacher_name"] ?? "Not assigned yet"); ?>
            &middot; <?php echo $course["credit_hour"]; ?> credit hours
            &middot; <?php echo countEnrolled($conn, $id); ?> / <?php echo $course["capacity"]; ?> seats filled
        </p>

        <?php if ($success != "") { ?>
            <div class="result"><?php echo $success; ?></div>
        <?php } ?>

        <div class="row">

            <div class="col-left">

                <!-- ---------- About ---------- -->

                <div class="box">
                    <h2 class="box-title">About This Course</h2>
                    <p class="item-text"><?php echo htmlspecialchars($course["description"]); ?></p>

                    <?php if ($me["role"] == "student" && !$is_enrolled) { ?>
                        <a href="courses.php?action=enroll&id=<?php echo $id; ?>" class="btn btn-green" style="margin-top:14px">Enroll in this course</a>
                    <?php } ?>
                </div>

                <!-- ---------- Materials ---------- -->

                <div class="box">
                    <h2 class="box-title">Course Materials</h2>

                    <?php if (!$is_owner_teacher && !$is_admin && !$is_enrolled) { ?>

                        <div class="empty">Enroll in this course to access the materials.</div>

                    <?php } else { ?>

                        <?php if ($materials->num_rows == 0) { ?>

                            <div class="empty">No material uploaded yet.</div>

                        <?php } else { while ($m = $materials->fetch_assoc()) { ?>

                            <div class="item">
                                <h3><a href="<?php echo htmlspecialchars($m["link_url"]); ?>" target="_blank"><?php echo htmlspecialchars($m["title"]); ?></a></h3>
                                <div class="item-info">Uploaded on <?php echo showDate($m["uploaded_at"]); ?></div>
                                <?php if ($m["description"] != "") { ?>
                                    <div class="item-text"><?php echo htmlspecialchars($m["description"]); ?></div>
                                <?php } ?>

                                <?php if ($is_owner_teacher) { ?>
                                    <button class="btn-small btn-red" onclick="if (confirm('Remove this material?')) { window.location.href='course-details.php?id=<?php echo $id; ?>&action=remove_material&mid=<?php echo $m["material_id"]; ?>'; }">Remove</button>
                                <?php } ?>
                            </div>

                        <?php } } ?>

                    <?php } ?>

                    <?php if ($is_owner_teacher) { ?>

                        <h3 style="margin-top:20px">Add New Material</h3>

                        <form method="POST" action="course-details.php?id=<?php echo $id; ?>">
                            <input type="hidden" name="form" value="add_material">

                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" placeholder="Example : Week 3 Slides - Sorting Algorithms">
                            <small class="error"><?php if (isset($errors["title"])) { echo $errors["title"]; } ?></small>

                            <label for="link_url">Link (Google Drive, YouTube, etc.)</label>
                            <input type="text" id="link_url" name="link_url" placeholder="https://...">
                            <small class="error"><?php if (isset($errors["link_url"])) { echo $errors["link_url"]; } ?></small>

                            <label for="description">Description (optional)</label>
                            <textarea id="description" name="description" placeholder="Short note about this material"></textarea>

                            <button type="submit" class="btn-full">Add Material</button>
                        </form>

                    <?php } ?>

                </div>

                <!-- ---------- My grades (student only) ---------- -->

                <?php if ($me["role"] == "student" && $is_enrolled) { ?>

                    <div class="box">
                        <h2 class="box-title">My Grades In This Course</h2>

                        <?php if ($my_grades->num_rows == 0) { ?>

                            <div class="empty">No grade has been posted yet.</div>

                        <?php } else { ?>

                            <table>
                                <tr><th>Component</th><th>Marks</th><th>Remarks</th><th>Date</th></tr>
                                <?php while ($g = $my_grades->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($g["component"]); ?></td>
                                        <td><?php echo $g["marks"]; ?></td>
                                        <td><?php echo htmlspecialchars($g["remarks"]); ?></td>
                                        <td><?php echo showDate($g["graded_at"]); ?></td>
                                    </tr>
                                <?php } ?>
                            </table>

                        <?php } ?>
                    </div>

                <?php } ?>

                <!-- ---------- Enrolled students (owner teacher / admin) ---------- -->

                <?php if ($is_owner_teacher || $is_admin) { ?>

                    <div class="box">
                        <h2 class="box-title">Enrolled Students</h2>

                        <?php if ($students_result->num_rows == 0) { ?>

                            <div class="empty">No student has enrolled yet.</div>

                        <?php } else { ?>

                            <table>
                                <tr><th>Name</th><th>Email</th><th>Enrolled On</th><?php if ($is_owner_teacher) { echo "<th>Action</th>"; } ?></tr>
                                <?php while ($s = $students_result->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s["name"]); ?></td>
                                        <td><?php echo htmlspecialchars($s["email"]); ?></td>
                                        <td><?php echo showDate($s["enrolled_at"]); ?></td>
                                        <?php if ($is_owner_teacher) { ?>
                                            <td><a href="add-grade.php?course_id=<?php echo $id; ?>&student_id=<?php echo $s["user_id"]; ?>" class="btn btn-small">Give Grade</a></td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </table>

                        <?php } ?>
                    </div>

                <?php } ?>

            </div>

            <div class="col-right">

                <!-- ---------- Announcements ---------- -->

                <div class="box">
                    <h2 class="box-title">Announcements</h2>

                    <?php if ($is_owner_teacher) { ?>

                        <form method="POST" action="course-details.php?id=<?php echo $id; ?>" style="margin-bottom:16px">
                            <input type="hidden" name="form" value="post_announcement">
                            <label for="message">Post a new announcement</label>
                            <textarea id="message" name="message" placeholder="Write something for your students..."></textarea>
                            <small class="error"><?php if (isset($errors["message"])) { echo $errors["message"]; } ?></small>
                            <button type="submit" class="btn-full btn-small">Post</button>
                        </form>

                    <?php } ?>

                    <?php if ($announcements->num_rows == 0) { ?>

                        <div class="empty">No announcement for this course yet.</div>

                    <?php } else { while ($a = $announcements->fetch_assoc()) { ?>

                        <div class="notif">
                            <div>
                                <div class="item-text"><?php echo htmlspecialchars($a["message"]); ?></div>
                                <span class="gray-text"><?php echo htmlspecialchars($a["poster_name"]); ?> &middot; <?php echo showDateTime($a["created_at"]); ?></span>
                            </div>
                        </div>

                    <?php } } ?>
                </div>

                <?php if ($me["role"] == "student" && $is_enrolled) { ?>
                    <div class="box">
                        <h2 class="box-title">Need To Leave This Course?</h2>
                        <p class="item-text">You can submit a drop request. It needs approval from your
                           teacher and then the admin.</p>
                        <a href="drop-request.php?course_id=<?php echo $id; ?>" class="btn btn-outline btn-full">Request To Drop</a>
                    </div>
                <?php } ?>

            </div>

        </div>

    </div>

<?php include "footer.php"; ?>
