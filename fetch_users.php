<?php
include('config/db_connect.php');

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    $sql = "SELECT user.user_id, user.name, COUNT(blogs.id) as numBlogs, 
                   SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                   MAX(blogs.topic) as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id
            WHERE user.name LIKE '%$search%' OR blogs.topic LIKE '%$search%'
            GROUP BY user.user_id, user.name
            ORDER BY user.name ASC";

    $result = mysqli_query($conn, $sql);

    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_free_result($result);

    mysqli_close($conn);

    // Return the result as JSON
    echo json_encode($users);
}
<<<<<<< HEAD
?>
=======
?>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
