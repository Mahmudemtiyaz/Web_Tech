<?php
// ============================================
// Included at the top of every teacher-only page.
// ============================================

require_once "check_login.php";

if ($me["role"] != "teacher") {
    header("Location: dashboard.php");
    exit();
}
?>
