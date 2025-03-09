<?php
// Database connection
$mysqli = require __DIR__ . "/../../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationId']) && isset($_POST['notificationType'])) {
    $notificationId = $_POST['notificationId'];
    $notificationType = $_POST['notificationType'];

    // Determine which table to delete from based on the notification type
    switch ($notificationType) {
        case 'like':
            $query = "DELETE FROM post_likes_notifications WHERE id = ?";
            break;
        case 'comment_like':
            $query = "DELETE FROM comment_likes_notifications WHERE id = ?";
            break;
        case 'reply_like':
            $query = "DELETE FROM reply_likes_notifications WHERE id = ?";
            break;
        case 'blog_like':
            $query = "DELETE FROM blog_like_notifications WHERE id = ?";
            break;
        case 'comment':
            $query = "DELETE FROM post_comment_notifications WHERE id = ?";
            break;
        case 'feedback':
            $query = "DELETE FROM blog_feedback_notifications WHERE id = ?";
            break;
        case 'accepted':
            $query = "DELETE FROM friend_accepted_notifications WHERE id = ?";
            break;
        case 'reply':
            $query = "DELETE FROM post_reply_notifications WHERE id = ?";
            break;
        default:
            $response = [
                'status' => 'error',
                'message' => 'Invalid notification type'
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
    }

    // Prepare statement to delete the notification from the database
    $stmt = $mysqli->prepare($query);

    if ($stmt === false) {
        $response = [
            'status' => 'error',
            'message' => 'Database error: ' . $mysqli->error
        ];
    } else {
        $stmt->bind_param('i', $notificationId);
        if ($stmt->execute()) {
            // Successful deletion
            $response = [
                'status' => 'success',
                'message' => 'Notification removed successfully'
            ];
        } else {
            // Error executing deletion
            $response = [
                'status' => 'error',
                'message' => 'Failed to remove notification'
            ];
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Invalid request method or missing parameters
    $response = [
        'status' => 'error',
        'message' => 'Invalid request'
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
