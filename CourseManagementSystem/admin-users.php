<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

$errors  = array();
$success = "";

// ============================================
// Add a new user (admin can create any role, including another admin)
// ============================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form"]) && $_POST["form"] == "add_user") {

    $name       = trim($_POST["name"]);
    $email      = trim($_POST["email"]);
    $phone      = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $role       = $_POST["role"];
    $password   = $_POST["password"];

    if ($name == "") {
        $errors["name"] = "Name is required.";
    }

    if ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Enter a valid email address.";
    }
    else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors["email"] = "This email is already registered.";
        }
        $check->close();
    }

    if ($phone == "") {
        $errors["phone"] = "Phone number is required.";
    }

    if ($department == "") {
        $errors["department"] = "Department is required.";
    }

    if (!in_array($role, array("admin", "teacher", "student"))) {
        $errors["role"] = "Choose a valid role.";
    }

    if ($password == "" || strlen($password) < 6) {
        $errors["password"] = "Password must be at least 6 characters.";
    }

    if (count($errors) == 0) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $today  = date("Y-m-d");

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, department, bio, status, joined)
                                VALUES (?, ?, ?, ?, ?, ?, '', 'active', ?)");
        $stmt->bind_param("sssssss", $name, $email, $hashed, $role, $phone, $department, $today);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        addNotification($conn, $new_id, "An admin created an account for you on CourseHub. Please log in and update your profile.", "profile.php");
        addActivity($conn, $me["user_id"], "Created a new " . $role . " account : " . $name);

        $success = "New " . $role . " account created for " . htmlspecialchars($name) . ".";
    }
}

// ============================================
// Suspend / activate / delete
// ============================================

if (isset($_GET["action"]) && isset($_GET["id"])) {

    $uid    = intval($_GET["id"]);
    $action = $_GET["action"];

    if ($uid != $me["user_id"]) {   // an admin cannot suspend or delete themselves by accident

        if ($action == "suspend") {
            $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        }
        else if ($action == "activate") {
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        }
        else if ($action == "delete") {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: admin-users.php");
    exit();
}

// ============================================
// List with search and role filter
// ============================================

$search      = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$role_filter = isset($_GET["role"])   ? $_GET["role"]         : "";

$sql = "SELECT * FROM users WHERE 1 = 1";

if ($search != "") {
    $sql = $sql . " AND (name LIKE ? OR email LIKE ?)";
}

if (in_array($role_filter, array("admin", "teacher", "student"))) {
    $sql = $sql . " AND role = ?";
}

$sql = $sql . " ORDER BY user_id DESC";

$stmt = $conn->prepare($sql);

if ($search != "" && $role_filter != "") {
    $like = "%" . $search . "%";
    $stmt->bind_param("sss", $like, $like, $role_filter);
}
else if ($search != "") {
    $like = "%" . $search . "%";
    $stmt->bind_param("ss", $like, $like);
}
else if ($role_filter != "") {
    $stmt->bind_param("s", $role_filter);
}

$stmt->execute();
$result = $stmt->get_result();

$page_title = "Manage Users";
include "header.php";
?>

    <div class="page">

        <h1>Manage Users</h1>
        <p class="page-info">Every account on CourseHub - admins, teachers and students.</p>

        <?php if ($success != "") { ?>
            <div class="result"><?php echo $success; ?></div>
        <?php } ?>

        <div class="row">

            <div class="col-left">

                <div class="box">

                    <form method="GET" action="admin-users.php" class="row" style="align-items:flex-end">
                        <div class="col-half">
                            <label for="search">Search</label>
                            <input type="search" id="search" name="search" placeholder="Search by name or email"
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-half">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <option value="">All roles</option>
                                <option value="admin"   <?php if ($role_filter == "admin")   { echo "selected"; } ?>>Admin</option>
                                <option value="teacher" <?php if ($role_filter == "teacher") { echo "selected"; } ?>>Teacher</option>
                                <option value="student" <?php if ($role_filter == "student") { echo "selected"; } ?>>Student</option>
                            </select>
                        </div>
                        <div class="buttons" style="margin-top:0">
                            <button type="submit">Filter</button>
                            <button type="button" class="btn-gray" onclick="window.location.href='admin-users.php'">Clear</button>
                        </div>
                    </form>

                </div>

                <div class="box">

                    <?php if ($result->num_rows == 0) { ?>

                        <div class="empty">No user found.</div>

                    <?php } else { ?>

                        <table>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr>

                            <?php while ($u = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["email"]); ?></td>
                                    <td><?php echo showRole($u["role"]); ?></td>
                                    <td><?php echo showStatus($u["status"]); ?></td>
                                    <td>

                                        <?php if ($u["user_id"] != $me["user_id"]) { ?>

                                            <?php if ($u["status"] == "active") { ?>
                                                <a href="admin-users.php?action=suspend&id=<?php echo $u["user_id"]; ?>" class="btn btn-small btn-gray">Suspend</a>
                                            <?php } else { ?>
                                                <a href="admin-users.php?action=activate&id=<?php echo $u["user_id"]; ?>" class="btn btn-small btn-green">Activate</a>
                                            <?php } ?>

                                            <button class="btn-small btn-red" onclick="if (confirm('Permanently delete this account?')) { window.location.href='admin-users.php?action=delete&id=<?php echo $u["user_id"]; ?>'; }">Delete</button>

                                        <?php } else { ?>
                                            <span class="gray-text">This is you</span>
                                        <?php } ?>

                                    </td>
                                </tr>
                            <?php } ?>

                        </table>

                    <?php } ?>

                </div>

            </div>

            <div class="col-right">

                <div class="box">
                    <h2 class="box-title">Add A New User</h2>

                    <form method="POST" action="admin-users.php">
                        <input type="hidden" name="form" value="add_user">

                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name">
                        <small class="error"><?php if (isset($errors["name"])) { echo $errors["name"]; } ?></small>

                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                        <small class="error"><?php if (isset($errors["email"])) { echo $errors["email"]; } ?></small>

                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" placeholder="01711-223344">
                        <small class="error"><?php if (isset($errors["phone"])) { echo $errors["phone"]; } ?></small>

                        <label for="department">Department</label>
                        <input type="text" id="department" name="department">
                        <small class="error"><?php if (isset($errors["department"])) { echo $errors["department"]; } ?></small>

                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                        <small class="error"><?php if (isset($errors["role"])) { echo $errors["role"]; } ?></small>

                        <label for="password">Temporary Password</label>
                        <input type="password" id="password" name="password">
                        <small class="error"><?php if (isset($errors["password"])) { echo $errors["password"]; } ?></small>

                        <button type="submit" class="btn-full">Create Account</button>
                    </form>
                </div>

            </div>

        </div>

    </div>

<?php
$stmt->close();
include "footer.php";
?>
