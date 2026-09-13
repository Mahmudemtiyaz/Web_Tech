<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

$id     = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$course = getCourse($conn, $id);

if ($course == null) {
    header("Location: admin-courses.php");
    exit();
}

$errors = array();

$title         = $course["title"];
$description   = $course["description"];
$credit_hour   = $course["credit_hour"];
$teacher_id    = $course["teacher_id"];
$capacity      = $course["capacity"];
$course_status = $course["course_status"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title         = trim($_POST["title"]);
    $description   = trim($_POST["description"]);
    $credit_hour   = trim($_POST["credit_hour"]);
    $teacher_id    = trim($_POST["teacher_id"]);
    $capacity      = trim($_POST["capacity"]);
    $course_status = $_POST["course_status"];

    if ($title == "") {
        $errors["title"] = "Title is required.";
    }

    if ($description == "") {
        $errors["description"] = "Description is required.";
    }

    if (!is_numeric($credit_hour) || $credit_hour <= 0) {
        $errors["credit_hour"] = "Enter a valid credit hour.";
    }

    if (!is_numeric($capacity) || $capacity <= 0) {
        $errors["capacity"] = "Enter a valid capacity.";
    }
    else if ($capacity < countEnrolled($conn, $id)) {
        $errors["capacity"] = "Capacity cannot be less than the " . countEnrolled($conn, $id) . " students already enrolled.";
    }

    if (!in_array($course_status, array("active", "closed"))) {
        $course_status = "active";
    }

    $teacher_id_to_save = null;

    if ($teacher_id != "") {

        $tcheck = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'teacher'");
        $tcheck->bind_param("i", $teacher_id);
        $tcheck->execute();

        if ($tcheck->get_result()->num_rows == 0) {
            $errors["teacher_id"] = "Choose a valid teacher.";
        }
        else {
            $teacher_id_to_save = intval($teacher_id);
        }

        $tcheck->close();
    }

    if (count($errors) == 0) {

        $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, credit_hour = ?, teacher_id = ?, capacity = ?, course_status = ? WHERE course_id = ?");
        $stmt->bind_param("ssdiisi", $title, $description, $credit_hour, $teacher_id_to_save, $capacity, $course_status, $id);
        $stmt->execute();
        $stmt->close();

        addActivity($conn, $me["user_id"], "Edited the course : " . $title);

        // let the newly assigned teacher know, only if it actually changed
        if ($teacher_id_to_save != null && $teacher_id_to_save != $course["teacher_id"]) {
            addNotification($conn, $teacher_id_to_save, "You have been assigned to teach " . $title . ".", "course-details.php?id=" . $id);
        }

        header("Location: admin-courses.php");
        exit();
    }
}

$teachers = $conn->query("SELECT user_id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");

$page_title = "Edit Course";
include "header.php";
?>

    <div class="medium-page">

        <div class="box">

            <h2 class="center">Edit Course</h2>
            <p class="page-info center"><?php echo htmlspecialchars($course["course_code"]); ?> (course code cannot be changed)</p>

            <form method="POST" action="edit-course.php?id=<?php echo $id; ?>">

                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
                <small class="error"><?php if (isset($errors["title"])) { echo $errors["title"]; } ?></small>

                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
                <small class="error"><?php if (isset($errors["description"])) { echo $errors["description"]; } ?></small>

                <div class="row">
                    <div class="col-half">
                        <label for="credit_hour">Credit Hours</label>
                        <input type="number" id="credit_hour" name="credit_hour" step="0.5" value="<?php echo htmlspecialchars($credit_hour); ?>">
                        <small class="error"><?php if (isset($errors["credit_hour"])) { echo $errors["credit_hour"]; } ?></small>
                    </div>
                    <div class="col-half">
                        <label for="capacity">Seat Capacity</label>
                        <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($capacity); ?>">
                        <small class="error"><?php if (isset($errors["capacity"])) { echo $errors["capacity"]; } ?></small>
                    </div>
                </div>

                <label for="teacher_id">Assigned Teacher</label>
                <select id="teacher_id" name="teacher_id">
                    <option value="">-- Not assigned --</option>
                    <?php while ($t = $teachers->fetch_assoc()) { ?>
                        <option value="<?php echo $t["user_id"]; ?>" <?php if ($teacher_id == $t["user_id"]) { echo "selected"; } ?>>
                            <?php echo htmlspecialchars($t["name"]); ?>
                        </option>
                    <?php } ?>
                </select>
                <small class="error"><?php if (isset($errors["teacher_id"])) { echo $errors["teacher_id"]; } ?></small>

                <label for="course_status">Status</label>
                <select id="course_status" name="course_status">
                    <option value="active" <?php if ($course_status == "active") { echo "selected"; } ?>>Active</option>
                    <option value="closed" <?php if ($course_status == "closed") { echo "selected"; } ?>>Closed</option>
                </select>

                <div class="buttons">
                    <button type="submit">Save Changes</button>
                    <a href="admin-courses.php" class="btn btn-gray">Cancel</a>
                </div>

            </form>

        </div>

    </div>

<?php include "footer.php"; ?>
