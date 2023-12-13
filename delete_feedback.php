<?php
include('config/db_connect.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['delete_feedback'])) {
    $feedback_id = mysqli_real_escape_string($conn, $_POST['feedback_id']);
    $blog_id = mysqli_real_escape_string($conn, $_POST['blog_id']);

    // Perform the deletion
    $deleteFeedbackSql = "DELETE FROM feedback WHERE feedback_id = $feedback_id";

    if (mysqli_query($conn, $deleteFeedbackSql)) {
        // Success
        header("Location: view.php?id=$blog_id");
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn);
    }
}

mysqli_close($conn);
<<<<<<< HEAD
?>
=======
?>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
