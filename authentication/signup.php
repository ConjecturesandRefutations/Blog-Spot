<?php

session_start();

if (empty($_POST["name"])) {
    $_SESSION['error'] = "Name is required";
    header("Location: signup-page.php");
    exit();
}

if (empty($_POST["name"])){
    die("Name is required");
}

if (strlen($_POST["password"]) < 6) {
    die("Password must be at least 6 characters");
}

if ( ! preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}

if ( ! preg_match("/[0-9]/", $_POST["password"])) {
    die("Password must contain at least one number");
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    die("Passwords must match");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$mysqli = require __DIR__ . "/../config/db_connect.php";

if (!($mysqli instanceof mysqli)) {
    die("Database connection error");
}

// Check if a user with the same name already exists
$check_sql = "SELECT COUNT(*) FROM user WHERE name = ?";
$check_stmt = $mysqli->prepare($check_sql);
$check_stmt->bind_param("s", $_POST["name"]);
$check_stmt->execute();
$check_stmt->bind_result($name_count);
$check_stmt->fetch();
$check_stmt->close();

if ($name_count > 0) {
    $_SESSION['error'] = "Username already exists. Please choose a different username.";
    header("Location: signup-page.php");
    exit();
}
$sql = "INSERT INTO user (name, password_hash)
        VALUES (?, ?)";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("ss", $_POST["name"], $password_hash);


try {
    if ($stmt->execute()) {
        header("Location: login.php");
        exit;
    } else {
        die("An error occurred during signup: " . $stmt->error);
    }
} catch (mysqli_sql_exception $e) {
    die("An error occurred during signup: " . $e->getMessage());
}

?>
