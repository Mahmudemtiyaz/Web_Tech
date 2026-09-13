<?php
// ============================================
// Included at the top of every student-only page.
// ============================================

require_once "check_login.php";

if ($me["role"] != "student") {
    header("Location: dashboard.php");
    exit();
}
?>
