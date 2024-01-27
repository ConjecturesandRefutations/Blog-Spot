<?php
// Database connection
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

// Check if user_id is provided
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Count unread messages directly in the database
    $stmt_unread_count = $mysqli->prepare("SELECT COUNT(*) AS unreadCount FROM messages WHERE receiver_user_id = ? AND is_read = 0");
    $stmt_unread_count->bind_param("i", $user_id);
    $stmt_unread_count->execute();
    $result_unread_count = $stmt_unread_count->get_result();
    $unreadCount = $result_unread_count->fetch_assoc()['unreadCount'];
    $stmt_unread_count->close();

    // Return the unread count as JSON
    echo json_encode(['unreadCount' => $unreadCount]);
} else {
    // Handle the case when user_id is not provided
    echo json_encode(['error' => 'User ID not provided']);
}

// Close the database connection
mysqli_close($mysqli);
?>
