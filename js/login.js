function validateForm() {
    const nameInput = document.getElementById("name");
    const passwordInput = document.getElementById("password");
    const passwordConfirmationInput = document.getElementById("password-confirmation");
    const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d\W&]{6,}$/;

    const nameError = document.getElementById("nameError");
    const passwordError = document.getElementById("passwordError");
    const passwordConfirmationError = document.getElementById("passwordConfirmationError");

    // Clear previous error messages
    nameError.textContent = "";
    passwordError.textContent = "";
    passwordConfirmationError.textContent = "";

    if (nameInput.value.trim() === "") {
        nameError.textContent = "Please enter your name.";
        return false;
    }


    if (passwordInput.value.length < 6 || !passwordPattern.test(passwordInput.value)) {
        passwordError.textContent = "Password must be at least 6 characters long and contain at least one letter and one number.";

            return false;
    }

    if (passwordInput.value !== passwordConfirmationInput.value) {
        passwordConfirmationError.textContent = "Passwords do not match.";
        return false;
    }

        // Check name availability via fetch request
    const nameAvailabilityUrl = "validate-name.php?name=" + encodeURIComponent(nameInput.value);

    return fetch(nameAvailabilityUrl)
        .then(response => response.json())
        .then(data => {
            if (!data.available) {
                nameError.textContent = "This name is already taken. Please choose a different one.";
                return false;
            } else {
                return true;
            }
        })
        .catch(error => {
            console.error("Error checking name availability:", error);
            return false;
        });
}
