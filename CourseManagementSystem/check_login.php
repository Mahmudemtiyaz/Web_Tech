<?php
// ============================================
// Included at the top of every page that needs a login.
// Works for all three roles, because everyone sits in one "users" table.
// ============================================

if (!isset($_SESSION["user_logged_in"]) || $_SESSION["user_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

// $me keeps all the information of the person who is logged in
$me = getUser($conn, $_SESSION["user_id"]);

// the admin may have deleted or suspended this account while it was logged in
if ($me == null || $me["status"] == "suspended") {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
