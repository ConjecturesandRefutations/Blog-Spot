<?php
    $mysqli = require __DIR__ . "/config/db_connect.php";

    $searchTerm = '';

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, redirect to the login page
        header("Location: authentication/login.php");
        exit();
    }
    
    if (!isset($_GET['id']) || $_SESSION['user_id'] != $_GET['id']) {
        // "id" parameter is not present in the URL or doesn't match the logged-in user's ID
        // Redirect to the login page
        header("Location: authentication/login.php");
        exit();
    }
    
    

    if (isset($_GET["id"])) {
        $user_id = $_GET["id"];

        $stmt_profile_user = $mysqli->prepare("SELECT user.user_id, user.name
                                    FROM user
                                    WHERE user.user_id = ?");
        $stmt_profile_user->bind_param("i", $user_id);
        $stmt_profile_user->execute();
        $result_profile_user = $stmt_profile_user->get_result();

        if ($result_profile_user && $result_profile_user->num_rows > 0) {
            $profileUser = $result_profile_user->fetch_assoc();
        } else {
            header("Location: error_page.php");
            exit();
        }

        $stmt_profile_user->close();

        if (isset($_GET['search'])) {
            $searchTerm = $_GET['search'];
        }
           

        $stmt_blogs = $mysqli->prepare("SELECT blogs.title, blogs.date, blogs.last_updated, blogs.content, blogs.id, blogs.topic, user.user_id, user.name as author_name
            FROM blogs
            INNER JOIN user ON blogs.user_id = user.user_id
            WHERE blogs.user_id = ? AND (blogs.title LIKE ? OR blogs.topic LIKE ?) AND blogs.is_draft = 1
            ORDER BY COALESCE(blogs.last_updated, blogs.date) DESC, blogs.id DESC");
        $likeParam = "%$searchTerm%";
        $stmt_blogs->bind_param("iss", $user_id, $likeParam, $likeParam);
        $stmt_blogs->execute();
        $result_blogs = $stmt_blogs->get_result();

        $blogs = $result_blogs->fetch_all(MYSQLI_ASSOC);

        $numBlogs = count($blogs);

        $stmt_blogs->close();
    } else {
        header("Location: authentication/login.php");
        exit();
    }

    function calculateWordCount($content) {
        $wordCount = substr_count($content, ' ') + 1;
        return $wordCount;
    }

    mysqli_close($mysqli);
?>

<?php include('templates/header.php'); ?>

<div class="wrapping center">
    <div class="info">
        <p style="font-weight:bold">These are your Drafts</p>
        <p style="font-weight:bold">Drafts are only visible to you.</p>
        <p style="font-weight:bold">To make a draft public, you must publish it</p>
        <p class="center grey-text text-darken-2">Total Drafts: <?php echo $numBlogs; ?></p>
    </div>
</div>

<!-- Search Bar -->
<div class="row search-profile">
    <div class="col s12 l6 offset-l3">
        <form action="<?php echo "profile.php" . (isset($profileUser['user_id']) ? "?id={$profileUser['user_id']}" : ''); ?>" method="GET" id="searchForm">
            <div class="input-field col s12">
                <i class="material-icons prefix">search</i>
                <input class="white" type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>" />
                <label for="search" class="profile-placeholder">
                        Search Your Drafts by Title or Topic
                </label>
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
                        <p style="font-size: smaller">Created On: <?php echo date('d-m-Y', strtotime($blog['date'])); ?></p>
                        <p style="font-size: smaller">Last Updated: <?php echo date('d M Y H:i:s', strtotime($blog['last_updated'])); ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('templates/footer.php'); ?>
