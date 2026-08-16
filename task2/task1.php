<?php
if (isset($_COOKIE["student_name"]) && isset($_COOKIE["student_id"])) {
 
    $saved_name = htmlspecialchars($_COOKIE["student_name"]);
    $saved_id = htmlspecialchars($_COOKIE["student_id"]);
 
    echo "<h2>Welcome Back!</h2>";
    echo "<p>Student Name: " . $saved_name . "</p>";
    echo "<p>Student ID: " . $saved_id . "</p>";
 
} else {
 
    echo "<p>No saved student information found.</p>";
 
}
 
?>
 
 
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
</head>
 <body>
 
    <h1>Student Registration Form</h1>
 
    <form action="task2.php" method="POST">
 
 
        <label for="student_name">Student Name:</label>
        <input type="text" id="student_name" name="student_name" required>
        <br><br>
 
 
        <label for="student_id">Student ID:</label>
        <input type="text" id="student_id" name="student_id" required>
        <br><br>
 
 
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br><br>
 
 
        <label for="department">Department:</label>
        <select id="department" name="department" required>
            <option value="">Select Department</option>
            <option value="CSE">Computer Science & Engineering</option>
            <option value="EEE">Electrical & Electronic Engineering</option>
            <option value="BBA">Business Administration</option>
            <option value="English">English</option>
        </select>
        <br><br>
 
 
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br><br>
 
 
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <br><br>
 
 
        <button type="submit">Register</button>
 
    </form>
 
    <br>
 
 
    <form action="clearcookie.php" method="POST">
        <button type="submit">Clear Cookie</button>
    </form>
 
</body>
 
</html>


