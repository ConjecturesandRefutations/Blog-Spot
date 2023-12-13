<?php
include('config/db_connect.php');

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT blogs.title, blogs.date, blogs.id, blogs.topic, user.user_id, user.name as author_name,
               SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) AS word_count
        FROM blogs
        INNER JOIN user ON blogs.user_id = user.user_id
        WHERE blogs.title LIKE '%$search%'
           OR user.name LIKE '%$search%'
           OR blogs.topic LIKE '%$search%'
        GROUP BY blogs.id
        ORDER BY blogs.date DESC, blogs.id DESC";

$result = mysqli_query($conn, $sql);

// Fetch the resulting rows as an array
$blogs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Free result from memory
mysqli_free_result($result);

// Close connection
mysqli_close($conn);

// Loop through the filtered blogs and generate HTML
foreach ($blogs as $blog):
    // Output the HTML structure for each blog item
    ?>
    <div class="col s6">
        <a href="view.php?id=<?php echo $blog['id'] ?>" class="card-link">
            <div class="card z-depth-0 blog-card">
                <div class="card-content blog-card-content">
                    <img src="images/favicon.png" class="favicon" alt="favicon">
                    <img src="images/Quill.jpg" class="quill" alt="Image of a quill">
                    <div class='center grey-text' style="font-weight: bold;"><?php echo "Word Count: " . $blog['word_count']; ?></div>
                    <h6 class='center grey-text text-darken-3 blog-title'><?php echo htmlspecialchars($blog['title']); ?></h6>
                    <p class='center grey-text text-darken-3' style="font-style: italic;"><span id="category">Category: </span><?php echo htmlspecialchars($blog['topic']); ?></p>
                    <p class='center grey-text text-darken-3' style="font-weight: bold;">Author: <?php echo htmlspecialchars($blog['author_name']); ?></p>
                    <div class='center grey-text' style="font-weight: bold;"><?php echo date('d M Y', strtotime($blog['date'])); ?></div>
                </div>
            </div>
        </a>
    </div>
<?php
endforeach;
<<<<<<< HEAD
?>
=======
?>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
