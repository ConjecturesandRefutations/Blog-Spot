<?php
include('config/db_connect.php');

// Initialize the search variable
$search = '';

// Check if the search parameter is provided in the URL
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    $sql = "SELECT user.user_id, user.name, user.profile_image, COUNT(blogs.id) as numBlogs, 
                MAX(blogs.topic) as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id AND blogs.is_draft = 0
            WHERE user.name LIKE '%$search%' OR blogs.topic LIKE '%$search%'
            GROUP BY user.user_id, user.name, user.profile_image
            ORDER BY user.name ASC";
} else {
    // Default query without search
    $sql = "SELECT user.user_id, user.name, user.profile_image, COUNT(blogs.id) as numBlogs, 
                MAX(blogs.topic) as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id AND blogs.is_draft = 0
            GROUP BY user.user_id, user.name, user.profile_image
            ORDER BY user.name ASC";
}

// Make the query and get the result
$result = mysqli_query($conn, $sql);

// Fetch the resulting rows as an array
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Function to calculate word count with HTML tags stripped
function calculateWordCount($content) {
    // Remove HTML tags from the content
    $contentWithoutTags = strip_tags($content);
    
    // Count words by counting spaces
    $wordCount = str_word_count($contentWithoutTags);
    
    return $wordCount;
}
?>

<?php include('templates/header.php'); ?>

<h4 class='center grey-text'>All Users</h4>

<!-- Search Bar -->
<div class="row">
    <div class="col s12 l6 offset-l3"> 
        <form>
            <div class="input-field">
                <i class="material-icons prefix">search</i></label>
                <input id="search" type="text" name="search" class="validate white">
                <label for="search" class="placeholder">Search Users by Name or Favourite Topic</label>
            </div>
        </form>
    </div>
</div>

<!-- Add the missing container -->
<div class="container">
    <div class="row" id="user-list">
        <?php foreach ($users as $profileUser) : ?>
            <div class="col s12 m6 l4">
    <a href="profile.php?id=<?php echo $profileUser['user_id']; ?>" class="card-link">
        <div class="card <?php if (isset($_SESSION['user_id']) && $profileUser['user_id'] == $_SESSION['user_id']) echo 'grey lighten-3'; ?>">
            <div class="card-content">
                <div class="center-align">
                    <img src="<?php echo (!empty($profileUser['profile_image'])) ? $profileUser['profile_image'] : 'images/defaultProfile.jpg'; ?>" alt="Profile Image" class="circle responsive-img" style="width: 80px; height: 80px;">
                </div>
                <div class="user-info center-align">
                    <h6 class="black-text" style="font-weight: bold;"><?php echo htmlspecialchars($profileUser['name']); ?></h6>
                    <p class="grey-text text-darken-2">Total Blogs: <?php echo $profileUser['numBlogs']; ?></p>

                    <?php 
                    $totalWords = 0;
                    $blogsResult = mysqli_query($conn, "SELECT content FROM blogs WHERE user_id = {$profileUser['user_id']} AND is_draft = 0");
                    while ($blog = mysqli_fetch_assoc($blogsResult)) {
                        $totalWords += calculateWordCount($blog['content']);
                    }
                    mysqli_free_result($blogsResult);
                    ?>
                    <p class="grey-text text-darken-2">Total Words: <?php echo $totalWords; ?></p>
                    <p class="grey-text text-darken-2 favourite-topic">Favourite Topic: <?php echo htmlspecialchars($profileUser['favoriteTopic']); ?></p>
                </div>
            </div>
        </div>
    </a>
</div>

<?php endforeach; ?>

    </div>
</div>

<?php include('templates/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function fetchUsers(search) {
        $.ajax({
            type: "GET",
            url: "utilities/fetch_users.php",
            data: { search: search },
            dataType: "json",
            success: function(users) {
                var userList = $("#user-list");
                userList.empty();

                users.forEach(function(profileUser) {
                    var userCard = `
                        <div class="col s12 m6 l4" data-user-id="${profileUser.user_id}">
                           <a href="profile.php?id=${profileUser.user_id}" class="card-link">
                            <div class="card ${profileUser.isCurrentUser ? 'grey lighten-3' : ''}">
                                <div class="card-content">
                                    <div class="center-align">
                                        <img src="${profileUser.profile_image ? profileUser.profile_image : 'images/defaultProfile.jpg'}" 
                                            alt="Profile Image" class="circle responsive-img profile-all" style="width: 80px; height: 80px;">
                                    </div>
                                    <div class="user-info center-align">
                                        <h6 class="black-text user-name" style="font-weight: bold;">${profileUser.name}</h6>
                                        <p class="grey-text text-darken-2 num-blogs">Total Blogs: ${profileUser.numBlogs}</p>
                                        <p class="grey-text text-darken-2 total-words">Total Words: ${profileUser.totalWords}</p>
                                        <p class="grey-text text-darken-2 favourite-topic">Favourite Topic: ${profileUser.favoriteTopic}</p>
                                    </div>
                                </div>
                            </div>
                        </a>
`;
                    userList.append(userCard);
                });
            }
        });
    }

    // Event listener for input changes in the search bar
    $("#search").on("input", function() {
        var searchValue = $(this).val().trim();
        fetchUsers(searchValue);
    });
});
</script>