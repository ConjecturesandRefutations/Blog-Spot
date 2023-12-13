<?php
include('config/db_connect.php');

// Initialize the search variable
$search = '';

// Check if the search parameter is provided in the URL
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    // Modify the query to include the search condition
    $sql = "SELECT user.user_id, user.name, COUNT(blogs.id) as numBlogs, 
                   SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                   MAX(blogs.topic) as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id
            WHERE user.name LIKE '%$search%' OR blogs.topic LIKE '%$search%'
            GROUP BY user.user_id, user.name
            ORDER BY user.name ASC";
} else {
    // Default query without search
    $sql = "SELECT user.user_id, user.name, COUNT(blogs.id) as numBlogs, 
                   SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) as totalWords,
                   MAX(blogs.topic) as favoriteTopic
            FROM user
            LEFT JOIN blogs ON user.user_id = blogs.user_id
            GROUP BY user.user_id, user.name
            ORDER BY user.name ASC";
}

// Make the query and get the result
$result = mysqli_query($conn, $sql);

// Fetch the resulting rows as an array
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Free result from memory
mysqli_free_result($result);

// Close connection
mysqli_close($conn);

?>

<?php include('templates/header.php'); ?>

<h4 class='center grey-text'>All Users</h4>

<!-- Search Bar -->
<div class="row">
    <div class="col s12 m6 offset-m3"> 
        <form>
            <div class="input-field">
                <input id="search" type="text" name="search" class="validate">
                <label for="search">Search Users by Name or Favourite Topic</label>
            </div>
        </form>
    </div>
</div>

<!-- Add the missing container -->
<div class="container">
    <div class="row" id="user-list">
        <?php foreach ($users as $profileUser) : ?>
            <div class="col s12" style="border: 1px solid grey;">
                <a href="profile.php?id=<?php echo $profileUser['user_id']; ?>" class="card-content center grey-text text-darken-2">
                    <h6 style="font-weight: bold"><?php echo htmlspecialchars($profileUser['name']); ?></h6>
                    <p style="font-size: smaller">Total Blogs: <?php echo $profileUser['numBlogs']; ?></p>
                    <p style="font-size: smaller">Total Words: <?php echo $profileUser['totalWords']; ?></p>
                    <p style="font-size: smaller">Favorite Topic: <?php echo htmlspecialchars($profileUser['favoriteTopic']); ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
$(document).ready(function() {
    // Function to fetch and display users in real-time
    function fetchUsers(search) {
        $.ajax({
            type: "GET",
            url: "fetch_users.php",
            data: { search: search },
            dataType: "json",
            success: function(users) {
                var userList = $("#user-list");
                userList.empty();

                // Iterate through fetched users and append to the list
                users.forEach(function(profileUser) {
                    var userCard = `<div class="col s12" style="border: 1px solid grey;">
                                        <a href="profile.php?id=${profileUser.user_id}" class="card-content center grey-text text-darken-2">
                                            <h6 style="font-weight: bold">${profileUser.name}</h6>
                                            <p style="font-size: smaller">Total Blogs: ${profileUser.numBlogs}</p>
                                            <p style="font-size: smaller">Total Words: ${profileUser.totalWords}</p>
                                            <p style="font-size: smaller">Favorite Topic: ${profileUser.favoriteTopic}</p>
                                        </a>
                                    </div>`;
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

<?php include('templates/footer.php'); ?>
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
