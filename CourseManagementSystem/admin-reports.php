<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

// ---------- students per course (for a simple bar chart) ----------

$per_course = $conn->query("SELECT c.course_code, c.title,
                            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.enroll_status = 'enrolled') AS enrolled,
                            c.capacity
                            FROM courses c ORDER BY enrolled DESC");

$max_enrolled = 1;
$rows = array();
while ($row = $per_course->fetch_assoc()) {
    $rows[] = $row;
    if ($row["enrolled"] > $max_enrolled) { $max_enrolled = $row["enrolled"]; }
}

// ---------- overall numbers ----------

$total_users     = countAll($conn, "SELECT COUNT(*) AS total FROM users");
$active_users    = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'");
$suspended_users = countAll($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'suspended'");

$total_requests    = countAll($conn, "SELECT COUNT(*) AS total FROM drop_requests");
$approved_requests = countAll($conn, "SELECT COUNT(*) AS total FROM drop_requests WHERE admin_status = 'approved'");
$rejected_requests = countAll($conn, "SELECT COUNT(*) AS total FROM drop_requests WHERE admin_status = 'rejected' OR teacher_status = 'rejected'");
$pending_requests  = countAll($conn, "SELECT COUNT(*) AS total FROM drop_requests WHERE admin_status = 'pending' AND teacher_status != 'rejected'");

$total_materials = countAll($conn, "SELECT COUNT(*) AS total FROM materials");
$total_grades    = countAll($conn, "SELECT COUNT(*) AS total FROM grades");

$page_title = "Reports";
include "header.php";
?>

    <div class="page">

        <h1>Reports</h1>
        <p class="page-info">A quick statistical summary of the whole system.</p>

        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?php echo $active_users; ?></div>
                <div class="stat-name">Active Users</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $suspended_users; ?></div>
                <div class="stat-name">Suspended Users</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_materials; ?></div>
                <div class="stat-name">Materials Uploaded</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $total_grades; ?></div>
                <div class="stat-name">Grade Entries</div>
            </div>
        </div>

        <div class="row">

            <div class="col-left">

                <div class="box">
                    <h2 class="box-title">Enrollment Per Course</h2>

                    <?php foreach ($rows as $r) {
                        $width = round(($r["enrolled"] / $max_enrolled) * 100);
                        if ($width < 4 && $r["enrolled"] > 0) { $width = 4; }
                        ?>

                        <div style="margin-bottom:14px">
                            <div class="row" style="justify-content:space-between;margin-bottom:4px">
                                <span><?php echo htmlspecialchars($r["course_code"]); ?> - <?php echo htmlspecialchars($r["title"]); ?></span>
                                <span class="gray-text"><?php echo $r["enrolled"]; ?> / <?php echo $r["capacity"]; ?></span>
                            </div>
                            <div style="background:#eef1f7;border-radius:20px;height:12px;overflow:hidden">
                                <div style="background:linear-gradient(90deg, #4f46e5, #14b8a6); height:100%; width:<?php echo $width; ?>%;"></div>
                            </div>
                        </div>

                    <?php } ?>
                </div>

            </div>

            <div class="col-right">

                <div class="box">
                    <h2 class="box-title">Drop Requests</h2>

                    <p class="item-text">Total submitted : <b><?php echo $total_requests; ?></b></p>

                    <div class="tag tag-green">Approved : <?php echo $approved_requests; ?></div>
                    <div class="tag tag-red">Rejected : <?php echo $rejected_requests; ?></div>
                    <div class="tag tag-orange">Pending : <?php echo $pending_requests; ?></div>
                </div>

                <div class="box">
                    <h2 class="box-title">User Base</h2>
                    <p class="item-text">Total accounts : <b><?php echo $total_users; ?></b></p>
                    <div class="tag tag-blue">Active : <?php echo $active_users; ?></div>
                    <div class="tag tag-red">Suspended : <?php echo $suspended_users; ?></div>
                </div>

            </div>

        </div>

    </div>

<?php include "footer.php"; ?>
