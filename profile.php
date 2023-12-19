<?php
$mysqli = require __DIR__ . "/config/db_connect.php";

$profileUser = null; // Initialize the $profileUser variable
$searchTerm = '';

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}


// Check if user_id is provided in the URL
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Use prepared statement to prevent SQL injection
    $stmt_profile_user = $mysqli->prepare("SELECT user.user_id, user.name, COUNT(blogs.id) as numBlogs, 
                                       SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                                       MAX(blogs.topic) as favoriteTopic,
                                       user.profile_image  -- Include profile_image in the select
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

        // Retrieve profile image path from session if available
        if (isset($_SESSION['profile_image'][$user_id])) {
            $profileUser['profile_image'] = $_SESSION['profile_image'][$user_id];
        }
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

function calculateWordCount($content) {
    // Count words by counting spaces
    $wordCount = substr_count($content, ' ') + 1;

    return $wordCount;
}

// Close connection
mysqli_close($mysqli);
?>

<?php include('templates/header.php'); ?>

<div class="wrapping center">

    <div class="info">
        <h4 class='center grey-text text-darken-2'><?php echo htmlspecialchars($profileUser['name']); ?></h4>
        <p class="center grey-text text-darken-2">Total Blogs: <?php echo $numBlogs; ?></p>
        <p class="center grey-text text-darken-2">Total Words: <?php echo $profileUser['totalWords']; ?></p> 
        <p class="center grey-text text-darken-2">Favorite Topic: <?php echo htmlspecialchars($profileUser['favoriteTopic']); ?></p>
    </div>

    <div class="image">
    <img id="profileImagePreview" src="<?php echo (!empty($profileUser['profile_image'])) ? $profileUser['profile_image'] : 'images/defaultProfile.jpg'; ?>" alt="Profile Image" class="responsive-img circle" style="width: 150px; height: 150px;">

    <!-- Form for profile image upload -->
    <form class="" action="<?php echo "profile.php" . (isset($profileUser['user_id']) ? "?id={$profileUser['user_id']}" : ''); ?>" method="POST" enctype="multipart/form-data" id="profileImageForm">
        <!-- Add input field for profile image upload -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUser['user_id']) : ?>
            <div class="file-field input-field">
                <div class="waves-effect waves-light select">
                    <span>Change Profile Image</span>
                    <input type="file" name="profile_image" id="profile_image_input" accept="image/*" onchange="uploadProfileImage()">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>


</div>


<!-- Search Bar -->
<div class="row search-profile">
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


<div class="container center" id="blogList">
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

<?php
// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Check if the logged-in user ID matches the profile user ID
    if ($_SESSION['user_id'] == $profileUser['user_id']) {
        // Display the delete account button
?>
        <div class="row">
            <div class="col s12 m6 offset-m3">
                <form action="delete_account.php" method="POST" id="deleteAccountForm">
                    <input type="hidden" name="user_id" value="<?php echo $profileUser['user_id']; ?>">
                    <button type="submit" class="btn red">Delete My Account</button>
                </form>
            </div>
        </div>
<?php
    }
}
?>

<?php include('templates/footer.php'); ?>


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

$(document).ready(function () {
        $('#deleteAccountForm').submit(function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete your account? This will also delete all your blogs')) {
                this.submit();
            }
        });
    });

    function uploadProfileImage() {
    var formData = new FormData($('#profileImageForm')[0]);
    formData.append('user_id', <?php echo $profileUser['user_id']; ?>);

    $.ajax({
        type: 'POST',
        url: 'upload_profile_image.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Parse the JSON response
            var responseData = JSON.parse(response);

            // Handle the response
            console.log(responseData);

            if (responseData.status === 'success') {
                // Update the profile image on the page
                $('#profileImagePreview').attr('src', responseData.profile_image);

                // You may update other information on the page as needed
                // Example: $('#someElement').text(responseData.someValue);
            } else {
                // Handle the error case
                console.error(responseData.message);
            }
        },
        error: function(error) {
            // Handle the error
            console.error(error);
        }
    });
}


</script>


