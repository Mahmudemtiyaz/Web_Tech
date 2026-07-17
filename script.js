const form = document.getElementById("clubForm");

let wrongAttempts = 0;

form.addEventListener("submit", function (event) {

    event.preventDefault();

    clearErrors();

    let valid = true;

    let firstName = document.getElementById("firstName");
    let lastName = document.getElementById("lastName");
    let email = document.getElementById("email");
    let password = document.getElementById("password");
    let category = document.getElementById("category");
    let reason = document.getElementById("reason");

    let gender =
        document.querySelector('input[name="gender"]:checked');

    let clubs =
        document.querySelectorAll('input[name="club"]:checked');


    let nameRegex = /^[A-Za-z]+$/;

    let emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    let passwordRegex =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;



    /* First Name */

    if (firstName.value.trim() === "") {

        showError(firstName,
            "firstNameError",
            "First Name is required.");

        valid = false;

    }

    else if (!nameRegex.test(firstName.value.trim())) {

        showError(firstName,
            "firstNameError",
            "Only alphabets are allowed.");

        valid = false;

    }

    else {

        showSuccess(firstName);

    }



    /* Last Name */

    if (lastName.value.trim() === "") {

        showError(lastName,
            "lastNameError",
            "Last Name is required.");

        valid = false;

    }

    else if (!nameRegex.test(lastName.value.trim())) {

        showError(lastName,
            "lastNameError",
            "Only alphabets are allowed.");

        valid = false;

    }

    else {

        showSuccess(lastName);

    }



    /* Email */

    if (email.value.trim() === "") {

        showError(email,
            "emailError",
            "Email is required.");

        valid = false;

    }

    else if (!emailRegex.test(email.value.trim())) {

        showError(email,
            "emailError",
            "Invalid Email Address.");

        valid = false;

    }

    else {

        showSuccess(email);

    }



    /* Password */

    if (password.value === "") {

        showError(password,
            "passwordError",
            "Password is required.");

        valid = false;

    }

    else if (!passwordRegex.test(password.value)) {

        wrongAttempts++;

        showError(password,
            "passwordError",
            "Invalid Password. Attempt "
            + wrongAttempts + " of 3.");

        valid = false;

        if (wrongAttempts >= 3) {

            password.disabled = true;

        }

    }

    else {

        wrongAttempts = 0;

        showSuccess(password);

    }



    /* Gender */

    if (gender === null) {

        document.getElementById("genderError").innerHTML =
            "Please select your gender.";

        valid = false;

    }



    /* Clubs */

    if (clubs.length === 0) {

        document.getElementById("clubError").innerHTML =
            "Please select a club.";

        valid = false;

    }



    /* Category */

    if (category.value === "") {

        showError(category,
            "categoryError",
            "Select a category.");

        valid = false;

    }

    else {

        showSuccess(category);

    }



    /* Reason */

    if (reason.value.trim() === "") {

        showError(reason,
            "reasonError",
            "Reason is required.");

        valid = false;

    }

    else if (reason.value.trim().length < 20) {

        showError(reason,
            "reasonError",
            "Minimum 20 characters required.");

        valid = false;

    }

    else {

        showSuccess(reason);

    }



    if (valid) {

        alert("Registration Successful!");

        form.reset();

        clearErrors();

    }

});



function showError(input, id, message) {

    input.classList.add("errorBorder");
    input.classList.remove("successBorder");

    document.getElementById(id).innerHTML = message;

}


function showSuccess(input) {

    input.classList.remove("errorBorder");
    input.classList.add("successBorder");

}


function clearErrors() {

    let errors =
        document.querySelectorAll(".error");

    errors.forEach(function (item) {

        item.innerHTML = "";

    });


    let fields =
        document.querySelectorAll(
            "input,select,textarea"
        );

    fields.forEach(function (item) {

        item.classList.remove("errorBorder");
        item.classList.remove("successBorder");

    });

}