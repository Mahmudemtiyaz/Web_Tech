<?php
// ============================================
// Included at the top of every admin-only page.
// A teacher or a student who types the URL directly is sent back to the dashboard.
// ============================================

require_once "check_login.php";

if ($me["role"] != "admin") {
    header("Location: dashboard.php");
    exit();
}
?>
