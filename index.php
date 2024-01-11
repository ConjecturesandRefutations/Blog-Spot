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


// A query to fetch blogs with user information
$sql = "SELECT blogs.title, blogs.date, blogs.id, blogs.topic, user.user_id, user.name as author_name,
                SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) AS word_count
         FROM blogs
         INNER JOIN user ON blogs.user_id = user.user_id
         WHERE blogs.title LIKE '%$search%'
            OR user.name LIKE '%$search%'
            OR blogs.topic LIKE '%$search%'
         GROUP BY blogs.id
         ORDER BY blogs.date DESC, blogs.id DESC";

// Make the query and get the result
$result = mysqli_query($conn, $sql);

// Fetch the resulting rows as an array
$blogs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Free result from memory
mysqli_free_result($result);

// Close connection
mysqli_close($conn);

?>

<?php include('templates/header.php'); ?>

<h4 class='center grey-text'>Feed</h4>

<div class="row">
    <div class="col s11 m6 offset-m3"> 
        <form method="GET" action="">
            <div class="input-field">
                <i class="material-icons prefix">search</i></label>
                <input id="search" type="text" name="search" class="validate white">
                <label for="search" class="placeholder">Search Blogs by Title, Category, or Author</label>
            </div>
        </form>
    </div>
</div>


<div class="container">
    <div class="row d-flex flex-wrap">
    <?php foreach($blogs as $blog): ?>
    <div class="col s12 m6">
    <a href="view.php?id=<?php echo $blog['id'] ?>" class="card-link"> <!-- Added anchor tag around the card -->
            <div class="card z-depth-0 blog-card">
                <div class="card-content blog-card-content">
                    <img src="images/favicon.png" class="favicon" alt="favicon">
                    <img src="images/Quill.jpg" class="quill" alt="Image of a quill">
                    <div class='center grey-text' style="font-weight: bold;"><?php echo "Word Count: " . $blog['word_count']; ?></div>
                    <h6 class='center grey-text text-darken-2 blog-title'><?php echo htmlspecialchars($blog['title']); ?></h6>
                    <p class='center grey-text text-darken-2' style="font-style: italic;"><span id="category">Category: </span><?php echo htmlspecialchars($blog['topic']); ?></p>
                    <p class='center grey-text text-darken-2' style="font-weight: bold;">Author: <?php echo htmlspecialchars($blog['author_name']); ?></p>
                    <div class='center grey-text' style="font-weight: bold;"><?php echo date('d M Y', strtotime($blog['date'])); ?></div>
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
