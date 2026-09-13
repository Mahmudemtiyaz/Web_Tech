<?php
session_start();

// SESSION : destroy everything, this is the actual logout
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
