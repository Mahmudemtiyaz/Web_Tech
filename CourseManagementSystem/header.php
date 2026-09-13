<?php
// ============================================
// The top part of every page (include file).
// The menu is different for a visitor, a student, a teacher and an admin,
// and PHP decides it from the SESSION.
// ============================================

if (!isset($page_title)) {
    $page_title = "CourseHub";
}

$logged_in = isset($_SESSION["user_logged_in"]) && $_SESSION["user_logged_in"] === true;
$role      = $logged_in ? $_SESSION["role"] : "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CourseHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Header -->

    <div class="header">
        <div class="container">

            <h2 class="logo">
                <a href="<?php echo $logged_in ? 'dashboard.php' : 'index.php'; ?>">
                    Course<span>Hub</span>
                </a>
                <?php if ($logged_in) { echo showRole($role); } ?>
            </h2>

            <div class="menu">

            <?php if ($role == "student") { ?>

                <a href="dashboard.php">Dashboard</a>
                <a href="courses.php">Browse Courses</a>
                <a href="my-courses.php">My Courses</a>
                <a href="grades.php">My Grades</a>
                <a href="drop-requests.php">Drop Requests</a>
                <a href="notifications.php" class="bell">Notifications
                    <?php $unread = countUnread($conn, $me["user_id"]);
                          if ($unread > 0) { echo "<span class='bell-count' id='bellCount'>" . $unread . "</span>"; } ?>
                </a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>

            <?php } else if ($role == "teacher") { ?>

                <a href="dashboard.php">Dashboard</a>
                <a href="my-courses.php">My Courses</a>
                <a href="grades.php">Grades</a>
                <a href="drop-requests.php">Drop Requests</a>
                <a href="notifications.php" class="bell">Notifications
                    <?php $unread = countUnread($conn, $me["user_id"]);
                          if ($unread > 0) { echo "<span class='bell-count' id='bellCount'>" . $unread . "</span>"; } ?>
                </a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>

            <?php } else if ($role == "admin") { ?>

                <a href="admin-dashboard.php">Overview</a>
                <a href="admin-users.php">Users</a>
                <a href="admin-courses.php">Courses</a>
                <a href="admin-drop-requests.php">Drop Requests</a>
                <a href="admin-reports.php">Reports</a>
                <a href="notifications.php" class="bell">Notifications
                    <?php $unread = countUnread($conn, $me["user_id"]);
                          if ($unread > 0) { echo "<span class='bell-count' id='bellCount'>" . $unread . "</span>"; } ?>
                </a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>

            <?php } else { ?>

                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>

            <?php } ?>

            </div>

        </div>
    </div>
