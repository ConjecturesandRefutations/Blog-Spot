<?php
include('config/db_connect.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['update_feedback'])) {
    $feedback_id = mysqli_real_escape_string($conn, $_POST['feedback_id']);
    $edited_feedback_text = mysqli_real_escape_string($conn, $_POST['edited_feedback_text']);

    // Perform the update
    $updateFeedbackSql = "UPDATE feedback SET feedback_text = '$edited_feedback_text' WHERE feedback_id = $feedback_id";

    if (mysqli_query($conn, $updateFeedbackSql)) {
        // Success
        echo 'Feedback updated successfully';
    } else {
        // Failure
        echo 'query error: ' . mysqli_error($conn) . ' - Query: ' . $updateFeedbackSql;
    }
} else {
    // No data received
    echo 'No data received for updating feedback';
}

mysqli_close($conn);
?>
