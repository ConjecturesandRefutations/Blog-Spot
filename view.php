<?php

include('config/db_connect.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}


if (isset($_POST['delete'])) {
    $id_to_delete = mysqli_real_escape_string($conn, $_POST['id_to_delete']);

    // Retrieve the 'is_draft' status of the blog
    $draftSql = "SELECT is_draft FROM blogs WHERE id = $id_to_delete";
    $draftResult = mysqli_query($conn, $draftSql);
    $blog = mysqli_fetch_assoc($draftResult);
    $isDraft = $blog['is_draft'];

    if ($isDraft == 0) {
        // If the deleted blog is not a draft, redirect to index.php
        $redirectLocation = 'index.php';
    } else {
        // If the deleted blog is a draft, redirect to drafts.php
        $redirectLocation = 'drafts.php?id=' . $_SESSION['user_id'];
    }

    // Proceed with deletion
    $sql = "DELETE FROM blogs WHERE id = $id_to_delete";

    if (mysqli_query($conn, $sql)) {
        // Success
        header("Location: $redirectLocation");
        exit(); // Ensure script execution stops after redirection
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

// Handling the Edit Submission
if (isset($_POST['id_to_edit'])) {
    $id_to_edit = mysqli_real_escape_string($conn, $_POST['id_to_edit']);
    $edited_title = mysqli_real_escape_string($conn, $_POST['title']);
    $edited_content = mysqli_real_escape_string($conn, $_POST['content']);
    $edited_topic = mysqli_real_escape_string($conn, $_POST['topic']); // Add this line for edited topic

    // Perform the update in the database
    $sql = "UPDATE blogs SET title = '$edited_title', content = '$edited_content', topic = '$edited_topic', last_updated = CURRENT_TIMESTAMP WHERE id = $id_to_edit";

    if (mysqli_query($conn, $sql)) {
    } else {
        echo 'query error: ' . mysqli_error($conn);
    }
}

if (isset($_POST['publish'])) {
    $id_to_publish = mysqli_real_escape_string($conn, $_POST['id_to_publish']);

    // Update the 'is_draft' property to 0
    $publishSql = "UPDATE blogs SET is_draft = 0, last_updated = CURRENT_TIMESTAMP WHERE id = $id_to_publish";

    if (mysqli_query($conn, $publishSql)) {
        header('Location: index.php');
        exit();
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

if (isset($_POST['draft'])) {
    $id_to_draft = mysqli_real_escape_string($conn, $_POST['id_to_draft']);

    // Update the 'is_draft' property to 1
    $draftSql = "UPDATE blogs SET is_draft = 1, last_updated = CURRENT_TIMESTAMP WHERE id = $id_to_draft";

    if (mysqli_query($conn, $draftSql)) {
        header("Location: drafts.php?id=" . $_SESSION['user_id']);
        exit();
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

// Handle Featured Image Deletion
if (isset($_POST['delete_featured_image'])) {
    $id_to_delete_featured_image = mysqli_real_escape_string($conn, $_POST['id_to_delete_featured_image']);

    // Update the 'featured_image' property to empty string
    $deleteImageSql = "UPDATE blogs SET featured_image = '' WHERE id = $id_to_delete_featured_image";

    if (mysqli_query($conn, $deleteImageSql)) {
        header('Location: view.php?id=' . $id_to_delete_featured_image);
        exit();
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

// Check GET request id parameter
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Define user interaction variables
    $userLiked = false;
    $userDisliked = false;

    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        // Check if the user has liked or disliked the blog post
        $checkSql = "SELECT feedback_type FROM likes WHERE user_id = $userId AND blog_id = $id";
        $checkResult = mysqli_query($conn, $checkSql);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $feedbackType = mysqli_fetch_assoc($checkResult)['feedback_type'];
            if ($feedbackType == 'like') {
                $userLiked = true;
            } elseif ($feedbackType == 'dislike') {
                $userDisliked = true;
            }
        }
    }

    // Make sql with JOIN to get user information
    $sql = "SELECT blogs.*, user.name as author_name,
            SUM(LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1) AS word_count
            FROM blogs
            INNER JOIN user ON blogs.user_id = user.user_id
            WHERE blogs.id = $id";

    // Get the query result
    $result = mysqli_query($conn, $sql);

    // Fetch result in array format
    $blog = mysqli_fetch_assoc($result);    

    // Strip HTML tags from the content
    $stripped_content = strip_tags($blog['content']);

    // Count words in stripped content
    $word_count = str_word_count($stripped_content);
    
    // Add word count to the $blog array
    $blog['word_count'] = $word_count;

    mysqli_free_result($result);

    // Fetch feedback for the current blog post
    $feedbackSql = "SELECT feedback.*, user.name as feedback_author
                    FROM feedback
                    INNER JOIN user ON feedback.user_id = user.user_id
                    WHERE feedback.blog_id = $id
                    ORDER BY feedback.feedback_id DESC";  // Order by feedback ID in descending order
    
    $feedbackResult = mysqli_query($conn, $feedbackSql);
    
    // Fetch all feedback results
    $feedback = mysqli_fetch_all($feedbackResult, MYSQLI_ASSOC);
    
    // Free the feedback result
    mysqli_free_result($feedbackResult);

    mysqli_close($conn);
}

// Handle Image Upload
if (isset($_FILES['featured_image']) && isset($id)) { // Check if 'id' is set
    $file = $_FILES['featured_image'];

    // File properties
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    // File extension
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $file_ext = strtolower($file_ext);

    // Allowed extensions
    $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');

    if (in_array($file_ext, $allowed)) {
        if ($file_error === 0) {
            if ($file_size <= 2097152) { // 2MB limit
                // Generate unique filename
                $file_new_name = uniqid('', true) . '.' . $file_ext;

                // Define file destination
                $file_destination = 'uploads/' . $file_new_name;

                if (move_uploaded_file($file_tmp, $file_destination)) {
                    // File uploaded successfully
                    // Save only the image name to the database, not the full path
                    $image_name = $file_new_name;
                    $sql = "UPDATE blogs SET featured_image = '$image_name' WHERE id = $id";
                    // Execute SQL query
                    mysqli_query($conn, $sql);

                    // Redirect or display success message
                    header('Location: view.php?id=' . $id);
                    exit();
                } else {
                    // Error uploading file
                    echo 'Error uploading file.';
                }
            } else {
                // File too large
                echo 'File size exceeds limit.';
            }
        } else {
            // Error uploading file
            echo 'Error uploading file.';
        }
    } else {
        // Invalid file type
        echo 'Invalid file type.';
    }
}

// Include HTMLPurifier library
require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'a[href|target],strong,b,em,i,u,p,ul,ol,li,br,img[src|alt|width|height|style],h1,h2,h3,h4,h5,h6,span[style]');
$config->set('HTML.ForbiddenElements', 'script,iframe,embed,object');

$purifier = new HTMLPurifier($config);

// Purify the content before displaying
$clean_content = $purifier->purify($blog['content']);

?>

<?php include('templates/header.php'); ?>

<div class="container grey-text text-darken-4">
  <?php if (isset($blog) && $blog): ?>

    <?php
    // Check if the logged-in user is the author of the blog
    $loggedInUserId = $_SESSION['user_id'] ?? null; // Get the logged-in user ID from the session
    $authorId = $blog['user_id'];

    if ($loggedInUserId === $authorId): // Show buttons only if the logged-in user is the author 
    ?>

    <!-- Delete and Edit form -->
    <div class='buttons'>

    <form id="editForm" action="view.php" method="POST">
          <input type="hidden" name="id_to_edit" value="<?php echo $blog['id']; ?>">
          <input type="hidden" name="title" id="editedTitle" value="<?php echo htmlspecialchars($blog['title']); ?>">
          <input type="hidden" name="topic" id="editedTopic" value="<?php echo htmlspecialchars($blog['topic']); ?>">
          <input type="hidden" name="content" id="editedContent" value="<?php echo htmlspecialchars($blog['content']); ?>">
          <button type="button" id="editButton" class="btn orange lighten-3 z-depth-0" onclick="toggleEdit()">Edit</button>
       </form>

        <form action="view.php" method="POST" onsubmit="return confirmDelete();">
            <input type="hidden" name="id_to_delete" value="<?php echo $blog['id']; ?>">
            <input type="submit" name="delete" value="delete" class="btn red lighten-3 z-depth-0">
        </form>

        <?php if ($blog['is_draft'] == 1): ?>
            <!-- Display 'Publish' button only if is_draft is set to 1 -->
            <form action="view.php" method="POST" class="publish">
                <input type="hidden" name="id_to_publish" value="<?php echo $blog['id']; ?>">
                <button type="submit" name="publish" class="btn green lighten-3 z-depth-0">Publish</button>
            </form>
        <?php endif; ?>

        <?php if ($blog['is_draft'] == 0): ?>
            <!-- Display 'Publish' button only if is_draft is set to 0 -->
            <form action="view.php" method="POST">
                <input type="hidden" name="id_to_draft" value="<?php echo $blog['id']; ?>">
                <button type="submit" name="draft" class="btn brand lighten-3 z-depth-0">Draft</button>
            </form>
        <?php endif; ?>

    </div>

<?php endif; ?>

<?php if (isset($loggedInUserId) && $loggedInUserId !== $authorId): ?> <!-- Only allow non-authors to add feedback -->
    <!-- Feedback Button -->
    <form class="feedback" id="feedbackForm" action="view.php" method="POST">
        <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
        <textarea id="feedbackTextarea" name="feedback_text" placeholder="Add your feedback..." required style="display: none;"></textarea>
        <button type="submit" name="submit_feedback" id="feedbackButton" class="btn blue lighten-3 z-depth-0">Add Feedback</button>

<?php endif; ?>
        <div class="thumbs">
            <?php
            // Check if the user has liked the blog
            if ($userLiked) {
                // If the user has liked the blog, color the thumbs-up icon black
                echo '<i class="material-icons thumb_up black-text">thumb_up</i>';
            } else {
                // If the user has not liked the blog, keep the thumbs-up icon blue
                echo '<i class="material-icons thumb_up baby-blue">thumb_up</i>';
            }
            ?>
            <div class="count-wrapper" id="like_count">
                <span class="blue-text count-link"><?php echo $blog['likes']; ?></span>
            </div>
            <?php
            // Check if the user has disliked the blog
            if ($userDisliked) {
                // If the user has disliked the blog, color the thumbs-down icon black
                echo '<i class="material-icons thumb_down black-text">thumb_down</i>';
            } else {
                // If the user has not disliked the blog, keep the thumbs-down icon blue
                echo '<i class="material-icons thumb_down baby-blue">thumb_down</i>';
            }
    ?>
                <div class="count-wrapper" id="dislike_count">
                <span class="blue-text count-link"><?php echo $blog['dislikes']; ?></span>
            </div>
</div>

<div id="feedback-modal" class="modal">
    <div class="modal-content">
        <h4 style="font-size: 25px;">Users</h4>
        <hr>
        <ul class="modal-list">
            <!-- Users will display here -->
        </ul>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn-flat">Close</button>
    </div>
</div>

    </form>

    <h4 id="editableTitle" class='center' contenteditable="false"><?php echo htmlspecialchars($blog['title']); ?></h4>
    <hr>
    <p class='center grey-text word-count' style='font-weight: bold;'>Word Count: <?php echo $blog['word_count']; ?></p>
    <p class='center grey-text text-darken-3' style="font-weight: bold;">Author: <a href="profile.php?id=<?php echo $blog['user_id']; ?>"><?php echo htmlspecialchars($blog['author_name']); ?></a></p>
    <p class='center'>Topic: <span id="editableTopic" contenteditable="false" style="font-style:italic"><?php echo htmlspecialchars($blog['topic']); ?></span></p>
    <p class='center' contenteditable="false">Created On: <span style='font-style: italic;'><?php echo date('d M Y', strtotime($blog['date'])); ?></span></p>
    <p class='center' contenteditable="false">Last Updated: <span style='font-style: italic;'><?php echo date('d M Y H:i:s', strtotime($blog['last_updated'])); ?></span></p>
    
<div class="row featured-image" style="display:none;">
    <div class="flex-featured">
        <!-- First column for the first button -->
        <div class="featured-item">
            <!-- Custom styled button -->
            <label for="featured_image_input" class="custom-file-upload">
                <?php echo ($blog['featured_image'] !== '') ? 'Change Featured Image' : 'Add Featured Image'; ?>
            </label>
            <!-- Actual file input hidden from view -->
            <input type="file" name="featured_image" id="featured_image_input" accept="image/*" onchange="uploadFeaturedImage(<?php echo $id; ?>)" style="display: none;">
        </div>

        <!-- Second column for the second button (if featured image exists) -->
        <?php if (!empty($blog['featured_image'])): ?>
            <div class="featured-item">
                <!-- Display the 'Delete Featured Image' button -->
                <form action="view.php" method="POST">
                    <input type="hidden" name="id_to_delete_featured_image" value="<?php echo $blog['id']; ?>">
                    <button type="submit" name="delete_featured_image" class="custom-file-upload delete_featured_image">Remove Featured Image</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($blog['featured_image'])): ?>
    <!-- Display the featured image with a link to open the modal -->
    <div class="center">
        <a class="modal-trigger" href="#imageModal">
            <img class="faviconTwo" src="uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="Featured Image">
        </a>
    </div>

    <!-- Modal Structure -->
    <div id="imageModal" class="modal">
        <div class="modal-content center">
            <!-- Display the featured image inside the modal -->
            <img class="modal-image" src="uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="Featured Image">
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        </div>
    </div>
<?php endif; ?>

    
    <hr>
    <div id="editableContent" contenteditable="false">
        <?php echo $clean_content; ?>
    </div>
   
    <?php else: ?>

    <?php endif; ?>

<!-- Display feedback -->
<?php if (isset($feedback) && !empty($feedback)): ?>
    <hr>
    <div class="feedback-section center" id="feedback-section">
        <div class="card-panel teal lighten-5">
        <h5 class="center-align">User Feedback</h5>
            <ul class="collection">
                <?php foreach ($feedback as $fb): ?>
                    <li class="collection-item" id="feedback_<?php echo $fb['feedback_id']; ?>">
                        <p>
                            <strong><?php echo htmlspecialchars($fb['feedback_author']); ?>:</strong>
                            <span class="feedback-text"><?php echo nl2br(htmlspecialchars($fb['feedback_text'])); ?></span>
                        </p>
                        
                        <?php if ($fb['user_id'] == $loggedInUserId): ?>
                            <!-- Edit and delete options for user's own feedback -->
                            <button type="button" class="btn orange lighten-3 z-depth-0 edit-feedback-btn" data-feedback-id="<?php echo $fb['feedback_id']; ?>">Edit</button>
                            <form class="delete-feedback-form" action="utilities/delete_feedback.php" method="POST" style="display: inline;">
                                <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                                <input type="hidden" name="blog_id" value="<?php echo $fb['blog_id']; ?>">
                                <button type="submit" name="delete_feedback" class="btn red lighten-3 z-depth-0">Delete</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

</div>

<?php include('templates/footer.php'); ?>

<script>

$(document).ready(function() {
    $('#feedbackForm').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Prepare form data
        var formData = {
            feedback_text: $('#feedbackTextarea').val(),
            blog_id: $('input[name="blog_id"]').val()
        };

        // Perform the AJAX request
        $.ajax({
            type: 'POST',
            url: 'utilities/write_feedback.php',
            data: formData,
            success: function(response) {
                // Refresh the page after successful feedback submission
                window.scrollTo(0, document.body.scrollHeight);
                location.reload();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle error - display a message
                $('#feedbackResponse').html('<p>Error: ' + errorThrown + '</p>');
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM content loaded"); // Debug statement

    let elems = document.querySelectorAll('.modal');
    console.log("Modal elements found:", elems.length); // Debug statement
    let instances = M.Modal.init(elems);

    // Prevent default behavior of anchor links with class 'modal-trigger'
    let modalTriggers = document.querySelectorAll('.modal-trigger');
    console.log("Modal triggers found:", modalTriggers.length); // Debug statement
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(event) {
            console.log("Modal trigger clicked"); // Debug statement
            event.preventDefault(); // Prevent default anchor behavior

            // Optionally, open the modal manually
            let modalId = trigger.getAttribute('href').substring(1); // Get the modal ID
            let modalInstance = M.Modal.getInstance(document.getElementById(modalId));
            modalInstance.open();
        });
    });
});

$(document).ready(function() {
    
    $('.material-icons').click(function() {
        let blogId = <?php echo $blog['id']; ?>;
        let action = $(this).hasClass('thumb_up') ? 'like' : 'dislike';

        console.log("Blog ID: " + blogId);
        console.log("Action: " + action);

        let iconElement = $(this);

        // Check if user is logged in
        <?php if(isset($_SESSION['user_id'])) { ?>
            $.ajax({
                url: 'utilities/update_likes.php',
                type: 'POST',
                data: { id: blogId, action: action },
                success: function(response) {
                    console.log("Server response: " + response);
                    let jsonResponse = JSON.parse(response);
                    if (jsonResponse.status === 'success') {
                        // Update the UI to reflect the new like/dislike count
                        $('#like_count').text(jsonResponse.likes);
                        $('#dislike_count').text(jsonResponse.dislikes);

                        // Determine the opposite action and select the opposite icon
                        let oppositeAction = action === 'like' ? 'dislike' : 'like';
                        let oppositeIcon = $('.material-icons.' + (oppositeAction === 'like' ? 'thumb_up' : 'thumb_down'));

                        // Toggle the color of the clicked icon
                        if (iconElement.hasClass('black-text')) {
                            iconElement.removeClass('black-text').addClass('baby-blue');
                        } else {
                            iconElement.removeClass('baby-blue').addClass('black-text');
                            // Ensure the opposite icon is blue when the clicked icon is changed to black
                            oppositeIcon.removeClass('black-text').addClass('baby-blue');
                        }
                    } else {
                        alert('Error updating likes/dislikes: ' + jsonResponse.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + ' ' + error);
                }
            });
        <?php } else { ?>
            // Alert user to login
            alert('You must login to like or dislike a blog');
        <?php } ?>
    });
    
    $('#like_count').click(function() {
    let blogId = <?php echo $blog['id']; ?>;
    fetchFeedback(blogId, 'like', 'All Likes');
});

$('#dislike_count').click(function() {
    let blogId = <?php echo $blog['id']; ?>;
    fetchFeedback(blogId, 'dislike', 'All Dislikes');
});

});

function toggleEdit() {
    const title = document.getElementById('editableTitle');
    const content = document.getElementById('editableContent');
    const topic = document.getElementById('editableTopic');
    const editedTitleInput = document.getElementById('editedTitle');
    const editedContentInput = document.getElementById('editedContent');
    const editedTopicInput = document.getElementById('editedTopic');
    const editButton = document.getElementById('editButton');

    if (title.contentEditable === 'true') {
        // If in edit mode, toggle back to read-only
        title.contentEditable = 'false';
        content.contentEditable = 'false';
        topic.contentEditable = 'false';

        editButton.innerHTML = 'Edit';
        removeBorder(title, content, topic);

        // Extract only the topic text (text after the colon)
        const topicText = topic.innerText
        editedTitleInput.value = title.innerText
        editedContentInput.value = content.innerHTML
        editedTopicInput.value = topicText;

        submitForm();

        // Destroy TinyMCE when leaving edit mode
        initializeTinyMCE(false);
    } else {
        // If not in edit mode, toggle to edit mode
        title.contentEditable = 'true';
        content.contentEditable = 'true';
        topic.contentEditable = 'true';

        // Initialize TinyMCE when entering edit mode
        initializeTinyMCE(true);

        editButton.innerHTML = 'Save';
        addBorder(title, content, topic);
    }

    let featuredImageButton = document.querySelector('.featured-image')
    if(featuredImageButton.style.display === "none"){
        featuredImageButton.style.display = "block";
    } else if(featuredImageButton.style.display === "block"){
        featuredImageButton.style.display = "none";
    }

}

function decodeEntities(encodedString) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = encodedString;
    return textarea.value;
}


function addBorder(...elements) {
    elements.forEach(element => {
        element.style.border = '2px solid red';
    });
}

function removeBorder(...elements) {
    elements.forEach(element => {
        element.style.border = 'none';
    });
}

function submitForm() {
    const form = document.getElementById('editForm');
    const editedTitleInput = document.getElementById('editedTitle');
    const editedContentInput = document.getElementById('editedContent');
    const editedTopicInput = document.getElementById('editedTopic');
    const contentEditor = tinymce.get('editableContent');
    const previousFeaturedImage = $('.faviconTwo').attr('src'); // Store the current image URL

    // Set the values in the hidden input fields
    editedTitleInput.value = document.getElementById('editableTitle').innerText;
    editedContentInput.value = contentEditor.getContent();
    editedTopicInput.value = document.getElementById('editableTopic').innerText;

    let formData = new FormData(form);

    fetch(form.action, {
        method: form.method,
        body: formData,
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(responseText => {
            console.log('Response Text:', responseText);
            // Check if the featured image has been updated
            const currentFeaturedImage = $('.faviconTwo').attr('src');
            if (currentFeaturedImage !== previousFeaturedImage) {
                // If the featured image has been updated, nothing needs to be done as it's already displayed
                console.log('Featured image updated successfully.');
            } else {
                // If the featured image hasn't been updated, reapply the previous image URL
                $('.faviconTwo').attr('src', previousFeaturedImage);
                console.log('Featured image retained.');
            }

            // Reload the page after successful submission
            location.reload();
        })
        .catch(error => {   
            console.error('Error in fetch request:', error);
            alert('An error occurred during form submission. Please try again.\n\n' + error);
        });
}

function initializeTinyMCE(editMode) {
    if (editMode) {
        tinymce.init({
            selector: '#editableContent',
            plugins: 'lists image charmap print preview hr anchor pagebreak link',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            autosave_ask_before_unload: false,
            height: 300,
            content_css: [
                '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
                '//www.tiny.cloud/css/codepen.min.css'
            ],
                // Disallow dangerous tags
                invalid_elements: 'script,iframe,embed,object',
                contextmenu: false
        });
    } else {
        tinymce.remove('#editableContent');
    }
}

let feedbackButton = document.getElementById('feedbackButton');
let feedbackForm = document.getElementById('feedbackForm');
let feedbackTextarea = document.querySelector('textarea[name="feedback_text"]');

feedbackButton.addEventListener('click', function() {
    // Toggle the visibility of the textarea
    feedbackTextarea.style.display = feedbackTextarea.style.display === 'none' ? 'block' : 'none';

    // Change the button text based on the textarea visibility
    feedbackButton.innerHTML = feedbackTextarea.style.display === 'none' ? 'Add Feedback' : 'Submit';
});

document.addEventListener('DOMContentLoaded', function () {
    const editFeedbackButtons = document.querySelectorAll('.edit-feedback-btn');

    editFeedbackButtons.forEach(button => {
        button.addEventListener('click', function () {
            const feedbackId = this.getAttribute('data-feedback-id');
            const feedbackTextElement = document.querySelector(`#feedback_${feedbackId} .feedback-text`);
            const editedFeedbackText = prompt('Edit your feedback:', feedbackTextElement.innerText);

            if (editedFeedbackText !== null) {
                // Update the feedback text on the page
                feedbackTextElement.innerText = editedFeedbackText;

                // Update the feedback in the database using AJAX/Fetch
                updateFeedbackInDatabase(feedbackId, editedFeedbackText);
            }
        });
    });

    function updateFeedbackInDatabase(feedbackId, editedFeedbackText) {
        const formData = new FormData();
        formData.append('update_feedback', '1'); // Indicate the update_feedback parameter
        formData.append('feedback_id', feedbackId);
        formData.append('edited_feedback_text', editedFeedbackText);

        fetch('utilities/update_feedback.php', {
            method: 'POST',
            body: formData,
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(responseText => {
                console.log('Feedback updated successfully:', responseText);
                console.log('Feedback ID:', feedbackId, 'Edited Text:', editedFeedbackText);
            })
            .catch(error => {
                console.error('Error updating feedback:', error);
                alert('An error occurred during feedback update. Please try again.\n\n' + error);
            });
    }
});

document.addEventListener('DOMContentLoaded', function () {

    // Initialize TinyMCE with editMode set to false
    initializeTinyMCE(false);
});

function confirmDelete() {
    return confirm("Are you sure you want to delete this blog?");
}

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
        // Check if the image tag exists
        let featuredImage = $('.faviconTwo');
        if (featuredImage.length === 0) {
            // Create a new image tag if it doesn't exist
            featuredImage = $('<img>').addClass('faviconTwo');
            $('.featured-image').append(featuredImage);
        }

        // Update the featured image src attribute with the new image URL
        let newImageUrl = responseData.featured_image;
        featuredImage.attr('src', newImageUrl);
        featuredImage.show(); // Make sure the image is visible
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

function fetchFeedback(blogId, type, heading) {
    $.ajax({
        url: 'utilities/fetch_feedback.php',
        type: 'POST',
        data: { id: blogId, type: type },
        success: function(response) {
            try {
                let jsonResponse = JSON.parse(response);
                if (jsonResponse.status === 'success') {
                    // Update heading
                    $('#feedback-modal h4').text(heading);

                    // Display the list of users in a modal
                    let usersList = jsonResponse.users.map(user => 
                        `<li style="display: flex; align-items: center; margin-bottom: 10px;">
                            <a href="profile.php?id=${user.id}" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                                <img src="${user.profile_image}" alt="${user.name}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                                ${user.name}
                            </a>
                        </li>`
                    ).join('');
                    $('#feedback-modal .modal-list').html(usersList);

                    // Open the modal
                    $('#feedback-modal').modal('open');
                } else {
                    alert('Error fetching feedback: ' + jsonResponse.error);
                }
            } catch (e) {
                console.error('Error parsing response: ', e);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ' + status + ' ' + error);
        }
    });
}

</script>
