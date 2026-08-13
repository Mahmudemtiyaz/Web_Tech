<?php

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get values from the form
    $applicant_id = trim($_POST["applicant_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $gender = $_POST["gender"] ?? "";
    $job_position = $_POST["job_position"] ?? "";
    $qualification = trim($_POST["qualification"] ?? "");
    $address = trim($_POST["address"] ?? "");


    if (empty($applicant_id)) {
        $errors[] = "Applicant ID is required.";
    }


    if (empty($name)) {
        $errors[] = "Name is required.";
    }


    if (empty($email)) {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Invalid email address.";

    }


    // Phone
    if (empty($phone)) {

        $errors[] = "Phone number is required.";

    } elseif (!preg_match("/^[0-9]{11}$/", $phone)) {

        $errors[] = "Phone number must contain exactly 11 digits.";

    }


    if (empty($password)) {

        $errors[] = "Password is required.";

    } elseif (strlen($password) < 6) {

        $errors[] = "Password must contain at least 6 characters.";

    }


    if (empty($gender)) {

        $errors[] = "Please select your gender.";

    }

    if (empty($job_position)) {

        $errors[] = "Please select a job position.";

    }

    if (empty($qualification)) {

        $errors[] = "Qualification is required.";

    }

    if (empty($address)) {

        $errors[] = "Address is required.";

    }


    // CV
    if (!isset($_FILES["cv"]) || $_FILES["cv"]["error"] != 0) {

        $errors[] = "Please upload your CV.";

    } else {

        $file_name = $_FILES["cv"]["name"];
        $file_size = $_FILES["cv"]["size"];
        $file_tmp = $_FILES["cv"]["tmp_name"];

        // Get file extension
        $extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );

        // Allowed file types
        $allowed_extensions = ["pdf", "doc", "docx"];

        if (!in_array($extension, $allowed_extensions)) {

            $errors[] =
                "Only PDF, DOC and DOCX files are allowed.";

        }

        // Maximum size = 2 MB
        if ($file_size > 2 * 1024 * 1024) {

            $errors[] =
                "CV file size must be less than or equal to 2 MB.";

        }
    }


    // If there are no errors
    if (count($errors) == 0) {

        $upload_folder = "uploads/";

        $new_file_name =
            time() . "_" . basename($file_name);

        $file_path =
            $upload_folder . $new_file_name;


        // Upload CV
        if (move_uploaded_file($file_tmp, $file_path)) {

            // Send information to result.php using GET
            header(
                "Location: result.php?" .
                "applicant_id=" . urlencode($applicant_id) .
                "&name=" . urlencode($name) .
                "&cv=" . urlencode($new_file_name) .
                "&email=" . urlencode($email) .
                "&phone=" . urlencode($phone) .
                "&gender=" . urlencode($gender) .
                "&job_position=" . urlencode($job_position) .
                "&qualification=" . urlencode($qualification) .
                "&address=" . urlencode($address)
            );

            exit();

        } else {

            $errors[] = "Failed to upload the CV.";

        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Application Result</title>

</head>

<body>

<h2>Application Result</h2>

<?php

if (count($errors) > 0) {

    echo "<h3>Application Failed!</h3>";

    foreach ($errors as $error) {

        echo "<p>" .
             htmlspecialchars($error) .
             "</p>";

    }

    echo '<a href="index.php">Go Back</a>';

} else {

    echo "<p>Application submitted successfully.</p>";

}

?>

</body>

</html>