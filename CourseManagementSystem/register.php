<?php
session_start();
require_once "config.php";
require_once "functions.php";

// somebody who is already logged in does not need this page
if (isset($_SESSION["user_logged_in"])) {
    header("Location: dashboard.php");
    exit();
}

$errors = array();

// these keep the old input, so the form is not empty after an error
$name       = "";
$email      = "";
$phone      = "";
$department = "";
$role       = "student";

// ============================================
// The form was submitted
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ---------- collect the input ----------

    $name       = trim($_POST["name"]);
    $email      = trim($_POST["email"]);
    $phone      = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $role       = $_POST["role"];
    $password   = $_POST["password"];
    $confirm    = $_POST["confirm"];

    // ---------- server side validation ----------

    if ($name == "") {
        $errors["name"] = "Name is required.";
    }
    else if (!preg_match("/^[A-Za-z. ]+$/", $name)) {
        $errors["name"] = "Only letters, dots and spaces are allowed in the name.";
    }

    if ($email == "") {
        $errors["email"] = "Email is required.";
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email address.";
    }
    else {

        // is this email already in the database?
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors["email"] = "This email is already registered.";
        }

        $stmt->close();
    }

    if ($phone == "") {
        $errors["phone"] = "Phone number is required.";
    }
    else if (!preg_match("/^01[3-9][0-9]{2}-?[0-9]{6}$/", $phone)) {
        $errors["phone"] = "Write a valid number like 01711-223344.";
    }

    if ($department == "") {
        $errors["department"] = "Department is required.";
    }

    // only student and teacher can self register, never admin
    $allowed_roles = array("student", "teacher");

    if (!in_array($role, $allowed_roles)) {
        $errors["role"] = "Please choose a valid account type.";
        $role = "student";
    }

    if ($password == "") {
        $errors["password"] = "Password is required.";
    }
    else if (strlen($password) < 6) {
        $errors["password"] = "Password must be at least 6 characters.";
    }

    if ($confirm != $password) {
        $errors["confirm"] = "The two passwords are not same.";
    }

    if (!isset($_POST["rules"])) {
        $errors["rules"] = "Please accept the rules.";
    }

    // ---------- if everything is correct, save the new user ----------

    if (count($errors) == 0) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $today = date("Y-m-d");

        $stmt = $conn->prepare("INSERT INTO users
            (name, email, password, role, phone, department, bio, status, joined)
            VALUES (?, ?, ?, ?, ?, ?, '', 'active', ?)");

        $stmt->bind_param("sssssss", $name, $email, $hashed_password, $role, $phone, $department, $today);
        $stmt->execute();

        $new_user_id = $conn->insert_id;      // the id MySQL created for us
        $stmt->close();

        addActivity($conn, $new_user_id, "Created a CourseHub account");
        addNotification($conn, $new_user_id, "Welcome to CourseHub! Complete your profile to get started.", "profile.php");

        // ---------- SESSION : log the new user in ----------

        $_SESSION["user_logged_in"] = true;
        $_SESSION["user_id"]        = $new_user_id;
        $_SESSION["user_name"]      = $name;
        $_SESSION["role"]           = $role;

        header("Location: dashboard.php");
        exit();
    }
}

$page_title = "Register";
include "header.php";
?>

    <div class="small-page">

        <div class="box">

            <h2 class="center">Create Your Account</h2>
            <p class="page-info center">Admin accounts are created by an existing admin only.</p>

            <form method="POST" action="register.php" id="registerForm">

                <!-- Name -->

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name"
                       value="<?php echo htmlspecialchars($name); ?>">
                <small id="nameError" class="error"><?php if (isset($errors["name"])) { echo $errors["name"]; } ?></small>

                <!-- Email -->

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="example@coursehub.com"
                       value="<?php echo htmlspecialchars($email); ?>">
                <small id="emailError" class="error"><?php if (isset($errors["email"])) { echo $errors["email"]; } ?></small>

                <!-- Phone -->

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="01711-223344"
                       value="<?php echo htmlspecialchars($phone); ?>">
                <small id="phoneError" class="error"><?php if (isset($errors["phone"])) { echo $errors["phone"]; } ?></small>

                <!-- Department -->

                <label for="department">Department</label>
                <input type="text" id="department" name="department" placeholder="Example : Computer Science"
                       value="<?php echo htmlspecialchars($department); ?>">
                <small id="departmentError" class="error"><?php if (isset($errors["department"])) { echo $errors["department"]; } ?></small>

                <!-- Account type -->

                <label>Account Type</label>

                <div class="radio-group">
                    <label>
                        <input type="radio" name="role" value="student" <?php if ($role == "student") { echo "checked"; } ?>>
                        Student
                    </label>
                    <label>
                        <input type="radio" name="role" value="teacher" <?php if ($role == "teacher") { echo "checked"; } ?>>
                        Teacher
                    </label>
                </div>

                <small id="roleError" class="error"><?php if (isset($errors["role"])) { echo $errors["role"]; } ?></small>

                <!-- Password -->

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 6 characters">
                <small id="passwordError" class="error"><?php if (isset($errors["password"])) { echo $errors["password"]; } ?></small>

                <!-- Confirm Password -->

                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" placeholder="Write the password again">
                <small id="confirmError" class="error"><?php if (isset($errors["confirm"])) { echo $errors["confirm"]; } ?></small>

                <!-- Rules -->

                <label>
                    <input type="checkbox" id="rules" name="rules" style="width:auto">
                    I agree to the rules of CourseHub.
                </label>

                <small id="rulesError" class="error"><?php if (isset($errors["rules"])) { echo $errors["rules"]; } ?></small>

                <button type="submit" class="btn-full">Register</button>

            </form>

            <p class="center" style="margin-top:15px">
                Already have an account? <a href="login.php">Login here</a>
            </p>

        </div>

    </div>

    <script src="register.js"></script>

<?php include "footer.php"; ?>
