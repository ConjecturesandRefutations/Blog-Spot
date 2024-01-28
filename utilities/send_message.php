<?php
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize the input data
    $recipient_user_id = $_POST["recipient_user_id"];
    $message_content = trim($_POST["message_content"]); // Trim to remove leading and trailing spaces

    // Ensure that the user is logged in
    if (isset($_SESSION['user_id'])) {
        $sender_user_id = $_SESSION['user_id'];

        // Check if the message content is empty or contains only spaces
        if (empty($message_content) || ctype_space($message_content)) {
            echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty or contain only spaces.']);
            exit();
        }

        // Use prepared statement to prevent SQL injection
        $stmt_insert_message = $mysqli->prepare("INSERT INTO messages (sender_user_id, receiver_user_id, message_content) VALUES (?, ?, ?)");
        $stmt_insert_message->bind_param("iis", $sender_user_id, $recipient_user_id, $message_content);

        // Execute the prepared statement
        if ($stmt_insert_message->execute()) {
            // Message inserted successfully
            $stmt_insert_message->close();
            mysqli_close($mysqli);
            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
            exit();
        } else {
            // Error inserting message
            $stmt_insert_message->close();
            mysqli_close($mysqli);
            echo json_encode(['status' => 'error', 'message' => 'Error sending message.', 'mysqli_error' => mysqli_error($mysqli)]);
            exit();
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
