<?php
// Start the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to index.php if logged in
    header("Location: ../index.php");
    exit();
}

// Initialize $error variable
$error = null;

// Check if there is an error message in the session
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    // Clear the error message from the session
    unset($_SESSION['error']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="shortcut icon" href="../images/favicon.png" type="image/svg+xml">
    <script src="../js/login.js"></script>
</head>
<body>

    <div class="login-page">
    
        <div class="login-card">
    
          <h1>Blog Spot</h1>
          <h2>Signup</h2>
          <form class="login-form signup-form" action="signup.php" method="POST" id="signup" onsubmit="return validateForm()">
            
            <!-- Name Input -->
            <input type="text" id="name" name="name" placeholder="username"/>
            <span class="error" id="nameError"></span>
            
            <?php if ($error) : ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Password Input -->
            <input type="password" id="password" name="password" placeholder="password"/>
            <span class="error" id="passwordError"></span>

            <!-- Confirm Password -->
            <input type="password" id="password-confirmation" name="password_confirmation" placeholder="confirm password">
            <span class="error" id="passwordConfirmationError"></span>
            
            <p class="loginSignup">Already Have an Account?</p>
            <a href="login.php">Login</a>
            <button class='loginBtn'>SIGNUP</button>
            <a href="../index.php" class='loginBtn noLogin'>Browse without logging in (you will not be able to post blogs)</a>

          </form>
    </div>
    </div>
</body>
</html>