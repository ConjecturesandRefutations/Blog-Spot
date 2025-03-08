<?php
// utilities/fetch_conversation.php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($otherUserId === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit();
}

// Fetch messages between the two users
$query = "
    SELECT message_id, sender_user_id, receiver_user_id, message_content, media_path, media_type, timestamp
    FROM messages
    WHERE (sender_user_id = ? AND receiver_user_id = ?)
       OR (sender_user_id = ? AND receiver_user_id = ?)
    ORDER BY timestamp ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iiii', $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => $row['message_id'],
        'sender_user_id' => $row['sender_user_id'],
        'receiver_user_id' => $row['receiver_user_id'],
        'message_content' => htmlspecialchars($row['message_content']),
        'media_path' => $row['media_path'] ? htmlspecialchars($row['media_path']) : null,
        'media_type' => $row['media_type'],
        'timestamp' => date('d/m/Y H:i', strtotime($row['timestamp']))
    ];
}

echo json_encode(['status' => 'success', 'messages' => $messages]);
