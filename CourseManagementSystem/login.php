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

// ---------- COOKIE : the email of the last login is remembered ----------

$email = "";

if (isset($_COOKIE["remember_email"])) {
    $email = $_COOKIE["remember_email"];
}

// ============================================
// The form was submitted
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    // ---------- validation ----------

    if ($email == "") {
        $errors["email"] = "Email is required.";
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email address.";
    }

    if ($password == "") {
        $errors["password"] = "Password is required.";
    }

    if (count($errors) == 0) {

        // ---------- remember the email in a cookie for 30 days ----------

        if (isset($_POST["remember"])) {
            setcookie("remember_email", $email, time() + (30 * 24 * 60 * 60), "/");
        }
        else {
            // the box is not ticked, so delete the old cookie
            setcookie("remember_email", "", time() - 3600, "/");
        }

        // ---------- one users table, one lookup for every role ----------

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user == null) {
            $errors["email"] = "No account found with this email.";
        }
        else if (!password_verify($password, $user["password"])) {
            $errors["password"] = "Wrong password. Please try again.";
        }
        else if ($user["status"] == "suspended") {
            $errors["email"] = "This account has been suspended by the admin.";
        }
        else {

            // ---------- SESSION : the login is successful ----------

            $_SESSION["user_logged_in"] = true;
            $_SESSION["user_id"]        = $user["user_id"];
            $_SESSION["user_name"]      = $user["name"];
            $_SESSION["role"]           = $user["role"];

            addActivity($conn, $user["user_id"], "Logged in to CourseHub");

            if ($user["role"] == "admin") {
                header("Location: admin-dashboard.php");
            }
            else {
                header("Location: dashboard.php");
            }
            exit();
        }
    }
}

$page_title = "Login";
include "header.php";
?>

    <div class="small-page">

        <div class="box">

            <h2 class="center">Login to CourseHub</h2>

            <!--<div class="result">
                <b>Demo accounts</b><br>
                Admin : admin@coursehub.com &nbsp; password : admin123<br>
                Teacher : rahim@coursehub.com &nbsp; password : 123456<br>
                Student : tasfir@coursehub.com &nbsp; password : 123456
            </div>-->

            <form method="POST" action="login.php" id="loginForm">

                <!-- Email -->

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="example@coursehub.com"
                       value="<?php echo htmlspecialchars($email); ?>">
                <small id="emailError" class="error"><?php if (isset($errors["email"])) { echo $errors["email"]; } ?></small>

                <!-- Password -->

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password">
                <small id="passwordError" class="error"><?php if (isset($errors["password"])) { echo $errors["password"]; } ?></small>

                <!-- Remember me : this makes the cookie -->

                <label>
                    <input type="checkbox" name="remember" style="width:auto"
                           <?php if ($email != "") { echo "checked"; } ?>>
                    Remember my email in this browser
                </label>

                <button type="submit" class="btn-full">Login</button>

            </form>

            <p class="center" style="margin-top:15px">
                <a href="forgot.php">Forgot password?</a> &nbsp; | &nbsp;
                <a href="register.php">Create a new account</a>
            </p>

        </div>

    </div>

    <script src="login.js"></script>

<?php include "footer.php"; ?>
