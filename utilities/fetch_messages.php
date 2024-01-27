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

    // Use prepared statement to prevent SQL injection
    $stmt_fetch_messages = $mysqli->prepare("SELECT * FROM messages WHERE receiver_user_id = ? ORDER BY timestamp DESC");
    
    if ($stmt_fetch_messages) {
        $stmt_fetch_messages->bind_param("i", $user_id);
        $stmt_fetch_messages->execute();
        
        $result_messages = $stmt_fetch_messages->get_result();
        
        if ($result_messages) {
            // Fetch the resulting rows as an array
            $messages = $result_messages->fetch_all(MYSQLI_ASSOC);

            // Count unread messages directly in the database
            $stmt_unread_count = $mysqli->prepare("SELECT COUNT(*) AS unreadCount FROM messages WHERE receiver_user_id = ? AND is_read = 0");
            $stmt_unread_count->bind_param("i", $user_id);
            $stmt_unread_count->execute();
            $result_unread_count = $stmt_unread_count->get_result();
            $unreadCount = $result_unread_count->fetch_assoc()['unreadCount'];
            $stmt_unread_count->close();

            // Set all messages as read
            $stmt_set_messages_read = $mysqli->prepare("UPDATE messages SET is_read = 1 WHERE receiver_user_id = ?");
            $stmt_set_messages_read->bind_param("i", $user_id);
            $stmt_set_messages_read->execute();
            $stmt_set_messages_read->close();

            // Close the result set
            $result_messages->close();

            // Return the messages and unread count as JSON
            echo json_encode(['messages' => $messages, 'unreadCount' => $unreadCount]);
        } else {
            // Handle the case when the query execution fails
            echo json_encode(['error' => 'Query execution failed']);
        }
        
        // Close the statement
        $stmt_fetch_messages->close();
    } else {
        // Handle the case when the statement preparation fails
        echo json_encode(['error' => 'Statement preparation failed']);
    }
} else {
    // Handle the case when user_id is not provided
    echo json_encode(['error' => 'User ID not provided']);
}

// Close the database connection
mysqli_close($mysqli);
?>
