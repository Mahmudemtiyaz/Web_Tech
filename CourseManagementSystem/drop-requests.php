<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

if ($me["role"] == "admin") {
    header("Location: admin-drop-requests.php");
    exit();
}

$page_title = "Drop Requests";

// ============================================
// STUDENT : my own request history, with a cancel option
// ============================================

if ($me["role"] == "student") {

    // ---------- cancel a still-pending request ----------

    if (isset($_GET["action"]) && $_GET["action"] == "cancel") {

        $rid = intval($_GET["id"]);

        $stmt = $conn->prepare("DELETE FROM drop_requests WHERE request_id = ? AND student_id = ? AND admin_status = 'pending'");
        $stmt->bind_param("ii", $rid, $me["user_id"]);
        $stmt->execute();
        $stmt->close();

        header("Location: drop-requests.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT d.*, c.course_code, c.title
                            FROM drop_requests d JOIN courses c ON d.course_id = c.course_id
                            WHERE d.student_id = ? ORDER BY d.request_id DESC");
    $stmt->bind_param("i", $me["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    include "header.php";
    ?>

    <div class="page">

        <h1>My Drop Requests</h1>
        <p class="page-info">Track the approval status of every drop request you have submitted.</p>

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">You have not submitted any drop request.</div>

        <?php } else { while ($r = $result->fetch_assoc()) {

            $overall = "pending";
            if ($r["teacher_status"] == "rejected") { $overall = "rejected"; }
            else if ($r["admin_status"] == "approved") { $overall = "approved"; }
            else if ($r["admin_status"] == "rejected") { $overall = "rejected"; }
            ?>

            <div class="item">
                <h3><?php echo htmlspecialchars($r["course_code"] . " - " . $r["title"]); ?></h3>
                <div class="item-info">Submitted on <?php echo showDate($r["created_at"]); ?></div>
                <div class="item-text"><?php echo htmlspecialchars($r["reason"]); ?></div>

                <p>
                    Teacher : <?php echo showStatus($r["teacher_status"]); ?>
                    &nbsp; Admin : <?php echo showStatus($r["admin_status"]); ?>
                </p>

                <?php if ($overall == "pending") { ?>
                    <button class="btn-small btn-red" onclick="if (confirm('Cancel this drop request?')) { window.location.href='drop-requests.php?action=cancel&id=<?php echo $r["request_id"]; ?>'; }">Cancel Request</button>
                <?php } ?>
            </div>

        <?php } } ?>

    </div>

    <?php
    $stmt->close();
    include "footer.php";
    exit();
}

// ============================================
// TEACHER : approve or reject requests for the courses I teach
// ============================================

require_once "check_teacher.php";

if (isset($_GET["action"]) && $_GET["id"]) {

    $rid    = intval($_GET["id"]);
    $action = $_GET["action"];

    // make sure this request belongs to a course this teacher actually teaches
    $check = $conn->prepare("SELECT d.*, c.teacher_id, c.title FROM drop_requests d
                             JOIN courses c ON d.course_id = c.course_id
                             WHERE d.request_id = ?");
    $check->bind_param("i", $rid);
    $check->execute();
    $request = $check->get_result()->fetch_assoc();
    $check->close();

    if ($request != null && $request["teacher_id"] == $me["user_id"] && $request["teacher_status"] == "pending") {

        if ($action == "teacher_approve") {

            $stmt = $conn->prepare("UPDATE drop_requests SET teacher_status = 'approved' WHERE request_id = ?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();

            addNotification($conn, $request["student_id"],
                "Your teacher approved your drop request for " . $request["title"] . ". Waiting for admin's final approval.", "drop-requests.php");

            // let every admin know a final decision is needed
            $admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin'");
            while ($a = $admins->fetch_assoc()) {
                addNotification($conn, $a["user_id"], "A drop request needs your final approval.", "admin-drop-requests.php");
            }
        }
        else if ($action == "teacher_reject") {

            // a teacher rejection ends the whole process, no need to bother the admin
            $stmt = $conn->prepare("UPDATE drop_requests SET teacher_status = 'rejected', admin_status = 'rejected' WHERE request_id = ?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();

            addNotification($conn, $request["student_id"],
                "Your teacher rejected your drop request for " . $request["title"] . ".", "drop-requests.php");
        }
    }

    header("Location: drop-requests.php");
    exit();
}

$stmt = $conn->prepare("SELECT d.*, c.course_code, c.title, u.name AS student_name
                        FROM drop_requests d
                        JOIN courses c ON d.course_id = c.course_id
                        JOIN users u ON d.student_id = u.user_id
                        WHERE c.teacher_id = ?
                        ORDER BY d.teacher_status ASC, d.request_id DESC");
$stmt->bind_param("i", $me["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>

<div class="page">

    <h1>Drop Requests</h1>
    <p class="page-info">Requests from students in the courses you teach.</p>

    <?php if ($result->num_rows == 0) { ?>

        <div class="empty">No drop request for your courses yet.</div>

    <?php } else { while ($r = $result->fetch_assoc()) { ?>

        <div class="item <?php echo $r["teacher_status"] == "pending" ? "item-orange" : ""; ?>">
            <h3><?php echo htmlspecialchars($r["student_name"]); ?> - <?php echo htmlspecialchars($r["course_code"] . " " . $r["title"]); ?></h3>
            <div class="item-info">Submitted on <?php echo showDate($r["created_at"]); ?></div>
            <div class="item-text"><?php echo htmlspecialchars($r["reason"]); ?></div>

            <p>Your decision : <?php echo showStatus($r["teacher_status"]); ?>
               &nbsp; Admin's decision : <?php echo showStatus($r["admin_status"]); ?></p>

            <?php if ($r["teacher_status"] == "pending") { ?>
                <div class="buttons">
                    <button class="btn-small btn-green" onclick="window.location.href='drop-requests.php?action=teacher_approve&id=<?php echo $r["request_id"]; ?>'">Approve</button>
                    <button class="btn-small btn-red" onclick="if (confirm('Reject this drop request?')) { window.location.href='drop-requests.php?action=teacher_reject&id=<?php echo $r["request_id"]; ?>'; }">Reject</button>
                </div>
            <?php } ?>
        </div>

    <?php } } ?>

</div>

<?php
$stmt->close();
include "footer.php";
?>
