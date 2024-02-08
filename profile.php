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
                                        SUM(CASE WHEN blogs.is_draft = 0 THEN LENGTH(blogs.content) - LENGTH(REPLACE(blogs.content, ' ', '')) + 1 ELSE 0 END) as totalWords,
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
        $stmt_blogs = $mysqli->prepare("SELECT blogs.title, blogs.date, blogs.last_updated, blogs.content, blogs.id, blogs.topic, user.user_id, user.name as author_name
        FROM blogs
        INNER JOIN user ON blogs.user_id = user.user_id
        WHERE blogs.user_id = ? AND (blogs.title LIKE ? OR blogs.topic LIKE ?) AND blogs.is_draft = 0
        ORDER BY COALESCE(blogs.last_updated, blogs.date) DESC, blogs.id DESC");
        $likeParam = "%$searchTerm%";
        $stmt_blogs->bind_param("iss", $user_id, $likeParam, $likeParam);
        $stmt_blogs->execute();
        $result_blogs = $stmt_blogs->get_result();

        // Fetch the resulting rows as an array
        $blogs = $result_blogs->fetch_all(MYSQLI_ASSOC);

        // Recalculate the total word count for all the user's blogs combined
        $totalWords = 0;
        foreach ($blogs as $blog) {
            // Calculate the word count for each blog content
            $wordCount = calculateWordCount($blog['content']);
            // Add the word count of the current blog to the total words
            $totalWords += $wordCount;
        }


        // Get the number of blogs
        $numBlogs = count($blogs);

        // Free result from memory
        $stmt_blogs->close();

    } else {
        header("Location: authentication/login.php");
        exit();
    }

    function calculateWordCount($content) {
        // Remove HTML tags from the content
        $contentWithoutTags = strip_tags($content);
    
        // Count words by counting spaces
        $wordCount = str_word_count($contentWithoutTags);
    
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
            <p class="center grey-text text-darken-2">Favourite Topic: <?php echo htmlspecialchars($profileUser['favoriteTopic']); ?></p>

            <?php
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profileUser['user_id']) {
        // Display the message button or form
        
    ?>
        <button class="secondary z-depth-0 button hov white-text" style="border:none;" onclick="openMessageModal()">Send Message <i class="fas fa-paper-plane"></i></button>

        <!-- Message Modal -->
        <div id="messageModal" class="modal">
            <div class="modal-content">
                <h4>Compose Message</h4>
                <form action="utilities/send_message.php" method="POST" id="messageForm">
                    <input type="hidden" name="recipient_user_id" value="<?php echo $profileUser['user_id']; ?>">
                    <div class="input-field">
                        <textarea id="message_content" name="message_content" class="materialize-textarea"></textarea>
                        <label for="message_content">Message</label>
                    </div>
                    <button type="submit" class="btn blue z-depth-0">Send <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat red hov white-text" onclick="closeModalAndRefresh('messageModal')">Close</a>
            </div>
        </div>

    <?php
    }
    ?>
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUser['user_id']) : ?>
        <button class="secondary z-depth-0 hov white-text" style="border:none;" onclick="seeMessagesModal()" id="seeMessagesButton">See Messages<i class="fas fa-envelope"></i></button>

        <!-- See Messages Modal -->
    <div id="seeMessagesModal" class="modal">
    <div class="modal-footer">
            <a href="#!" class="modal-close red btn-flat white-text" onclick="closeModalAndRefresh('seeMessagesModal')">Close</a>
        </div>
        <div class="modal-content">
            <h4>Messages</h4>
            <hr>
            <div id="messagesContainer">
                <!-- Messages will be displayed here -->
            </div>
        </div>

    </div>

    <?php endif; ?>
        </div>

        <div class="image">
        <img id="profileImagePreview" src="<?php echo (!empty($profileUser['profile_image'])) ? $profileUser['profile_image'] : 'images/defaultProfile.jpg'; ?>" alt="Profile Image" class="responsive-img circle" style="width: 150px; height: 150px;">

        <!-- Form for profile image upload -->
        <form class="" action="<?php echo "profile.php" . (isset($profileUser['user_id']) ? "?id={$profileUser['user_id']}" : ''); ?>" method="POST" enctype="multipart/form-data" id="profileImageForm">
            <!-- Add input field for profile image upload -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUser['user_id']) : ?>
                <div class="file-field input-field">
                    <div class="change-image">
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
    <div class="col s12 l6 offset-l3">
        <form action="<?php echo "profile.php" . (isset($profileUser['user_id']) ? "?id={$profileUser['user_id']}" : ''); ?>" method="GET" id="searchForm">
            <div class="input-field col s12">
                <i class="material-icons prefix">search</i>
                <input class="white" type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>" />
                <label for="search" class="profile-placeholder">

                    <?php
                    if (isset($loggedInUserId) && isset($profileUser['user_id']) && $profileUser['user_id'] == $loggedInUserId) {
                        echo "Search Your Blogs by Title or Topic";
                    } else {
                        echo "Search " . htmlspecialchars($profileUser['name']) . "'s Blogs by Title or Topic";
                    }
                    ?>
                </label>
            </div>
            <input type="hidden" name="id" value="<?php echo isset($profileUser['user_id']) ? $profileUser['user_id'] : ''; ?>">
        </form>
    </div>
</div>

    <div class="container center" id="blogList">
        <div class="row">
        <h5 class='center grey-text'>                     <?php
                    if (isset($loggedInUserId) && isset($profileUser['user_id']) && $profileUser['user_id'] == $loggedInUserId) {
                        echo "Your Blogs";
                    } else {
                        echo "" . htmlspecialchars($profileUser['name']) . "'s Blogs";
                    }
                    ?></h5>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUser['user_id']) : ?>
            <a href="drafts.php?id=<?php echo $loggedInUserId; ?>" class="left underline">See Drafts</a>
            <?php endif; ?>
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

    <?php
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        // Check if the logged-in user ID matches the profile user ID
        if ($_SESSION['user_id'] == $profileUser['user_id']) {
            // Display the delete account button
    ?>
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <form action="utilities/delete_account.php" method="POST" id="deleteAccountForm">
                        <input type="hidden" name="user_id" value="<?php echo $profileUser['user_id']; ?>">
                        <button type="submit" class="red z-depth-0 white-text" style="border:none;">Delete My Account</button>
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
            let formData = $('#searchForm').serialize();

            // Make an AJAX request to update the blog list
            $.ajax({
                type: 'GET',
                url: 'utilities/fetch_user_blogs.php', // Update the URL to the new file
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
        let formData = new FormData($('#profileImageForm')[0]);
        formData.append('user_id', <?php echo $profileUser['user_id']; ?>);

        $.ajax({
            type: 'POST',
            url: 'utilities/upload_profile_image.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Parse the JSON response
                let responseData = JSON.parse(response);

                // Handle the response
                console.log(responseData);

                if (responseData.status === 'success') {
                    // Update the profile image on the page
                    $('#profileImagePreview').attr('src', responseData.profile_image);

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

    $(document).ready(function(){
            $('.modal').modal();
        });

        

    //For toggling the message button
    function openMessageModal() {
                $('#messageModal').modal('open');
            }

            // For handling the message form submission
            $(document).ready(function () {
        // For handling the message form submission
$(document).ready(function () {
    $('#messageForm').submit(function (e) {
        e.preventDefault();

        // Get the form data
        let formData = $(this).serialize();

        // Make an AJAX request to handle the message submission
        $.ajax({
            type: 'POST',
            url: 'utilities/send_message.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Handle the success response
                console.log(response.status);

                if (response.status === 'success') {
                    // Display a success message in the modal
                    $('#messageModal .modal-content').html('<p class="green-text">Message sent successfully!</p>');
                } else {
                    // Display the specific error message returned by the server
                    $('#messageModal .modal-content').html('<p class="red-text strong">' + response.message + '</p>');
                }
            },
            error: function (error) {
                // Handle the error
                console.error(error);
            }
        });
    });
});

    });

    function updateMessagesModal(messages) {
    let messagesHtml = '';

    if (messages.length === 0) {
        // Display "No Messages" if there are no messages
        messagesHtml += '<p>No Messages</p>';
    } else {
        // Loop through messages and create HTML
        for (let i = 0; i < messages.length; i++) {
            let message = messages[i];
            let senderUserId = message.sender_user_id;
            let messageContent = message.message_content;
            let timestamp = message.timestamp;
            let messageID = message.message_id

            // Format the timestamp (you might need to adjust this based on your timestamp format)
            let formattedTimestamp = new Date(timestamp).toLocaleString();

            // Placeholder for the sender's name
            messagesHtml += '<div class="message">';
            messagesHtml += '<p>From: <strong><span class="sender-name" data-userid="' + senderUserId + '">User ID ' + senderUserId + '</span></strong></p>';
            messagesHtml += '<p>' + messageContent + '</p>';
            messagesHtml += '<p>' + formattedTimestamp + '</p>';
            messagesHtml += '<button class="red delete-message white-text left" style="border: none" data-message-id="' + messageID + '">Delete</button>'
            messagesHtml += '<button class="blue reply-message white-text" style="margin-right: 50px; border:none" data-message-id="' + messageID + '">Reply</button>'
            messagesHtml += '<div id="messagesContainer"><!-- Messages will be displayed here --> </div>'
            messagesHtml += '<hr>'
            messagesHtml += '</div>';
        }
    }

    // Update the modal content with the fetched messages
    $('#messagesContainer').html(messagesHtml);

    // Fetch user details for each sender
    $('.sender-name').each(function () {
        let userId = $(this).data('userid');
        getUserDetails(userId, $(this));
    });
}


    // Function to fetch user details based on user_id and update the sender name
    function getUserDetails(userId, element) {
        // Make an AJAX request to fetch user details
        $.ajax({
            type: 'GET',
            url: 'utilities/fetch_user_details.php', // Adjust the URL to your PHP script
            data: { user_id: userId },
            dataType: 'json',
            success: function (response) {
                // Callback to handle the user details response
                if (response && response.name) {
                    // Update the sender name in the messages modal
                    element.text(response.name);
                }
            },
            error: function (error) {
                console.error(error);
            }
        });
    }

    // Function to update messages modal with sender's name
    function updateMessagesModalWithSenderName(html, userId, senderName) {
        // Find the placeholder and replace it with the sender's name
        let updatedHtml = html.replace(new RegExp('UserIdPlaceholder' + userId, 'g'), senderName);
        $('#messagesContainer').html(updatedHtml);
    }


    function seeMessagesModal() {
    // Get the user_id of the profile user
    let profileUserId = <?php echo $profileUser['user_id']; ?>;

    // Make an AJAX request to fetch messages
    $.ajax({
        type: 'GET',
        url: 'utilities/fetch_messages.php',
        data: { user_id: profileUserId },
        dataType: 'json',
        success: function (response) {
            // Update the modal content with the fetched messages
            updateMessagesModal(response.messages);

        },
        error: function (error) {
            console.error(error);
        }
    });

    // Open the modal
    $('#seeMessagesModal').modal('open');
}

    // Function to handle modal close and refresh the page
    function closeModalAndRefresh(modalType) {
    // Close the specified modal
    $('#' + modalType).modal('close');

    // Check if the modal being closed is the 'messageModal' or 'seeMessagesModal'
    if (modalType === 'messageModal' || modalType === 'seeMessagesModal') {
        // Reload the page
        location.reload();
    }
}

 $(document).ready(function () {
    $('.modal').modal({
        closeMethods: ['button', 'overlay'],
        dismissible: false,
    });
}); 

function updateUnreadMessagesCount() {
    // Make an AJAX request to fetch the unread messages count
    $.ajax({
        type: 'GET',
        url: 'utilities/fetch_unread_count.php',
        data: { user_id: <?php echo $profileUser['user_id']; ?> }, // Pass the user_id
        dataType: 'json',
        success: function(response) {
            // Get the button element
            var $seeMessagesButton = $('#seeMessagesButton');

            // Check if there are unread messages
            if (response.unreadCount > 0) {
                // Update the button text to include the unread count
                $seeMessagesButton.html('See Messages <span class="red-text text-darken-3">(' + response.unreadCount + ' Unread)</span> <i class="fas fa-envelope"></i>');
            } else {
                // No unread messages, set the default text
                $seeMessagesButton.html('See Messages <i class="fas fa-envelope-open"></i>');
            }
        },
        error: function(error) {
            console.error(error);
        }
    });
}


$(document).ready(function () {
    // Call the function to update the unread messages count when the page loads
    updateUnreadMessagesCount();

    // Set up an interval to regularly update the unread messages count
    setInterval(updateUnreadMessagesCount, 30000); // Update every 30 seconds (adjust as needed)
});

$(document).ready(function() {
    // Add an event listener for the delete buttons
    $(document).on('click', '.delete-message', function() {
        // Get the message ID from the data attribute
        let messageId = $(this).data('message-id');

        console.log('Clicked Delete for Message ID:', messageId);


        // Make an AJAX request to delete the message
        $.ajax({
            type: 'POST',
            url: 'utilities/delete_message.php',
            data: { message_id: messageId },
            dataType: 'json',
            success: function(response) {
                // Handle the success response
                console.log(response);

                // Reload the messages modal after deletion
                seeMessagesModal();
            },
            error: function(error) {
                // Handle the error
                console.error(error);
            }
        });
    });
});

$(document).ready(function() {
        // Add an event listener for the reply buttons
        $(document).on('click', '.reply-message', function() {
            // Get the message ID from the data attribute
            let messageId = $(this).data('message-id');

            // Create a textarea element for the reply
            let replyTextarea = '<textarea class="materialize-textarea" id="replyTextarea_' + messageId + '" placeholder="Type your reply here"></textarea>';

            // Create a 'Send' button
            let sendButton = '<button class="blue send-reply white-text" style="border: none" data-message-id="' + messageId + '">Send <i class="fas fa-paper-plane"></button>';

            // Append the textarea and 'Send' button after the clicked 'reply' button
            $(this).after(replyTextarea + sendButton);

            // Update the 'reply' button to 'Cancel' for user feedback
            $(this).text('Cancel');

            // Remove the 'reply' class and add 'cancel-reply' for further handling
            $(this).removeClass('reply-message').addClass('cancel-reply');

        });

        // Add an event listener for the cancel-reply buttons
        $(document).on('click', '.cancel-reply', function() {
            // Get the message ID from the data attribute
            let messageId = $(this).data('message-id');

            // Remove the appended textarea and 'Send' button
            $('#replyTextarea_' + messageId).remove();
            $('.send-reply').remove();

            // Update the button back to 'reply'
            $(this).text('Reply');

            // Remove the 'cancel-reply' class and add 'reply-message'
            $(this).removeClass('cancel-reply').addClass('reply-message');
        });
    });

    $(document).on('click', '.send-reply', function() {
    let messageId = $(this).data('message-id');
    let replyContent = $('#replyTextarea_' + messageId).val();

    // Make an AJAX request to send the reply
    $.ajax({
        type: 'POST',
        url: 'utilities/send_message.php',
        data: {
            recipient_user_id: <?php echo $profileUser['user_id']; ?>,
            message_content: replyContent,
            parent_message_id: messageId
        },
        dataType: 'json',

                   // Inside the success callback for sending a reply
success: function (response) {
    // Handle the success response
    console.log(response.status);

    if (response.status === 'success') {
        // Display a success message in the modal
        $('#seeMessagesModal .modal-content').html('<p class="green-text">' + response.message + '</p>');
    } else {
        // Display the specific error message returned by the server
        $('#seeMessagesModal .modal-content').html('<p class="red-text strong">' + response.message + '</p>');
    }
},

        error: function(error) {
            // Handle the error
            console.error(error);
        }
    });
});

</script>