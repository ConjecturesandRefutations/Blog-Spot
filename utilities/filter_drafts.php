<?php
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (!isset($_GET['id'])) {
    echo 'User ID is missing.';
    exit();
}

$user_id = $_GET['id'];
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$stmt_blogs = $mysqli->prepare("SELECT blogs.title, blogs.date, blogs.last_updated, blogs.content, blogs.id, blogs.topic, user.user_id, user.name as author_name
    FROM blogs
    INNER JOIN user ON blogs.user_id = user.user_id
    WHERE blogs.user_id = ? AND (blogs.title LIKE ? OR blogs.topic LIKE ?) AND blogs.is_draft = 1
    ORDER BY COALESCE(blogs.last_updated, blogs.date) DESC, blogs.id DESC");

$likeParam = "%$searchTerm%";
$stmt_blogs->bind_param("iss", $user_id, $likeParam, $likeParam);
$stmt_blogs->execute();
$result_blogs = $stmt_blogs->get_result();

$blogs = $result_blogs->fetch_all(MYSQLI_ASSOC);

function calculateWordCount($content) {
    $wordCount = substr_count($content, ' ') + 1;
    return $wordCount;
}

$stmt_blogs->close();
mysqli_close($mysqli);

if (count($blogs) > 0) {
    foreach($blogs as $blog) {
        echo '<div class="col s12 profile-card" style="border: 1px solid grey;" >';
        echo '<a href="view.php?id=' . htmlspecialchars($blog['id']) . '" class="center grey-text text-darken-2">';
        echo '<div class="card-content">';
        echo '<h6 style="font-weight: bold">' . htmlspecialchars($blog['title']) . '</h6>';
        echo '<p style="font-size: smaller">Topic: ' . htmlspecialchars($blog['topic']) . '</p>';
        echo '<p style="font-size: smaller">Word Count: ' . calculateWordCount($blog['content']) . '</p>';
        echo '<p style="font-size: smaller">Created On: ' . date('d-m-Y', strtotime($blog['date'])) . '</p>';
        echo '<p style="font-size: smaller">Last Updated: ' . date('d M Y H:i:s', strtotime($blog['last_updated'])) . '</p>';
        echo '</div>';
        echo '</a>';
        echo '</div>';
    }
} else {
    echo '<p>No drafts found.</p>';
}
?>
