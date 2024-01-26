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

    $sql = "DELETE FROM blogs WHERE id = $id_to_delete";

    if (mysqli_query($conn, $sql)) {
        //success
        header('Location: index.php');
    } else {
        //failure
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
    $sql = "UPDATE blogs SET title = '$edited_title', content = '$edited_content', topic = '$edited_topic' WHERE id = $id_to_edit";

    if (mysqli_query($conn, $sql)) {
        // Success
        // Optionally, you can redirect or handle success as needed
    } else {
        // Failure
        // Optionally, you can handle errors or take corrective actions here
        echo 'query error: ' . mysqli_error($conn);
    }
}



// Check GET request id parameter
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

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

// Handle Feedback Submission
if (isset($_POST['submit_feedback'])) {
    $blog_id = mysqli_real_escape_string($conn, $_POST['blog_id']);
    $user_id = $_SESSION['user_id'] ?? null; // Get the logged-in user ID from the session
    $feedback_text = mysqli_real_escape_string($conn, $_POST['feedback_text']);

    echo "User ID: $user_id, Blog ID: $blog_id";
    echo "User ID from Session: " . $_SESSION['user_id'] . ", Blog ID: $blog_id";


    // Insert feedback into the database
    $sql = "INSERT INTO feedback (user_id, blog_id, feedback_text) VALUES ('$user_id', '$blog_id', '$feedback_text')";

    if (mysqli_query($conn, $sql)) {
        // Success
        header('Location: view.php?id=' . $blog_id);
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

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

    </div>

<?php endif; ?>

<?php if (isset($loggedInUserId) && $loggedInUserId !== $authorId): ?> <!-- Only allow non-authors to add feedback -->
    <!-- Feedback Button -->
    <form class="feedback" id="feedbackForm" action="view.php" method="POST">
        <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
        <textarea id="feedbackTextarea" name="feedback_text" placeholder="Add your feedback..." required style="display: none;"></textarea>
        <button type="submit" name="submit_feedback" id="feedbackButton" class="btn blue lighten-3 z-depth-0">Add Feedback</button>
    </form>
<?php endif; ?>


    <h4 id="editableTitle" class='center' contenteditable="false"><?php echo htmlspecialchars($blog['title']); ?></h4>
    <hr>
    <p class='center grey-text word-count' style='font-weight: bold;'>Word Count: <?php echo $blog['word_count']; ?></p>
    <p class='center grey-text text-darken-3' style="font-weight: bold;">Author: <a href="profile.php?id=<?php echo $blog['user_id']; ?>"><?php echo htmlspecialchars($blog['author_name']); ?></a></p>
    <p class='center'>Topic: <span id="editableTopic" contenteditable="false" style="font-style:italic"><?php echo htmlspecialchars($blog['topic']); ?></span></p>
    <p class='center' contenteditable="false">Created on: <span style='font-style: italic;'><?php echo date('d M Y', strtotime($blog['date'])); ?></span></p>
    <hr>
    <div id="editableContent" contenteditable="false" style="white-space: pre-line;"><?php echo htmlspecialchars_decode($blog['content']); ?></div>
   
    <?php else: ?>

    <?php endif; ?>

<!-- Display feedback -->
<?php if (isset($feedback) && !empty($feedback)): ?>
    <hr>
    <div class="feedback-section center">
        <h5>User Feedback</h5>
        <ul>
            <?php foreach ($feedback as $fb): ?>
                <li id="feedback_<?php echo $fb['feedback_id']; ?>">
                    <p>
                        <strong><?php echo htmlspecialchars($fb['feedback_author']); ?>:</strong>
                        <span class="feedback-text"><?php echo nl2br(htmlspecialchars($fb['feedback_text'])); ?></span>
                    </p>
                    
                    <?php if ($fb['user_id'] == $loggedInUserId): ?>
                        <!-- Edit and delete options for user's own feedback -->
                        <button type="button" class="btn orange lighten-3 z-depth-0 edit-feedback-btn" data-feedback-id="<?php echo $fb['feedback_id']; ?>">Edit</button>
                        <form class="delete-feedback-form" action="delete_feedback.php" method="POST" style="display: inline;">
                            <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                            <input type="hidden" name="blog_id" value="<?php echo $fb['blog_id']; ?>">
                            <button type="submit" name="delete_feedback" class="btn red lighten-3 z-depth-0">Delete</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

</div>

<?php include('templates/footer.php'); ?>

<script>

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
            plugins: 'autolink lists link image charmap print preview hr anchor pagebreak',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            autosave_ask_before_unload: false,
            height: 300,
            content_css: [
                '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
                '//www.tiny.cloud/css/codepen.min.css'
            ]
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
        formData.append('update_feedback', '1'); // Add this line to indicate the update_feedback parameter
        formData.append('feedback_id', feedbackId);
        formData.append('edited_feedback_text', editedFeedbackText);

        fetch('update_feedback.php', {
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

</script>