<?php 
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	if (session_status() == PHP_SESSION_NONE) {
	    session_start();
	}
	require '../vendor/autoload.php';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_text']) && isset($_POST['blog_id'])) {
	    $feedback_text = $_POST['feedback_text'];
	    $blog_id = $_POST['blog_id'];
	    $user_id = $_SESSION['user_id'];

	    // Database connection
	    $mysqli = require __DIR__ . '/../config/db_connect.php';

	    // Insert the feedback into the database
	    $stmt = $mysqli->prepare("INSERT INTO feedback (blog_id, user_id, feedback_text) VALUES (?, ?, ?)");
	    $stmt->bind_param("iis", $blog_id, $user_id, $feedback_text);

	    if ($stmt->execute()) {
	        // Get the ID of the blog owner (receiver_id)
	        $blog_stmt = $mysqli->prepare("SELECT user_id FROM blogs WHERE id = ?");
	        $blog_stmt->bind_param("i", $blog_id);
	        $blog_stmt->execute();
	        $blog_stmt->bind_result($receiver_id);
	        $blog_stmt->fetch();
	        $blog_stmt->close();

	        // Fetch the feedback author's name
	        $feedbacker_stmt = $mysqli->prepare("SELECT name FROM user WHERE user_id = ?");
	        $feedbacker_stmt->bind_param("i", $user_id);
	        $feedbacker_stmt->execute();
	        $feedbacker_stmt->bind_result($feedbacker_name);
	        $feedbacker_stmt->fetch();
	        $feedbacker_stmt->close();

	        // Only insert the notification if the feedback author is not the blog owner
	        if ($user_id !== $receiver_id) {
	            // Insert the notification into the blog_feedback_notifications table
	            $notification_stmt = $mysqli->prepare("INSERT INTO blog_feedback_notifications (commenter_id, receiver_id, feedback_content, blog_id) VALUES (?, ?, ?, ?)");
	            $notification_stmt->bind_param("iisi", $user_id, $receiver_id, $feedback_text, $blog_id);

	            if (!$notification_stmt->execute()) {
	                echo "Error: " . $notification_stmt->error;
	            }
	            $notification_stmt->close();
	        }

	        // Redirect to avoid form re-submission
	        header("Location: " . $_SERVER['HTTP_REFERER']);
	        exit();
	    } else {
	        echo "Error: " . $stmt->error;
	    }

	    $stmt->close();
	} else {
	    // Redirect back if the request method is not POST or if required POST parameters are missing
	    header("Location: " . $_SERVER['HTTP_REFERER']);
	    exit();
	}
?>
