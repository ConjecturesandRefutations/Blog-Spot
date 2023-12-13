<?php
// Include necessary files and initialize database connection if needed
$mysqli = require __DIR__ . "/config/db_connect.php";

$searchTerm = '';

// Check if user_id is provided in the URL
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Check if the search term is provided
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
    }

    // Fetch blogs for the user with search functionality
    $stmt_blogs = $mysqli->prepare("SELECT blogs.title, blogs.date, blogs.content, blogs.id, blogs.topic, user.user_id, user.name as author_name
        FROM blogs
        INNER JOIN user ON blogs.user_id = user.user_id
        WHERE blogs.user_id = ? AND (blogs.title LIKE ? OR blogs.topic LIKE ?)
        ORDER BY blogs.date DESC, blogs.id DESC");
    $likeParam = "%$searchTerm%";
    $stmt_blogs->bind_param("iss", $user_id, $likeParam, $likeParam);
    $stmt_blogs->execute();
    $result_blogs = $stmt_blogs->get_result();

    // Fetch the resulting rows as an array
    $blogs = $result_blogs->fetch_all(MYSQLI_ASSOC);


    function calculateWordCount($content) {
        // Count words by counting spaces
        $wordCount = substr_count($content, ' ') + 1;
    
        return $wordCount;
    }
    ?>
    <div class="row">
        <?php foreach($blogs as $blog): ?>
            <div class="col s12" style="border: 1px solid grey;" >
                <a href="view.php?id=<?php echo $blog['id']; ?>" class="center grey-text text-darken-2">
                    <div class="card-content">
                        <h6 style="font-weight: bold"><?php echo htmlspecialchars($blog['title']); ?></h6>
                        <p style="font-size: smaller">Topic: <?php echo htmlspecialchars($blog['topic']); ?></p>
                        <p style="font-size: smaller">Word Count: <?php echo calculateWordCount($blog['content']); ?></p>
                        <p style="font-size: smaller">Created on <?php echo date('d-m-Y', strtotime($blog['date'])); ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php

    // Free result from memory
    $stmt_blogs->close();
} else {
    echo "User ID not provided";
    exit();
}

// Close connection
mysqli_close($mysqli);
<<<<<<< HEAD
?>
=======
?>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
