<?php
$errors = [];
 
if (isset($_POST["clear_cookie"])) {
 
    setcookie("student_name", "", time() - 3600, "/");
    setcookie("student_id", "", time() - 3600, "/");
    echo "<h2>Cookie deleted successfully</h2>";
    echo "<br>";
    echo "<a href='task1.php'>Go Back</a>";
 
    exit;
}
 
$student_name = trim($_POST["student_name"] ?? "");
$student_id = trim($_POST["student_id"] ?? "");
$email = trim($_POST["email"] ?? "");
$department = trim($_POST["department"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";
 
 
if ($student_name === "") {
    $errors[] = "Student Name is required.";
} elseif (!preg_match("/^[a-zA-Z ]+$/", $student_name)) {
    $errors[] = "Student Name should contain only letters and spaces.";
}
 
if ($student_id === "") {
    $errors[] = "Student ID is required.";
} elseif (strlen($student_id) < 4) {
    $errors[] = "Student ID must contain at least 4 characters.";
}
 
if ($email === "") {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}
 
if ($department === "") {
    $errors[] = "Please select a department.";
}
 
if ($password === "") {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must contain at least 6 characters.";
}
 
if ($confirm_password === "") {
 
    $errors[] = "Confirm Password is required.";
 
} elseif ($password !== $confirm_password) {
 
    $errors[] = "Passwords do not match.";
 
}
 
 
if (!empty($errors)) {
 
    echo "<h2>Registration Failed</h2>";
 
    foreach ($errors as $error) {
        echo "<p>$error</p>";
    }
 
    echo "<br>";
    echo "<a href='task1.php'>Go Back</a>";
 
} else {
 
 
 
    setcookie("student_name", $student_name, time() + 3600, "/");
    setcookie("student_id", $student_id, time() + 3600, "/");
 
    echo "<h2>Registration Successful!</h2>";
 
    echo "<p>Student Name: "
        . htmlspecialchars($student_name)
        . "</p>";
 
    echo "<p>Student ID: "
        . htmlspecialchars($student_id)
        . "</p>";
 
    echo "<p>Email: "
        . htmlspecialchars($email)
        . "</p>";
 
    echo "<p>Department: "
        . htmlspecialchars($department)
        . "</p>";
 
    echo "<p>Cookies saved successfully for 1 hour.</p>";
 
    echo "<br>";
    echo "<a href='task1.php'>Go Back</a>";
 
}
 
?>
 