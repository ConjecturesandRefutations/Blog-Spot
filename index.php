<?php

$user = null; // Initialize the $user variable

if (isset($_SESSION["user_id"])) {
    $mysqli = require __DIR__ . "/config/db_connect.php";
    $sql = "SELECT * FROM user WHERE user_id = {$_SESSION["user_id"]}";
    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();
}

include('config/db_connect.php');

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
            AND blogs.id NOT IN (1, 2) 
         GROUP BY blogs.id
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

// Free result from memory
mysqli_free_result($result);

// Close connection
mysqli_close($conn);

?>


<?php include('templates/header.php'); ?>

<h4 class='center grey-text'>All Users' Blogs</h4>

<div class="row">
    <div class="col s11 l6 offset-l3"> 
        <form method="GET" action="">
            <div class="input-field">
                <i class="material-icons prefix">search</i></label>
                <input id="search" type="text" name="search" class="validate white">
                <label for="search" class="placeholder">Search Blogs by Title, Topic, or Author</label>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <div class="row d-flex flex-wrap">
    <?php foreach($blogs as $blog): ?>
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
<?php endforeach; ?>



    </div>
</div>

<?php include('templates/footer.php'); ?>

<script>
function truncateText(element, maxLines) {
    // Get the line height and font size of the element
    var computedStyle = window.getComputedStyle(element);
    var lineHeight = parseFloat(computedStyle.lineHeight);
    var fontSize = parseFloat(computedStyle.fontSize);

    // Calculate the maximum height based on the number of lines
    var maxHeight = lineHeight * maxLines;

    // Set the max-height and overflow properties
    element.style.maxHeight = maxHeight + 'px';
    element.style.overflow = 'hidden';
    element.style.display = '-webkit-box';
    element.style.WebkitBoxOrient = 'vertical';

    // Check if truncation is applied
    if (element.offsetHeight > element.parentNode.offsetHeight) {
        // Add ellipsis if truncation is applied
        element.style.textOverflow = 'ellipsis';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var blogTitles = document.querySelectorAll('.blog-title');

    blogTitles.forEach(function (title) {
        truncateText(title, 9);
    });
});

document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('search');
        var blogContainer = document.querySelector('.row.d-flex.flex-wrap');

        searchInput.addEventListener('input', function () {
            var searchValue = searchInput.value.trim();

            // Use AJAX to fetch and update blogs based on the search input
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Replace the current blogs with the fetched ones
                    blogContainer.innerHTML = xhr.responseText;
                }
            };
            xhr.open('GET', 'utilities/fetch_blogs.php?search=' + encodeURIComponent(searchValue), true);
            xhr.send();
        });
    });

</script>


</html>