<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_admin.php";

// ---------- final decision ----------

if (isset($_GET["action"]) && isset($_GET["id"])) {

    $rid    = intval($_GET["id"]);
    $action = $_GET["action"];

    $check = $conn->prepare("SELECT d.*, c.title FROM drop_requests d JOIN courses c ON d.course_id = c.course_id WHERE d.request_id = ?");
    $check->bind_param("i", $rid);
    $check->execute();
    $request = $check->get_result()->fetch_assoc();
    $check->close();

    if ($request != null && $request["teacher_status"] == "approved" && $request["admin_status"] == "pending") {

        if ($action == "final_approve") {

            // this is the moment the student is actually dropped from the course
            $update = $conn->prepare("UPDATE enrollments SET enroll_status = 'dropped' WHERE student_id = ? AND course_id = ?");
            $update->bind_param("ii", $request["student_id"], $request["course_id"]);
            $update->execute();
            $update->close();

            $stmt = $conn->prepare("UPDATE drop_requests SET admin_status = 'approved' WHERE request_id = ?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();

            addNotification($conn, $request["student_id"], "Your drop request for " . $request["title"] . " has been fully approved. You are no longer enrolled.", "my-courses.php");
            addActivity($conn, $me["user_id"], "Finalized a drop request for " . $request["title"]);
        }
        else if ($action == "final_reject") {

            $stmt = $conn->prepare("UPDATE drop_requests SET admin_status = 'rejected' WHERE request_id = ?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();

            addNotification($conn, $request["student_id"], "Your drop request for " . $request["title"] . " was rejected by the admin. You remain enrolled.", "my-courses.php");
        }
    }

    header("Location: admin-drop-requests.php");
    exit();
}

// requests waiting for the admin's final word
$pending = $conn->query("SELECT d.*, c.course_code, c.title, s.name AS student_name, t.name AS teacher_name
                         FROM drop_requests d
                         JOIN courses c ON d.course_id = c.course_id
                         JOIN users s ON d.student_id = s.user_id
                         LEFT JOIN users t ON c.teacher_id = t.user_id
                         WHERE d.teacher_status = 'approved' AND d.admin_status = 'pending'
                         ORDER BY d.request_id ASC");

// full history, for reference
$history = $conn->query("SELECT d.*, c.course_code, c.title, s.name AS student_name
                         FROM drop_requests d
                         JOIN courses c ON d.course_id = c.course_id
                         JOIN users s ON d.student_id = s.user_id
                         WHERE d.admin_status != 'pending' OR d.teacher_status = 'rejected'
                         ORDER BY d.request_id DESC LIMIT 10");

$page_title = "Drop Requests";
include "header.php";
?>

    <div class="page">

        <h1>Drop Requests - Final Approval</h1>
        <p class="page-info">These requests were already approved by the teacher and now need your final decision.</p>

        <?php if ($pending->num_rows == 0) { ?>

            <div class="empty">No drop request is waiting for your approval right now.</div>

        <?php } else { while ($r = $pending->fetch_assoc()) { ?>

            <div class="item item-orange">
                <h3><?php echo htmlspecialchars($r["student_name"]); ?> - <?php echo htmlspecialchars($r["course_code"] . " " . $r["title"]); ?></h3>
                <div class="item-info">
                    Taught by <?php echo htmlspecialchars($r["teacher_name"] ?? "Not assigned"); ?>
                    &middot; Submitted on <?php echo showDate($r["created_at"]); ?>
                </div>
                <div class="item-text"><?php echo htmlspecialchars($r["reason"]); ?></div>
                <p>Teacher's decision : <?php echo showStatus($r["teacher_status"]); ?></p>

                <div class="buttons">
                    <button class="btn-small btn-green" onclick="window.location.href='admin-drop-requests.php?action=final_approve&id=<?php echo $r["request_id"]; ?>'">Final Approve</button>
                    <button class="btn-small btn-red" onclick="if (confirm('Reject this drop request?')) { window.location.href='admin-drop-requests.php?action=final_reject&id=<?php echo $r["request_id"]; ?>'; }">Reject</button>
                </div>
            </div>

        <?php } } ?>

        <div class="box">
            <h2 class="box-title">Recent Decisions</h2>

            <?php if ($history->num_rows == 0) { ?>

                <div class="empty">Nothing decided yet.</div>

            <?php } else { ?>

                <table>
                    <tr><th>Student</th><th>Course</th><th>Teacher</th><th>Admin</th><th>Date</th></tr>
                    <?php while ($r = $history->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r["student_name"]); ?></td>
                            <td><?php echo htmlspecialchars($r["course_code"]); ?></td>
                            <td><?php echo showStatus($r["teacher_status"]); ?></td>
                            <td><?php echo showStatus($r["admin_status"]); ?></td>
                            <td><?php echo showDate($r["created_at"]); ?></td>
                        </tr>
                    <?php } ?>
                </table>

            <?php } ?>
        </div>

    </div>

<?php include "footer.php"; ?>
