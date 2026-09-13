<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

$total_users    = countAll($conn, "SELECT COUNT(*) AS total FROM users");
$total_teachers = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'");
$total_students = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
$total_courses  = countAll($conn, "SELECT COUNT(*) AS total FROM courses");
$total_enrolled = countAll($conn, "SELECT COUNT(*) AS total FROM enrollments WHERE enroll_status = 'enrolled'");
$pending_final  = countAll($conn, "SELECT COUNT(*) AS total FROM drop_requests WHERE teacher_status = 'approved' AND admin_status = 'pending'");

// latest activity across the whole system
$activity = $conn->query("SELECT a.*, u.name, u.role FROM activities a
                          JOIN users u ON a.user_id = u.user_id
                          ORDER BY a.created_at DESC LIMIT 8");

// courses that still need a teacher assigned
$unassigned = $conn->query("SELECT * FROM courses WHERE teacher_id IS NULL ORDER BY course_code ASC");

$page_title = "Admin Overview";
include "header.php";
?>

    <div class="page">

        <h1>Admin Overview</h1>
        <p class="page-info">A snapshot of everything happening on CourseHub.</p>

        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-name">Total Users</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_teachers; ?></div>
                <div class="stat-name">Teachers</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-name">Students</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-name">Courses</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_enrolled; ?></div>
                <div class="stat-name">Active Enrollments</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $pending_final; ?></div>
                <div class="stat-name">Drop Requests To Finalize</div>
            </div>
        </div>

        <div class="row">

            <div class="col-left">

                <div class="box">
                    <h2 class="box-title">Recent Activity</h2>

                    <?php if ($activity->num_rows == 0) { ?>

                        <div class="empty">No activity recorded yet.</div>

                    <?php } else { while ($a = $activity->fetch_assoc()) { ?>

                        <div class="notif">
                            <div>
                                <div class="item-text">
                                    <?php echo htmlspecialchars($a["name"]); ?> <?php echo showRole($a["role"]); ?>
                                </div>
                                <span class="gray-text"><?php echo htmlspecialchars($a["text"]); ?> &middot; <?php echo showDateTime($a["created_at"]); ?></span>
                            </div>
                        </div>

                    <?php } } ?>
                </div>

            </div>

            <div class="col-right">

                <div class="box">
                    <h2 class="box-title">Quick Actions</h2>
                    <div class="buttons" style="flex-direction:column">
                        <a href="add-course.php" class="btn btn-full">+ Add New Course</a>
                        <a href="admin-users.php" class="btn btn-outline btn-full">Manage Users</a>
                        <a href="admin-drop-requests.php" class="btn btn-outline btn-full">Review Drop Requests</a>
                    </div>
                </div>

                <?php if ($unassigned->num_rows > 0) { ?>
                    <div class="box">
                        <h2 class="box-title">Needs A Teacher</h2>
                        <?php while ($c = $unassigned->fetch_assoc()) { ?>
                            <div class="item item-orange">
                                <span class="course-code"><?php echo htmlspecialchars($c["course_code"]); ?></span>
                                <h3><?php echo htmlspecialchars($c["title"]); ?></h3>
                                <a href="edit-course.php?id=<?php echo $c["course_id"]; ?>" class="btn btn-small">Assign Teacher</a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>

        </div>

    </div>

<?php include "footer.php"; ?>
