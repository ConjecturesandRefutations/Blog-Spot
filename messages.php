<?php
// messages.php
session_start();
$mysqli = require __DIR__ . "/config/db_connect.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: authentication/login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];

?>
<?php include('templates/header.php'); ?>

<div class="body-wrapper">
    <div class="container">
        <h4 class="center grey-text text-darken-2">Your Conversations</h4>
        <div id="conversationsContainer" class="masonry-grid">
            <!-- Conversations will be loaded here -->
        </div>
    </div>
</div>

<?php include('templates/footer.php'); ?>

<!-- Inline script for this page -->
<script>
    $(document).ready(function() {
        // Fetch and display conversations when the page loads
        fetchConversations();

        // Function to fetch conversations
        function fetchConversations() {
            $.ajax({
                type: 'GET',
                url: 'utilities/messaging/fetch_conversations.php',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.conversations.length > 0) {
                        displayConversations(response.conversations);
                    } else if (response.status === 'no_conversations') {
                        $('#conversationsContainer').html('<p>You haven\'t sent or received any messages.</p>');
                    } else {
                        $('#conversationsContainer').html('<p>No conversations found.</p>');
                    }
                },
                error: function(error) {
                    console.error('Error fetching conversations:', error);
                    $('#conversationsContainer').html('<p>Error fetching conversations. Please try again later.</p>');
                }
            });
        }

        // Function to display conversations
        function displayConversations(conversations) {
            let html = '';
            conversations.forEach(function(conversation) {
                html += `
                    <div class="col">
                        <div class="card hoverable">
                            <div class="card-content">
                                <span class="card-title">${conversation.name}</span>
                                <p><strong>Last message:</strong> ${conversation.last_message}</p>
                                <p><small>${conversation.last_message_time}</small></p>
                                ${conversation.unread_count > 0 ? `<p class="red-text"><strong>${conversation.unread_count} unread message(s)</strong></p>` : ''}
                            </div>
                            <div class="card-action">
                                <a href="conversation.php?user_id=${conversation.user_id}" class="btn">Open Conversation</a>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#conversationsContainer').html(html);
        }
    });
</script>