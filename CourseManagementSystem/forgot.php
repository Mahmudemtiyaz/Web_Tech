<?php
session_start();
require_once "config.php";
require_once "functions.php";

if (isset($_SESSION["user_logged_in"])) {
    header("Location: dashboard.php");
    exit();
}

$errors  = array();
$success = false;
$email   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm"];

    if ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Enter a valid email address.";
    }
    else {

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user == null) {
            $errors["email"] = "No account found with this email.";
        }
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

    if (count($errors) == 0) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}

$page_title = "Forgot Password";
include "header.php";
?>

    <div class="small-page">

        <div class="box">

            <h2 class="center">Reset Your Password</h2>
            <p class="page-info center">A real system would email you a reset link. For this class
               project, confirm your email and set a new password directly.</p>

            <?php if ($success) { ?>

                <div class="result">
                    Your password has been updated. You can now <a href="login.php">login</a> with
                    the new password.
                </div>

            <?php } else { ?>

                <form method="POST" action="forgot.php">

                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="example@coursehub.com"
                           value="<?php echo htmlspecialchars($email); ?>">
                    <small class="error"><?php if (isset($errors["email"])) { echo $errors["email"]; } ?></small>

                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters">
                    <small class="error"><?php if (isset($errors["password"])) { echo $errors["password"]; } ?></small>

                    <label for="confirm">Confirm New Password</label>
                    <input type="password" id="confirm" name="confirm" placeholder="Write the password again">
                    <small class="error"><?php if (isset($errors["confirm"])) { echo $errors["confirm"]; } ?></small>

                    <button type="submit" class="btn-full">Update Password</button>

                </form>

            <?php } ?>

            <p class="center" style="margin-top:15px">
                <a href="login.php">Back to login</a>
            </p>

        </div>

    </div>

<?php include "footer.php"; ?>
