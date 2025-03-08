<?php
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Send an error response if the user is not logged in
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if a featured image was uploaded
if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
    try {
        // Use a database transaction for consistency
        $mysqli->begin_transaction();

        // Define the filename for the image
        $imageFilename = basename($_FILES['featured_image']['name']);

        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], __DIR__ . '/../uploads/' . $imageFilename)) {
            // Update the database with the image filename
            $updateImageQuery = "UPDATE blogs SET featured_image = ? WHERE id = ?";
            $stmt_update_image = $mysqli->prepare($updateImageQuery);
            $stmt_update_image->bind_param("si", $imageFilename, $_POST['blog_id']);

            if ($stmt_update_image->execute()) {
                // Commit the transaction
                $mysqli->commit();

                // Send a success response
                echo json_encode(['status' => 'success', 'message' => 'Featured image uploaded successfully', 'featured_image' => 'uploads/' . $imageFilename]);
            } else {
                // Rollback the transaction in case of a database error
                $mysqli->rollback();

                // Send an error response
                echo json_encode(['status' => 'error', 'message' => 'Failed to update featured image in the database']);
            }
        } else {
            // Send an error response for file move failure
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
        }
    } catch (Exception $e) {
        // Handle any exceptions
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
    }
} else {
    // Send an error response if no file was uploaded
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
}

// Close the database connection
$mysqli->close();
?>
