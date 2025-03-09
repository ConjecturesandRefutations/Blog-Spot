<?php
// conversation.php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: authentication/login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($otherUserId === 0) {
    header('Location: messages.php');
    exit();
}

// Fetch other user's details
$stmt = $conn->prepare('SELECT name FROM user WHERE user_id = ?');
$stmt->bind_param('i', $otherUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: messages.php');
    exit();
}

$otherUser = $result->fetch_assoc();

?>

<?php include('templates/header.php'); ?>
<a class="back-to-messages" href="messages.php"><i class="fas fa-arrow-left"></i> All Conversations</a>

<div class="body-wrapper">
    <div class="container">
        <h4 class="center conversation-title grey-text text-darken-2">Conversation with <a href="profile.php?id=<?php echo htmlspecialchars($otherUserId); ?>"><?php echo htmlspecialchars($otherUser['name']); ?></a></h4>
        <div id="messagesContainer" style="max-height: 500px; overflow-y: scroll;">
            <!-- Messages will be loaded here -->
        </div>
        <form id="messageForm">
            <div class="input-field">
                <textarea id="message_content" name="message_content" class="materialize-textarea"></textarea>
                <label for="message_content">Type your message...</label>
                <div id="errorMessage" style="color: red; display: none;"></div>
            </div>
            <button type="submit" class="btn blue z-depth-0">Send <i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<?php include('templates/footer.php'); ?>

<script>
    $(document).ready(function() {
        const otherUserId = <?php echo $otherUserId; ?>;
        const currentUserId = <?php echo $currentUserId; ?>;

        let isAtBottom = true; // Track if the user is at the bottom of the messages container

        // Fetch and display messages
        fetchMessages();

        // Fetch messages function
        function fetchMessages() {
            $.ajax({
                type: 'GET',
                url: 'utilities/messaging/fetch_conversation.php',
                data: { user_id: otherUserId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.messages.length === 0) {
                            $('#messagesContainer').html('<p>Write the first message!</p>');
                        } else {
                            displayMessages(response.messages);
                        }

                        if (isAtBottom) {
                            $('#messagesContainer').scrollTop($('#messagesContainer')[0].scrollHeight);
                        }

                        markMessagesAsRead(otherUserId);
                    } else {
                        $('#messagesContainer').html('<p>Write the first message!</p>');
                    }
                },
                error: function(error) {
                    console.error('Error fetching messages:', error);
                }
            });
        }

        function displayMessages(messages) {
        let html = '';
        messages.forEach(function(message) {
            let messageClass = message.sender_user_id == currentUserId ? 'sent-message' : 'received-message';
            html += `
            <div class="${messageClass}">
                <p>${message.message_content}</p>
                <span>${message.timestamp}</span>
            `;

            // Only show the delete button if the message was sent by the current user
            if (message.sender_user_id == currentUserId) {
                html += `
                    <p 
                        class="delete-message red-text" 
                        style="border: none; cursor: pointer;" 
                        data-message-id="${message.message_id}" 
                    >
                        Delete
                    </p>
                `;
            }

            html += `</div>`;
        });

        $('#messagesContainer').html(html);

        $('.delete-message').click(function() {
            const messageId = $(this).data('message-id');

            if (confirm('Are you sure you want to delete this message?')) {
                deleteMessage(messageId);
            }
        });
    }

    function deleteMessage(messageId) {
    // Find the message element based on the data-message-id attribute
    var messageElement = $('.sent-message').filter(function() {
        return $(this).find('.delete-message').data('message-id') === messageId;
    });

    console.log(messageElement); // Check if the message is correctly selected

    // Fade out the message element
    messageElement.fadeOut(300, function() {
        // After fade-out is complete, delete the message via AJAX
        $.ajax({
            type: 'POST',
            url: 'utilities/messaging/delete_message.php',
            data: { message_id: messageId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    fetchMessages();
                } else {
                    alert('Error deleting message: ' + response.message);
                    messageElement.fadeIn(300);
                }
            },
            error: function(error) {
                console.error('Error deleting message:', error);
                messageElement.fadeIn(300);
            }
        });
    });
}

        $('#message_content').on('input', function() {
            $('#errorMessage').hide();
        });

        $('#messageForm').submit(function (e) {
            e.preventDefault();

            let formData = $(this).serialize();
            formData += `&receiver_user_id=${otherUserId}`;

            $.ajax({
                type: 'POST',
                url: 'utilities/messaging/send_message.php',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        $('#message_content').val('');
                        fetchMessages();
                    } else {
                        $('#errorMessage').text(response.message).show();
                    }
                },
                error: function (error) {
                    console.error('Error sending message:', error);
                }
            });
        });

        setInterval(fetchMessages, 5000);

        function markMessagesAsRead(userId) {
            $.ajax({
                type: 'POST',
                url: 'utilities/messaging/mark_messages_read.php',
                data: { user_id: userId },
                success: function(response) {
                    console.log('Messages marked as read:', response);
                },
                error: function(error) {
                    console.error('Error marking messages as read:', error);
                }
            });
        }

        markMessagesAsRead(otherUserId);

        $('#messagesContainer').on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            const scrollHeight = $(this)[0].scrollHeight;
            const clientHeight = $(this).innerHeight();

            isAtBottom = scrollTop + clientHeight >= scrollHeight;
        });
    });
</script>

<style>
    .sent-message {
        background-color: #dcf8c6;
        padding: 10px;
        margin: 5px;
        border-radius: 5px;
        text-align: right;
    }

    .received-message {
        background-color: #f1f0f0;
        padding: 10px;
        margin: 5px;
        border-radius: 5px;
        text-align: left;
    }

    #messagesContainer {
        border: 1px solid #ccc;
        padding: 10px;
        max-height: 50vh !important;
    }

    .back-to-messages {
        cursor: pointer;
        font-size: 20px;
        position: absolute;
        top: 75px;
        left: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    .back-to-messages:hover{
    background: white
    }

    .conversation-title {
        margin-top: 50px;
    }

    .delete-message{
        cursor: pointer;
        max-width: 60px;
    }

    .message-action-buttons{
    display: flex;
    flex-direction: row;
}

</style>