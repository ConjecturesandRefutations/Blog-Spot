<?php
$mysqli = require __DIR__ . "/config/db_connect.php";

$profileUser = null; // Initialize the $profileUser variable
$searchTerm = '';

// Check if user_id is provided in the URL
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Use prepared statement to prevent SQL injection
    $stmt_profile_user = $mysqli->prepare("SELECT user.user_id, user.name, COUNT(blogs.id) as numBlogs, 
                                       SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                                       MAX(blogs.topic) as favoriteTopic
                                FROM user
                                LEFT JOIN blogs ON user.user_id = blogs.user_id
                                WHERE user.user_id = ?
                                GROUP BY user.user_id, user.name");
    $stmt_profile_user->bind_param("i", $user_id);
    $stmt_profile_user->execute();
    $result_profile_user = $stmt_profile_user->get_result();

    // Check if the user exists
    if ($result_profile_user && $result_profile_user->num_rows > 0) {
        $profileUser = $result_profile_user->fetch_assoc();
    } else {
        header("Location: error_page.php");
        exit();
    }

    $stmt_profile_user->close();

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

    // Get the number of blogs
    $numBlogs = count($blogs);

    // Free result from memory
    $stmt_blogs->close();
} else {
    echo "User ID not provided";
    exit();
}

// Close connection
mysqli_close($mysqli);

function calculateWordCount($content) {
    // Count words by counting spaces
    $wordCount = substr_count($content, ' ') + 1;

    return $wordCount;
}
?>

<?php include('templates/header.php'); ?>

<h4 class='center grey-text text-darken-2'><?php echo htmlspecialchars($profileUser['name']); ?></h4>
<p class="center grey-text text-darken-2">Total Blogs: <?php echo $numBlogs; ?></p>
<p class="center grey-text text-darken-2">Total Words: <?php echo $profileUser['totalWords']; ?></p> 
<p class="center grey-text text-darken-2">Favorite Topic: <?php echo htmlspecialchars($profileUser['favoriteTopic']); ?></p>

<div class="row">
    <div class="col s12 m6 offset-m3">
        <form action="<?php echo "profile.php" . (isset($profileUser['user_id']) ? "?id={$profileUser['user_id']}" : ''); ?>" method="GET" id="searchForm">
            <div class="input-field col s12">
                <i class="material-icons prefix">search</i></label>
                <input class="white" type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>" />
                <label for="search">Search <?php echo htmlspecialchars($profileUser['name']); ?>'s Blogs by Title or Topic</label>
            </div>
            <input type="hidden" name="id" value="<?php echo isset($profileUser['user_id']) ? $profileUser['user_id'] : ''; ?>">
        </form>
    </div>
</div>

<div class="container" id="blogList">
    <div class="row">
        <?php foreach($blogs as $blog): ?>
            <div class="col s12 profile-card" style="border: 1px solid grey;" >
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
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
 $(document).ready(function() {
    $('#search').on('input', function() {
        // Get the form data
        var formData = $('#searchForm').serialize();

        // Make an AJAX request to update the blog list
        $.ajax({
            type: 'GET',
            url: 'fetch_user_blogs.php', // Update the URL to the new file
            data: formData,
            success: function(response) {
                // Replace the content of the blog list
                $('#blogList').html(response);
            }
        });
    });
});
</script>

<?php include('templates/footer.php'); ?>

