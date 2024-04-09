<?php
include('../config/db_connect.php');

// Get the search term from the GET request
$search = isset($_GET['search']) ? $_GET['search'] : '';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// A query to fetch published blogs with user information
$sql = "SELECT DISTINCT blogs.title, blogs.content, blogs.date, blogs.id, blogs.topic, blogs.featured_image, user.user_id, user.name as author_name,
                COALESCE(blogs.last_updated, blogs.date) AS last_updated
         FROM blogs
         INNER JOIN user ON blogs.user_id = user.user_id
         WHERE (blogs.title LIKE '%$search%'
            OR user.name LIKE '%$search%'
            OR blogs.topic LIKE '%$search%')
            AND blogs.is_draft = 0  -- Include only published blogs
         ORDER BY last_updated DESC, blogs.id DESC";

// Make the query and get the result
$result = mysqli_query($conn, $sql);

// Calculate word count for each blog after stripping HTML tags
$blogs = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Calculate word count for the blog after stripping HTML tags
    $row['word_count'] = str_word_count(strip_tags($row['content']));
    // Append the row to the $blogs array
    $blogs[] = $row;
}
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
                <!-- Check if the blog has a featured image -->
                <?php if (!empty($blog['featured_image'])): ?>
                <!-- Display the featured image -->
                <img class="favicon" src="uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="Featured Image">
                <img class="quill" src="uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="Featured Image">
                <?php else: ?>
                <!-- Display default images if no featured image -->
                <img src="images/favicon.png" class="favicon" alt="favicon">
                <img src="images/Quill.jpg" class="quill" alt="Image of a quill">
                <?php endif; ?>
                <div class='center grey-text' style="font-weight: bold;"><?php echo "Word Count: " . $blog['word_count']; ?></div>
                <h6 class='center grey-text text-darken-2 blog-title' style="font-weight: bold;"><?php echo htmlspecialchars($blog['title']); ?></h6>
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