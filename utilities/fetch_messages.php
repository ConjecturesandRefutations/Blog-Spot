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
    
    if (!$stmt_fetch_messages) {
        // Handle the case when the statement preparation fails
        echo json_encode(['error' => 'Statement preparation failed']);
    } else {
        $stmt_fetch_messages->bind_param("i", $user_id);
        $stmt_fetch_messages->execute();

        $result_messages = $stmt_fetch_messages->get_result();

        if (!$result_messages) {
            // Handle the case when the query execution fails
            echo json_encode(['error' => 'Query execution failed']);
        } else {
            // Fetch the resulting rows as an array
            $messages = $result_messages->fetch_all(MYSQLI_ASSOC);

            // Close the result set
            $result_messages->close();

            // Return the messages as JSON
            echo json_encode($messages);
        }

        // Close the statement
        $stmt_fetch_messages->close();
    }
} else {
    // Handle the case when user_id is not provided
    echo json_encode(['error' => 'User ID not provided']);
}

// Close the database connection
mysqli_close($mysqli);
?>
