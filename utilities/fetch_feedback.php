<?php
include('../config/db_connect.php');

$response = array('status' => 'error');

if (isset($_POST['id']) && isset($_POST['type'])) {
    $blogId = mysqli_real_escape_string($conn, $_POST['id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);

    // Validate the type
    if (!in_array($type, ['like', 'dislike'])) {
        $response['error'] = 'Invalid feedback type';
        echo json_encode($response);
        exit();
    }

    // Fetch users who liked or disliked the blog
    $sql = "SELECT user.user_id, user.name, user.profile_image 
    FROM likes 
    INNER JOIN user ON likes.user_id = user.user_id 
    WHERE likes.blog_id = $blogId AND likes.feedback_type = '$type'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
    $users = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = array(
            'id' => $row['user_id'], // Add user_id
            'name' => $row['name'],
            'profile_image' => $row['profile_image'] ? $row['profile_image'] : 'images/defaultProfile.jpg',
        );
    }
    $response['status'] = 'success';
    $response['users'] = $users;
    } else {
    $response['error'] = mysqli_error($conn);
    }
} else {
    $response['error'] = 'Invalid request';
}

echo json_encode($response);
mysqli_close($conn);
?>
