<?php
// ============================================
// Course Management System - Common functions
// This file is included in every page.
// ============================================


// ---------- Find one user by id ----------

function getUser($conn, $user_id) {

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user;
}


// ---------- Only the name of a user ----------

function getUserName($conn, $user_id) {

    $user = getUser($conn, $user_id);

    if ($user == null) {
        return "Deleted user";
    }

    return $user["name"];
}


// ---------- Find one course ----------

function getCourse($conn, $course_id) {

    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $course = $result->fetch_assoc();

    $stmt->close();

    return $course;
}


// ---------- Find one course with the teacher name already joined ----------

function getCourseWithTeacher($conn, $course_id) {

    $stmt = $conn->prepare("SELECT c.*, u.name AS teacher_name
                            FROM courses c
                            LEFT JOIN users u ON c.teacher_id = u.user_id
                            WHERE c.course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $course = $result->fetch_assoc();

    $stmt->close();

    return $course;
}


// ---------- Is this student enrolled (and still enrolled, not dropped) in this course? ----------

function getEnrollment($conn, $student_id, $course_id) {

    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row;
}


// ---------- Count anything with one small query (one integer parameter) ----------

function countRows($conn, $sql, $value) {

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $value);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row["total"];
}


// ---------- Count everything, no parameter needed ----------

function countAll($conn, $sql) {

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    return $row["total"];
}


// ---------- How many students are enrolled (not dropped) in a course ----------

function countEnrolled($conn, $course_id) {

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM enrollments
                            WHERE course_id = ? AND enroll_status = 'enrolled'");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row["total"];
}


// ---------- A coloured tag for any status word ----------

function showStatus($status) {

    $green  = array("active", "enrolled", "approved", "done", "completed");
    $orange = array("pending", "in progress");
    // anything else (dropped, rejected, suspended, closed) becomes red

    if (in_array($status, $green)) {
        return "<span class='tag tag-green'>" . ucfirst($status) . "</span>";
    }

    if (in_array($status, $orange)) {
        return "<span class='tag tag-orange'>" . ucfirst($status) . "</span>";
    }

    return "<span class='tag tag-red'>" . ucfirst($status) . "</span>";
}


// ---------- A coloured badge for a user role ----------

function showRole($role) {

    if ($role == "admin") {
        return "<span class='badge badge-admin'>Admin</span>";
    }

    if ($role == "teacher") {
        return "<span class='badge badge-teacher'>Teacher</span>";
    }

    return "<span class='badge badge-student'>Student</span>";
}


// ---------- Letter grade from a numeric average (out of 100) ----------

function markToLetter($marks) {

    if ($marks >= 90) return "A+";
    if ($marks >= 85) return "A";
    if ($marks >= 80) return "A-";
    if ($marks >= 75) return "B+";
    if ($marks >= 70) return "B";
    if ($marks >= 65) return "B-";
    if ($marks >= 60) return "C+";
    if ($marks >= 55) return "C";
    if ($marks >= 50) return "D";

    return "F";
}


// ---------- Save a notification for one user ----------

function addNotification($conn, $user_id, $text, $link) {

    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, text, link, is_read, created_at)
                            VALUES (?, ?, ?, 0, ?)");
    $stmt->bind_param("isss", $user_id, $text, $link, $now);
    $stmt->execute();
    $stmt->close();
}


// ---------- Save one line in the activity log ----------

function addActivity($conn, $user_id, $text) {

    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO activities (user_id, text, created_at)
                            VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $text, $now);
    $stmt->execute();
    $stmt->close();
}


// ---------- How many notifications of one user are still unread ----------

function countUnread($conn, $user_id) {

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications
                            WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row["total"];
}


// ---------- Short date like  14 Aug 2026 ----------

function showDate($date) {

    if ($date == null || $date == "0000-00-00") {
        return "-";
    }

    return date("d M Y", strtotime($date));
}


// ---------- Date with time ----------

function showDateTime($date) {

    if ($date == null) {
        return "-";
    }

    return date("d M Y, g:i A", strtotime($date));
}
?>
