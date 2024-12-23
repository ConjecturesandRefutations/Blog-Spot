<?php
// Include database connection
$mysqli = require __DIR__ . "/../../config/db_connect.php";

// Check if message_id is provided
if (isset($_POST['message_id'])) {
    $messageId = $_POST['message_id'];

    // Use prepared statement to delete the message
    $stmt = $mysqli->prepare("DELETE FROM messages WHERE message_id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();

    // Send a JSON response
    echo json_encode(['status' => 'success']);
} else {
    // Send an error JSON response
    echo json_encode(['status' => 'error', 'message' => 'Message ID not provided']);
}
