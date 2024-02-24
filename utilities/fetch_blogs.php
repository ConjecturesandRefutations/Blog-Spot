<?php
include('../config/db_connect.php');

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT blogs.title, blogs.content, blogs.date, blogs.id, blogs.topic, user.user_id, user.name as author_name,
                COALESCE(blogs.last_updated, blogs.date) AS last_updated
         FROM blogs
         INNER JOIN user ON blogs.user_id = user.user_id
         WHERE (blogs.title LIKE '%$search%'
            OR user.name LIKE '%$search%'
            OR blogs.topic LIKE '%$search%')
            AND blogs.is_draft = 0  -- Include only published blogs
         GROUP BY blogs.id
         ORDER BY last_updated DESC, blogs.id DESC";

$result = mysqli_query($conn, $sql);

// Fetch the resulting rows as an array
$blogs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate word count for each blog after stripping HTML tags
foreach ($blogs as &$blog) {
    $blog['word_count'] = str_word_count(strip_tags($blog['content']));
}

// Free result from memory
mysqli_free_result($result);

// Close connection
mysqli_close($conn);

// Loop through the filtered blogs and generate HTML
foreach ($blogs as $blog):
    // Output the HTML structure for each blog item
    ?>
    <div class="col s12 m6">
        <a href="view.php?id=<?php echo $blog['id'] ?>" class="card-link">
        <div class="card z-depth-0 blog-card">
                <div class="card-content blog-card-content">
                    <img src="images/favicon.png" class="favicon" alt="favicon">
                    <img src="images/Quill.jpg" class="quill" alt="Image of a quill">
                    <div class='center grey-text' style="font-weight: bold;"><?php echo "Word Count: " . $blog['word_count']; ?></div>
                    <h6 class='center grey-text text-darken-2 blog-title'><?php echo htmlspecialchars($blog['title']); ?></h6>
                    <p class='center grey-text text-darken-2'>Topic: <span style="font-weight: bold;"><?php echo htmlspecialchars($blog['topic']); ?></span></p>
                    <p class='center grey-text text-darken-2'>Author: <span style="font-weight: bold;"><?php echo htmlspecialchars($blog['author_name']); ?></span></p>
                    <div class='center grey-text text-darken-2'>Created: <span><?php echo date('d M Y', strtotime($blog['date'])); ?></span></div>
                    <div class='center grey-text text-darken-2'>Last Updated: <span><?php echo date('d M Y H:i:s', strtotime($blog['last_updated'])); ?></span></div>
                </div>
            </div>
        </a>
    </div>
<?php
endforeach;
?>
