<?php
// utilities/fetch_conversations.php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$currentUserId = $_SESSION['user_id'];

// Fetch unique conversations and unread message count
$query = "
    SELECT 
        u.user_id, 
        u.name AS name, 
        m.message_content AS last_message,
        m.timestamp AS last_message_time,
        (SELECT COUNT(*) 
         FROM messages 
         WHERE sender_user_id = u.user_id 
         AND receiver_user_id = ? 
         AND is_read = 0) AS unread_count
    FROM user u
    INNER JOIN (
        SELECT 
            CASE 
                WHEN sender_user_id = ? THEN receiver_user_id 
                ELSE sender_user_id 
            END AS user_id,
            MAX(timestamp) AS last_message_time
        FROM messages
        WHERE sender_user_id = ? OR receiver_user_id = ?
        GROUP BY user_id
    ) convo ON u.user_id = convo.user_id
    INNER JOIN messages m ON (
        (m.sender_user_id = ? AND m.receiver_user_id = u.user_id)
        OR
        (m.sender_user_id = u.user_id AND m.receiver_user_id = ?)
    ) AND m.timestamp = convo.last_message_time
    ORDER BY convo.last_message_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iiiiii', $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];

while ($row = $result->fetch_assoc()) {
    $conversations[] = [
        'user_id' => $row['user_id'],
        'name' => $row['name'],
        'last_message' => $row['last_message'],
        'last_message_time' => date('d/m/Y H:i', strtotime($row['last_message_time'])),
        'unread_count' => $row['unread_count']
    ];
}

echo json_encode(['status' => 'success', 'conversations' => $conversations]);
