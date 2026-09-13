<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

$errors = array();

$course_code = "";
$title       = "";
$description = "";
$credit_hour = "3.0";
$teacher_id  = "";
$capacity    = "40";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $course_code = strtoupper(trim($_POST["course_code"]));
    $title       = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $credit_hour = trim($_POST["credit_hour"]);
    $teacher_id  = trim($_POST["teacher_id"]);
    $capacity    = trim($_POST["capacity"]);

    if ($course_code == "") {
        $errors["course_code"] = "Course code is required.";
    }
    else {
        $check = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?");
        $check->bind_param("s", $course_code);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors["course_code"] = "This course code already exists.";
        }
        $check->close();
    }

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

    // teacher_id is optional (can be left unassigned), but if it's set it must be a real teacher
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

        $today = date("Y-m-d");

        $stmt = $conn->prepare("INSERT INTO courses (course_code, title, description, credit_hour, teacher_id, capacity, course_status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->bind_param("sssdiis", $course_code, $title, $description, $credit_hour, $teacher_id_to_save, $capacity, $today);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        addActivity($conn, $me["user_id"], "Created a new course : " . $title);

        if ($teacher_id_to_save != null) {
            addNotification($conn, $teacher_id_to_save, "You have been assigned to teach " . $title . ".", "course-details.php?id=" . $new_id);
        }

        header("Location: admin-courses.php");
        exit();
    }
}

$teachers = $conn->query("SELECT user_id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");

$page_title = "Add Course";
include "header.php";
?>

    <div class="medium-page">

        <div class="box">

            <h2 class="center">Add A New Course</h2>

            <form method="POST" action="add-course.php">

                <label for="course_code">Course Code</label>
                <input type="text" id="course_code" name="course_code" placeholder="Example : CSC330"
                       value="<?php echo htmlspecialchars($course_code); ?>">
                <small class="error"><?php if (isset($errors["course_code"])) { echo $errors["course_code"]; } ?></small>

                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="Example : Operating Systems"
                       value="<?php echo htmlspecialchars($title); ?>">
                <small class="error"><?php if (isset($errors["title"])) { echo $errors["title"]; } ?></small>

                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="What is this course about?"><?php echo htmlspecialchars($description); ?></textarea>
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

                <label for="teacher_id">Assign A Teacher (optional)</label>
                <select id="teacher_id" name="teacher_id">
                    <option value="">-- Not assigned yet --</option>
                    <?php while ($t = $teachers->fetch_assoc()) { ?>
                        <option value="<?php echo $t["user_id"]; ?>" <?php if ($teacher_id == $t["user_id"]) { echo "selected"; } ?>>
                            <?php echo htmlspecialchars($t["name"]); ?>
                        </option>
                    <?php } ?>
                </select>
                <small class="error"><?php if (isset($errors["teacher_id"])) { echo $errors["teacher_id"]; } ?></small>

                <div class="buttons">
                    <button type="submit">Create Course</button>
                    <a href="admin-courses.php" class="btn btn-gray">Cancel</a>
                </div>

            </form>

        </div>

    </div>

<?php include "footer.php"; ?>
