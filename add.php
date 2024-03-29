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

if(isset($_POST['submit']) || isset($_POST['draft'])) {
    
    // Check title
    if(empty($_POST['title'])){
        $errors['title'] = 'Blog must have a title';
    } else{
        $title = $_POST['title'];
    }
    
    // Check topic
    if(empty($_POST['topic'])){
        $errors['topic'] = 'Blog must have a topic';
    } else{
        $topic = $_POST['topic'];
    }

    // Check content
    if(empty($_POST['content'])){
        $errors['content'] = 'You need to write the main content of your blog <br/>';
    } else{
        $content = $_POST['content'];
    }

    if (array_filter($errors)) {
        // echo 'errors in form';
    } else {
        $title = $_POST['title'];
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $topic = mysqli_real_escape_string($conn, $_POST['topic']);

        // Convert newlines to HTML line breaks
        $content = $_POST['content'];
        $content = str_replace("\r\n", "\n", $content);
        $content = nl2br($content);

        // Replace HTML line breaks with spaces
        $content = str_replace('<br />', ' ', $content);

        $userId = $_SESSION['user_id']; 

        $isDraft = isset($_POST['draft']) ? 1 : 0;

        // Use prepared statement
        $stmt = $conn->prepare("INSERT INTO blogs (title, topic, content, user_id, date, is_draft, featured_image) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param("sssiss", $title, $topic, $content, $userId, $isDraft, $_FILES['featured_image']['name']);



        if ($stmt->execute()) {
            // success
            $blog_id = $stmt->insert_id; // Retrieve the inserted blog_id
            if ($isDraft) {
                header("Location: drafts.php?id=" . $_SESSION['user_id']);
            } else {
                // Pass the blog_id to the upload_featured_image.php script via AJAX
                echo '<script>';
                echo 'let blogId = ' . $blog_id . ';';
                echo 'uploadFeaturedImage();';
                echo '</script>';
                header('Location: index.php');
            }
        }
        

        // close the statement
        $stmt->close();
    }
}

?>

<?php include('templates/header.php'); ?>

<section class="container grey-text xxs3 xs4 s6">
    <h4 class="center">Write a Blog</h4>
    <form action="add.php" method="POST" class="white" enctype="multipart/form-data">
        <div class="row">
            <div class="input-field col s12 m6">
                <label for="">Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title) ?>">
                <div class="red-text"><?php echo $errors['title'] ?></div>
            </div>
            <div class="input-field col s12 m6">
                <label for="">Topic</label>
                <input type="text" name="topic" value="<?php echo htmlspecialchars($topic) ?>">            
                <div class="red-text"><?php echo $errors['topic'] ?></div>
            </div>
        </div>
        <div class="row">

        <!-- Add featured image -->

        <div class="row">
            <!-- Custom styled button -->
            <label for="featured_image_input" class="custom-file-upload">
                Add Featured Image (Optional)
            </label>
            <!-- Actual file input hidden from view -->
            <input type="file" name="featured_image" id="featured_image_input" accept="image/*" onchange="uploadFeaturedImage()" style="display: none;">
            <img id="featuredImagePreview" src="#" alt="Featured Image Preview" style="display: none; max-width: 100px;">
        </div>


        <div class="input-field">
            <textarea id="content" name="content" class="materialize-textarea auto-resize" placeholder="Content"><?php echo htmlspecialchars($content) ?></textarea>
            <div class="red-text"><?php echo $errors['content'] ?></div>
        </div>
            <input type="hidden" name="action" value="draft">
            <input type="submit" name='draft' value="Save Draft" class="btn grey z-depth-0">
            <input type="submit" name='submit' value="Publish" class="btn green z-depth-0">
    </form>
</section>

<?php include('templates/footer.php'); ?>

    <script>
  document.addEventListener('DOMContentLoaded', function () {
      tinymce.init({
         selector: '#content',
         plugins: 'autolink lists link image charmap print preview hr anchor pagebreak',
         toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
         autosave_ask_before_unload: false,
         height: 300,
         content_css: [
            '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
            '//www.tiny.cloud/css/codepen.min.css'
         ]
      });
   }); 

   function uploadFeaturedImage(blogId) {
    let formData = new FormData();
    let fileInput = document.querySelector('input[type="file"]');
    let file = fileInput.files[0];

    formData.append('featured_image', file);
    formData.append('blog_id', blogId);

    $.ajax({
        type: 'POST',
        url: 'utilities/upload_featured_image.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Handle the response
            console.log(response);

            // Update the featured image preview on the page if upload was successful
            let responseData = JSON.parse(response);
            if (responseData.status === 'success') {
                // Update the featured image preview on the page
                $('#featuredImagePreview').attr('src', responseData.featured_image).show();
            } else {
                // Display error message
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