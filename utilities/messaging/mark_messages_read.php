<?php
// mark_messages_read.php
session_start();
$mysqli = require __DIR__ . "/../../config/db_connect.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in or user ID missing']);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_POST['user_id'];

// Debugging: Log the user IDs
error_log("Current User ID: " . $currentUserId);
error_log("Other User ID: " . $otherUserId);

// Check existing unread messages
$checkMessagesSql = "SELECT * FROM messages WHERE sender_user_id = ? AND receiver_user_id = ? AND is_read = 0";
$checkStmt = $mysqli->prepare($checkMessagesSql);
$checkStmt->bind_param('ii', $otherUserId, $currentUserId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    error_log("Unread messages found: " . $result->num_rows);
} else {
    error_log("No unread messages found for this user pair.");
}

// Proceed to mark messages as read
$sql = "UPDATE messages 
        SET is_read = 1 
        WHERE sender_user_id = ? 
        AND receiver_user_id = ? 
        AND is_read = 0";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $otherUserId, $currentUserId);
$stmt->execute();

// Check if any messages were marked as read
if ($stmt->affected_rows > 0) {
    error_log("Messages marked as read: " . $stmt->affected_rows);

    // Delete all message notifications for the current user related to the messages
    $deleteNotificationsSql = "DELETE FROM message_notifications 
                                WHERE receiver_id = ? 
                                AND sender_id = ? 
                                AND message_content IN (
                                    SELECT message_content FROM messages 
                                    WHERE sender_user_id = ? 
                                    AND receiver_user_id = ?
                                )";

    $deleteStmt = $mysqli->prepare($deleteNotificationsSql);
    $deleteStmt->bind_param('iiii', $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    $deleteStmt->execute();

    error_log("Notifications deleted: " . $deleteStmt->affected_rows);
    
    // Check if any notifications were deleted
    if ($deleteStmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Notifications deleted']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'No notifications to delete']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No messages to mark as read']);
}

// Close statements
$stmt->close();
if (isset($deleteStmt)) {
    $deleteStmt->close();
}
$checkStmt->close();

// Close database connection
$mysqli->close();
?>
