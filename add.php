<?php

include('config/db_connect.php');

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: authentication/login.php");
    exit();
}

$title = $topic = $content = '';
$errors = array('title' =>'', 'topic'=>'', 'content' => '');

    if(isset($_POST['submit'])){
    
//check title
if(empty($_POST['title'])){
    $errors['title'] = 'Blog must have a title';
} else{
    $title = $_POST['title'];
}
//check topic
if(empty($_POST['topic'])){
    $errors['topic'] = 'Blog must have a topic';
} else{
    $topic = $_POST['topic'];
}

//check content
if(empty($_POST['content'])){
    $errors['content'] = 'You need to write the main content of your blog <br/>';
} else{
    $content = $_POST['content'];
}

if (array_filter($errors)) {
    // echo 'errors in form';
} else {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    // set $topic after the conversion
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);

        // Convert newlines to HTML line breaks
        $content = $_POST['content'];
        $content = str_replace("\r\n", "\n", $content);
        $content = nl2br($content);

        // Replace HTML line breaks with spaces
        $content = str_replace('<br />', ' ', $content);

    $userId = $_SESSION['user_id']; 

    // Use prepared statement
    $stmt = $conn->prepare("INSERT INTO blogs (title, topic, content, user_id, date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $title, $topic, $content, $userId);


        // execute the statement
        if ($stmt->execute()) {
            // success
            header('Location: index.php');
        } else {
            // error
            echo 'query error: ' . $stmt->error;
        }

        // close the statement
        $stmt->close();
    }
}
// end POST check

?>

<?php include('templates/header.php'); ?>

<section class="container grey-text xxs3 xs4 s6">
    <h4 class="center">Write a Blog</h4>
    <form action="add.php" method="POST" class="white">
        <div class="row">
            <div class="input-field col s12 m6">
                <label for="">Blog Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title) ?>">
                <div class="red-text"><?php echo $errors['title'] ?></div>
            </div>
            <div class="input-field col s12 m6">
                <label for="">Topic</label>
                <input type="text" name="topic" value="<?php echo htmlspecialchars($topic) ?>">            
                <div class="red-text"><?php echo $errors['topic'] ?></div>
            </div>
        </div>
        <div class="input-field">
            <label for="content" style="font-size: 14   px;">Blog Content</label>
            <textarea id="content" name="content" class="materialize-textarea auto-resize" rows="1"><?php echo htmlspecialchars($content) ?></textarea>
            <div class="red-text"><?php echo $errors['content'] ?></div>
        </div>
        <div class="center">
            <input type="submit" name='submit' value="Submit" class="btn brand z-depth-0">
        </div>
    </form>
</section>


<?php include('templates/footer.php'); ?>


</html>
