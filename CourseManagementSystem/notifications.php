<?php
session_start();
require_once "config.php";
require_once "functions.php";
require_once "check_login.php";

// ---------- mark one notification as read and go to its link ----------

if (isset($_GET["open"])) {

    $nid = intval($_GET["open"]);

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notif_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $nid, $me["user_id"]);
    $stmt->execute();
    $stmt->close();

    $find = $conn->prepare("SELECT link FROM notifications WHERE notif_id = ? AND user_id = ?");
    $find->bind_param("ii", $nid, $me["user_id"]);
    $find->execute();
    $row = $find->get_result()->fetch_assoc();
    $find->close();

    if ($row != null && $row["link"] != "") {
        header("Location: " . $row["link"]);
        exit();
    }

    header("Location: notifications.php");
    exit();
}

// ---------- mark everything as read ----------

if (isset($_GET["action"]) && $_GET["action"] == "read_all") {

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $me["user_id"]);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $me["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Notifications";
include "header.php";
?>

    <div class="page">

        <div class="row" style="align-items:center;justify-content:space-between">
            <h1>Notifications</h1>
            <a href="notifications.php?action=read_all" class="btn btn-outline btn-small">Mark all as read</a>
        </div>
        <p class="page-info">Everything CourseHub has told you, newest first.</p>

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">You have no notifications yet.</div>

        <?php } else { while ($n = $result->fetch_assoc()) { ?>

            <a href="notifications.php?open=<?php echo $n["notif_id"]; ?>" style="text-decoration:none;color:inherit">
                <div class="notif <?php echo $n["is_read"] == 0 ? "notif-unread" : ""; ?>">
                    <div>
                        <div class="item-text"><?php echo htmlspecialchars($n["text"]); ?></div>
                        <span class="gray-text"><?php echo showDateTime($n["created_at"]); ?></span>
                    </div>
                    <?php if ($n["is_read"] == 0) { ?><span class="tag tag-blue">New</span><?php } ?>
                </div>
            </a>

        <?php } } ?>

    </div>

<?php
$stmt->close();
include "footer.php";
?>
