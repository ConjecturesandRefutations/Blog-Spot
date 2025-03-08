<?php
$mysqli = require __DIR__ . "/../config/db_connect.php";

// Check if user_id is provided in the GET request
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Use prepared statement to prevent SQL injection
    $stmt_user_details = $mysqli->prepare("SELECT name FROM user WHERE user_id = ?");
    $stmt_user_details->bind_param("i", $user_id);
    $stmt_user_details->execute();
    $result_user_details = $stmt_user_details->get_result();

    // Check if the user exists
    if ($result_user_details && $result_user_details->num_rows > 0) {
        $user_details = $result_user_details->fetch_assoc();

        // Return user details as JSON
        echo json_encode($user_details);
    } else {
        // Return an empty JSON object if user is not found
        echo json_encode([]);
    }

    $stmt_user_details->close();
}

// Close connection
mysqli_close($mysqli);
?>
