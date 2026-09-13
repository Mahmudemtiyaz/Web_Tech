<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

$errors  = array();
$success = "";

// ============================================
// Update profile information
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form"]) && $_POST["form"] == "update_profile") {

    $name       = trim($_POST["name"]);
    $phone      = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $bio        = trim($_POST["bio"]);

    if ($name == "") {
        $errors["name"] = "Name is required.";
    }
    else if (!preg_match("/^[A-Za-z. ]+$/", $name)) {
        $errors["name"] = "Only letters, dots and spaces are allowed in the name.";
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

    if (count($errors) == 0) {

        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, department = ?, bio = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $department, $bio, $me["user_id"]);
        $stmt->execute();
        $stmt->close();

        $_SESSION["user_name"] = $name;
        addActivity($conn, $me["user_id"], "Updated profile information");

        $me = getUser($conn, $me["user_id"]);
        $success = "Your profile has been updated.";
    }
}

// ============================================
// Change password
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form"]) && $_POST["form"] == "change_password") {

    $current = $_POST["current_password"];
    $new     = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];

    if (!password_verify($current, $me["password"])) {
        $errors["current_password"] = "Your current password is wrong.";
    }

    if ($new == "") {
        $errors["new_password"] = "New password is required.";
    }
    else if (strlen($new) < 6) {
        $errors["new_password"] = "Password must be at least 6 characters.";
    }

    if ($confirm != $new) {
        $errors["confirm_password"] = "The two passwords are not same.";
    }

    if (count($errors) == 0) {

        $hashed = password_hash($new, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $me["user_id"]);
        $stmt->execute();
        $stmt->close();

        addActivity($conn, $me["user_id"], "Changed account password");
        $success = "Your password has been changed.";
    }
}

// ============================================
// Delete my own account
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form"]) && $_POST["form"] == "delete_account") {

    $confirm_password = $_POST["delete_password"];

    if (!password_verify($confirm_password, $me["password"])) {
        $errors["delete_password"] = "Wrong password. Account was not deleted.";
    }
    else {

        // ON DELETE CASCADE removes enrollments, grades, drop requests, notifications etc. automatically
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $me["user_id"]);
        $stmt->execute();
        $stmt->close();

        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

$page_title = "My Profile";
include "header.php";
?>

    <div class="medium-page">

        <div class="box center">
            <div class="avatar-circle"><?php echo strtoupper(substr($me["name"], 0, 1)); ?></div>
            <h2><?php echo htmlspecialchars($me["name"]); ?></h2>
            <p class="gray-text"><?php echo htmlspecialchars($me["email"]); ?></p>
            <?php echo showRole($me["role"]); ?>
        </div>

        <?php if ($success != "") { ?>
            <div class="result"><?php echo $success; ?></div>
        <?php } ?>

        <!-- ---------- Edit profile ---------- -->

        <div class="box">
            <h2 class="box-title">Edit Profile</h2>

            <form method="POST" action="profile.php">
                <input type="hidden" name="form" value="update_profile">

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($me["name"]); ?>">
                <small class="error"><?php if (isset($errors["name"])) { echo $errors["name"]; } ?></small>

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($me["phone"]); ?>">
                <small class="error"><?php if (isset($errors["phone"])) { echo $errors["phone"]; } ?></small>

                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($me["department"]); ?>">
                <small class="error"><?php if (isset($errors["department"])) { echo $errors["department"]; } ?></small>

                <label for="bio">Short Bio</label>
                <textarea id="bio" name="bio" placeholder="Tell us a little about yourself"><?php echo htmlspecialchars($me["bio"]); ?></textarea>

                <button type="submit" class="btn-full">Save Changes</button>
            </form>
        </div>

        <!-- ---------- Change password ---------- -->

        <div class="box">
            <h2 class="box-title">Change Password</h2>

            <form method="POST" action="profile.php">
                <input type="hidden" name="form" value="change_password">

                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password">
                <small class="error"><?php if (isset($errors["current_password"])) { echo $errors["current_password"]; } ?></small>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">
                <small class="error"><?php if (isset($errors["new_password"])) { echo $errors["new_password"]; } ?></small>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <small class="error"><?php if (isset($errors["confirm_password"])) { echo $errors["confirm_password"]; } ?></small>

                <button type="submit" class="btn-full">Change Password</button>
            </form>
        </div>

        <!-- ---------- Delete account ---------- -->

        <div class="box">
            <h2 class="box-title">Delete My Account</h2>
            <p class="item-text">This permanently deletes your account and everything tied to it
               (enrollments, grades, drop requests). This cannot be undone.</p>

            <form method="POST" action="profile.php" onsubmit="return confirm('This will permanently delete your account. Continue?');">
                <input type="hidden" name="form" value="delete_account">

                <label for="delete_password">Confirm Your Password</label>
                <input type="password" id="delete_password" name="delete_password">
                <small class="error"><?php if (isset($errors["delete_password"])) { echo $errors["delete_password"]; } ?></small>

                <button type="submit" class="btn-full btn-red">Delete My Account</button>
            </form>
        </div>

    </div>

<?php include "footer.php"; ?>
