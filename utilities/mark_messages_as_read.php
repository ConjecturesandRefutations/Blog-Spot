<?php
// Database connection
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

// Check if user_id is provided
if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Set all messages as read
    $stmt_set_messages_read = $mysqli->prepare("UPDATE messages SET is_read = 1 WHERE receiver_user_id = ?");
    $stmt_set_messages_read->bind_param("i", $user_id);
    $stmt_set_messages_read->execute();
    $stmt_set_messages_read->close();
} else {
    // Handle the case when user_id is not provided
    echo json_encode(['error' => 'User ID not provided']);
}

// Close the database connection
mysqli_close($mysqli);
?>
