<?php

// fetch_blog_like_notifications.php

function fetchBlogLikeNotifications($loggedInUserId, $mysqli) {
    $notifications = array();

    $sql = "SELECT bln.id, bln.liker_id, bln.action, bln.blog_id, b.title, u.name
            FROM blog_like_notifications bln
            INNER JOIN blogs b ON bln.blog_id = b.id
            INNER JOIN user u ON bln.liker_id = u.user_id
            WHERE bln.receiver_id = $loggedInUserId
            ORDER BY bln.created_at DESC";

    $result = mysqli_query($mysqli, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }

    return $notifications;
}
