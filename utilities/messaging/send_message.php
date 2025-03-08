<?php
$mysqli = require __DIR__ . "/../../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize the input data
    $receiver_user_id = $_POST["receiver_user_id"];
    $message_content = trim($_POST["message_content"]); // Trim to remove leading and trailing spaces

    // Ensure that the user is logged in
    if (isset($_SESSION['user_id'])) {
        $sender_user_id = $_SESSION['user_id'];

        // Check if the message content is empty or contains only spaces
        if (empty($message_content) || ctype_space($message_content)) {
            echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty or contain only spaces.']);
            exit();
        }

      // Check if it's a reply to an existing message
if (isset($_POST['parent_message_id'])) {
    $parent_message_id = $_POST['parent_message_id'];

    // Retrieve the first four words of the parent message
    $stmt_select_parent_message = $mysqli->prepare("SELECT SUBSTRING_INDEX(message_content, ' ', 4) AS first_four_words FROM messages WHERE message_id = ?");
    $stmt_select_parent_message->bind_param("i", $parent_message_id);
    $stmt_select_parent_message->execute();
    $stmt_select_parent_message->bind_result($first_four_words);
    $stmt_select_parent_message->fetch();
    $stmt_select_parent_message->close();

    // Check if the message content already contains "Replying to"
    $replying_to_added = false;
    if (strpos($message_content, 'Replying to') === false) {
        // Append the new "Replying to" prefix
        $message_content = 'Replying to "' . $first_four_words . '...' . '"<br>' . $message_content;
        $replying_to_added = true;
    }

    // Use a single query to insert both the original message and the reply
    $stmt_insert_reply = $mysqli->prepare("INSERT INTO messages (sender_user_id, receiver_user_id, message_content, parent_message_id) 
                                        SELECT ?, sender_user_id, ?, ? FROM messages WHERE message_id = ? AND sender_user_id <> ?");
    $stmt_insert_reply->bind_param("isisi", $sender_user_id, $message_content, $parent_message_id, $parent_message_id, $sender_user_id);

    // Execute the prepared statement for a reply
    if ($stmt_insert_reply->execute()) {
        // Reply inserted successfully
        $stmt_insert_reply->close();

        mysqli_close($mysqli);
        echo json_encode(['status' => 'success', 'message' => 'Reply sent successfully.']);
        exit();
    } else {
        // Error inserting reply
        $stmt_insert_reply->close();
        mysqli_close($mysqli);
        echo json_encode(['status' => 'error', 'message' => 'Error sending reply.', 'mysqli_error' => mysqli_error($mysqli)]);
        exit();
    }
}

 else {
            // It's a new message
            $stmt_insert_message = $mysqli->prepare("INSERT INTO messages (sender_user_id, receiver_user_id, message_content) VALUES (?, ?, ?)");
            $stmt_insert_message->bind_param("iis", $sender_user_id, $receiver_user_id, $message_content);

            // Execute the prepared statement for a new message
            if ($stmt_insert_message->execute()) {
                // Message inserted successfully
                $inserted_message_id = $stmt_insert_message->insert_id;
                $stmt_insert_message->close();

            // Add internal notification for the new message
            $query_notification = "INSERT INTO message_notifications (receiver_id, sender_id, message_content, message_id) VALUES (?, ?, ?, ?)";
            $stmt_notification = $mysqli->prepare($query_notification);
            $stmt_notification->bind_param("iisi", $receiver_user_id, $sender_user_id, $message_content, $inserted_message_id);

            if ($stmt_notification->execute()) {
                $stmt_notification->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error inserting notification.', 'mysqli_error' => mysqli_error($mysqli)]);
                mysqli_close($mysqli);
                exit();
            }

            mysqli_close($mysqli);
            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
            exit();

            }
        }
    } else {
        // User not logged in
        echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
        mysqli_close($mysqli);
        exit();
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}
?>