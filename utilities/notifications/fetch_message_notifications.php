<?php

// Include your database connection
$mysqli = require __DIR__ . "/../../config/db_connect.php";

// Function to fetch message notifications for a given user
function fetchMessageNotifications($userId) {
    global $mysqli;

    // Updated query to include sender_id
    $stmt = $mysqli->prepare("SELECT mn.id, mn.sender_id, u.name, mn.created_at 
                              FROM message_notifications mn
                              INNER JOIN user u ON mn.sender_id = u.user_id
                              WHERE mn.receiver_id = ?
                              ORDER BY mn.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $notifications;
}

?>
