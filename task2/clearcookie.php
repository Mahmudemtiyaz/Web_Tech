<?php
setcookie("student_name", "", time() - 3600, "/");
setcookie("student_id", "", time() - 3600, "/");
 
?>
 
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Cookie</title>
</head>
 
<body>
 
    <h2>Cookie deleted successfully.</h2>
    <br>
    <a href="task1.php">Go Back</a>
 
</body>
 
</html>
 