<?php
include('../config/db_connect.php');

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    $sql = "SELECT user.user_id, user.name, user.profile_image, COUNT(blogs.id) as numBlogs, 
                   SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                   COALESCE(favorite_topic.topic, '') as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id
            LEFT JOIN (
                SELECT user_id, MAX(topic) as topic
                FROM blogs
                GROUP BY user_id
            ) AS favorite_topic ON user.user_id = favorite_topic.user_id
            WHERE user.name LIKE '%$search%' OR favorite_topic.topic LIKE '%$search%'
            GROUP BY user.user_id, user.name, user.profile_image
            ORDER BY user.name ASC";

    $result = mysqli_query($conn, $sql);

    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_free_result($result);

    mysqli_close($conn);

    // Return the result as JSON
    echo json_encode($users);
}
?>
