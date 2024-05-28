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

    // Check if user has already interacted with the blog
    $checkSql = "SELECT * FROM likes WHERE user_id = $userId AND blog_id = $id";
    $checkResult = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkResult) > 0) {
        // User has already interacted with this blog
        $existingFeedback = mysqli_fetch_assoc($checkResult)['feedback_type'];
        if ($existingFeedback == $action) {
            // User is trying to do the same action again, do nothing
            $response['error'] = 'You have already ' . $action . 'd this blog.';
        } else {
            // User is trying to change their feedback
            if ($action == 'like') {
                // Update likes count
                $sql = "UPDATE blogs SET likes = likes + 1 WHERE id = $id";
                $updateLikesSql = "UPDATE likes SET feedback_type = 'like' WHERE user_id = $userId AND blog_id = $id";
                $updateOppositeSql = "UPDATE blogs SET dislikes = dislikes - 1 WHERE id = $id";
            } elseif ($action == 'dislike') {
                // Update dislikes count
                $sql = "UPDATE blogs SET dislikes = dislikes + 1 WHERE id = $id";
                $updateLikesSql = "UPDATE likes SET feedback_type = 'dislike' WHERE user_id = $userId AND blog_id = $id";
                $updateOppositeSql = "UPDATE blogs SET likes = likes - 1 WHERE id = $id";
            }

            if (mysqli_query($conn, $sql) && mysqli_query($conn, $updateLikesSql) && mysqli_query($conn, $updateOppositeSql)) {
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
