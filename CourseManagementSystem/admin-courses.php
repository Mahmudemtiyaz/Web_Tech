<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

// ---------- delete a course ----------

if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {

    $id = intval($_GET["id"]);

    // ON DELETE CASCADE removes its enrollments, materials, grades and drop requests too
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    addActivity($conn, $me["user_id"], "Deleted a course");

    header("Location: admin-courses.php");
    exit();
}

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

$sql = "SELECT c.*, u.name AS teacher_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.enroll_status = 'enrolled') AS enrolled
        FROM courses c LEFT JOIN users u ON c.teacher_id = u.user_id WHERE 1 = 1";

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

$page_title = "Manage Courses";
include "header.php";
?>

    <div class="page">

        <div class="row" style="align-items:center;justify-content:space-between">
            <h1>Manage Courses</h1>
            <a href="add-course.php" class="btn">+ Add New Course</a>
        </div>
        <p class="page-info">Every course on CourseHub.</p>

        <div class="box">
            <form method="GET" action="admin-courses.php">
                <div class="row">
                    <div class="col-left">
                        <label for="search">Search</label>
                        <input type="search" id="search" name="search" placeholder="Search by title or code"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-right" style="display:flex;align-items:flex-end">
                        <div class="buttons" style="margin-top:0">
                            <button type="submit">Search</button>
                            <button type="button" class="btn-gray" onclick="window.location.href='admin-courses.php'">Clear</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">No course found.</div>

        <?php } else { ?>

            <table>
                <tr><th>Code</th><th>Title</th><th>Teacher</th><th>Seats</th><th>Status</th><th>Action</th></tr>

                <?php while ($c = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c["course_code"]); ?></td>
                        <td><a href="course-details.php?id=<?php echo $c["course_id"]; ?>"><?php echo htmlspecialchars($c["title"]); ?></a></td>
                        <td><?php echo htmlspecialchars($c["teacher_name"] ?? "Not assigned"); ?></td>
                        <td><?php echo $c["enrolled"]; ?> / <?php echo $c["capacity"]; ?></td>
                        <td><?php echo showStatus($c["course_status"]); ?></td>
                        <td>
                            <a href="edit-course.php?id=<?php echo $c["course_id"]; ?>" class="btn btn-small">Edit</a>
                            <button class="btn-small btn-red" onclick="if (confirm('Delete this course? This also removes its enrollments, materials and grades.')) { window.location.href='admin-courses.php?action=delete&id=<?php echo $c["course_id"]; ?>'; }">Delete</button>
                        </td>
                    </tr>
                <?php } ?>

            </table>

        <?php } ?>

    </div>

<?php
$stmt->close();
include "footer.php";
?>
