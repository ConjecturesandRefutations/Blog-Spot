<?php
include('../config/db_connect.php');  // Adjust the path if necessary
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = array('status' => 'error');

if (isset($_POST['id']) && isset($_POST['action'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $userId = $_SESSION['user_id']; // Assuming you have stored user_id in session

    // Fetch blog owner ID
    $ownerSql = "SELECT user_id FROM blogs WHERE id = $id";
    $ownerResult = mysqli_query($conn, $ownerSql);
    
    if ($ownerResult && mysqli_num_rows($ownerResult) > 0) {
        $ownerRow = mysqli_fetch_assoc($ownerResult);
        $blogOwnerId = $ownerRow['user_id'];
    } else {
        // Handle error: blog owner not found
        $response['error'] = 'Blog owner not found';
        echo json_encode($response);
        exit;
    }

    // Check if user has already interacted with the blog
    $checkSql = "SELECT * FROM likes WHERE user_id = $userId AND blog_id = $id";
    $checkResult = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkResult) > 0) {
        // User has already interacted with this blog
        $existingFeedback = mysqli_fetch_assoc($checkResult)['feedback_type'];
        if ($existingFeedback == $action) {
            // User is trying to undo their previous action
            // Undo the action and update the counts
            if ($action == 'like') {
                $sql = "UPDATE blogs SET likes = likes - 1 WHERE id = $id";
            } elseif ($action == 'dislike') {
                $sql = "UPDATE blogs SET dislikes = dislikes - 1 WHERE id = $id";
            }
            $deleteSql = "DELETE FROM likes WHERE user_id = $userId AND blog_id = $id";

            if (mysqli_query($conn, $sql) && mysqli_query($conn, $deleteSql)) {
                // Fetch the updated counts
                $countSql = "SELECT likes, dislikes FROM blogs WHERE id = $id";
                $result = mysqli_query($conn, $countSql);
                if ($result) {
                    $counts = mysqli_fetch_assoc($result);
                    $response['status'] = 'success';
                    $response['likes'] = $counts['likes'];
                    $response['dislikes'] = $counts['dislikes'];
                }
            } else {
                $response['error'] = mysqli_error($conn);
            }
        } else {
            // User is trying to change their feedback
            // First, undo the previous action
            if ($existingFeedback == 'like') {
                $undoSql = "UPDATE blogs SET likes = likes - 1 WHERE id = $id";
            } elseif ($existingFeedback == 'dislike') {
                $undoSql = "UPDATE blogs SET dislikes = dislikes - 1 WHERE id = $id";
            }
            $deleteSql = "DELETE FROM likes WHERE user_id = $userId AND blog_id = $id";

            // Then, apply the new action
            if ($action == 'like') {
                $sql = "UPDATE blogs SET likes = likes + 1 WHERE id = $id";
                $insertSql = "INSERT INTO likes (user_id, blog_id, feedback_type) VALUES ($userId, $id, 'like')";
            } elseif ($action == 'dislike') {
                $sql = "UPDATE blogs SET dislikes = dislikes + 1 WHERE id = $id";
                $insertSql = "INSERT INTO likes (user_id, blog_id, feedback_type) VALUES ($userId, $id, 'dislike')";
            }

            if (mysqli_query($conn, $undoSql) && mysqli_query($conn, $deleteSql) &&
                mysqli_query($conn, $sql) && mysqli_query($conn, $insertSql)) {
                // Fetch the updated counts
                $countSql = "SELECT likes, dislikes FROM blogs WHERE id = $id";
                $result = mysqli_query($conn, $countSql);
                if ($result) {
                    $counts = mysqli_fetch_assoc($result);
                    $response['status'] = 'success';
                    $response['likes'] = $counts['likes'];
                    $response['dislikes'] = $counts['dislikes'];
                }
            } else {
                $response['error'] = mysqli_error($conn);
            }
        }
    } else {
        // User has not interacted with this blog before
        if ($action == 'like') {
            // Update likes count
            $sql = "UPDATE blogs SET likes = likes + 1 WHERE id = $id";
            // Insert into likes table
            $insertSql = "INSERT INTO likes (user_id, blog_id, feedback_type) VALUES ($userId, $id, 'like')";
        } elseif ($action == 'dislike') {
            // Update dislikes count
            $sql = "UPDATE blogs SET dislikes = dislikes + 1 WHERE id = $id";
            // Insert into likes table
            $insertSql = "INSERT INTO likes (user_id, blog_id, feedback_type) VALUES ($userId, $id, 'dislike')";
        }

        if (mysqli_query($conn, $sql) && mysqli_query($conn, $insertSql)) {
            // Fetch the updated counts
            $countSql = "SELECT likes, dislikes FROM blogs WHERE id = $id";
            $result = mysqli_query($conn, $countSql);
            if ($result) {
                $counts = mysqli_fetch_assoc($result);
                $response['status'] = 'success';
                $response['likes'] = $counts['likes'];
                $response['dislikes'] = $counts['dislikes'];
            }
    
            if ($action == 'like' && $userId != $blogOwnerId) {
                // Insert notification into blog_like_notifications only when the action is 'like'
                $insertNotificationSql = "INSERT INTO blog_like_notifications (liker_id, receiver_id, action, blog_id) VALUES ($userId, $blogOwnerId, '$action', $id)";
                mysqli_query($conn, $insertNotificationSql);
            }
            
        } else {
            $response['error'] = mysqli_error($conn);
        }
    }
    mysqli_close($conn);
} else {
    $response['error'] = 'Invalid request';
}

echo json_encode($response);
?>
