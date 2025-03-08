<?php
$mysqli = require __DIR__ . "/../config/db_connect.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Assuming $user_id is already defined in your code
$user_id = $_POST['user_id']; // Make sure to pass the user_id via AJAX

// Check if a profile image was uploaded
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    try {
        // Use a database transaction for consistency
        $mysqli->begin_transaction();

        $uploadDir = __DIR__ . '/../uploads/';
        $uploadFile = $uploadDir . basename($_FILES['profile_image']['name']);

        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {

            // Update the database with the relative image path
            $relativeImagePath = 'uploads/' . basename($_FILES['profile_image']['name']);
            $updateImageQuery = "UPDATE user SET profile_image = ? WHERE user_id = ?";
            $stmt_update_image = $mysqli->prepare($updateImageQuery);
            $stmt_update_image->bind_param("si", $relativeImagePath, $user_id);
            
            if ($stmt_update_image->execute()) {
                // Update the session variable with the new relative image path
                $_SESSION['profile_image'][$user_id] = $relativeImagePath;

                // Commit the transaction
                $mysqli->commit();

                // Fetch the updated user data
                $stmt_profile_user = $mysqli->prepare("SELECT user.user_id, user.name, user.profile_image
                                                      FROM user
                                                      WHERE user.user_id = ?");
                $stmt_profile_user->bind_param("i", $user_id);
                $stmt_profile_user->execute();
                $result_profile_user = $stmt_profile_user->get_result();

                // Check if the user exists
                if ($result_profile_user && $result_profile_user->num_rows > 0) {
                    $profileUser = $result_profile_user->fetch_assoc();
                }

                $stmt_profile_user->close();

                // Send a success response
                echo json_encode(['status' => 'success', 'message' => 'Profile image uploaded successfully', 'profile_image' => $relativeImagePath]);
            } else {
                // Rollback the transaction in case of a database error
                $mysqli->rollback();

                // Send an error response
                echo json_encode(['status' => 'error', 'message' => 'Failed to update profile image in the database']);
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
mysqli_close($mysqli);
?>
