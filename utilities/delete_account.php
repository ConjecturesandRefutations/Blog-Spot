<?php
if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}
$mysqli = require __DIR__ . "/../config/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate and sanitize user input
    $user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);

    if (!$user_id) {
        // Handle invalid user ID
        header("Location: error_page.php");
        exit();
    }

    // Perform the deletion
    $stmt_delete_user = $mysqli->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt_delete_user->bind_param("i", $user_id);

    if ($stmt_delete_user->execute()) {
        // Deletion successful, destroy the session and redirect
        session_destroy();
        header("Location: ../authentication/login.php");
        exit();
    } else {
        // Deletion failed, handle accordingly
        header("Location: ../error_page.php");
        exit();
    }
} else {
    // Handle invalid request method
    header("Location: ../error_page.php");
    exit();
}
