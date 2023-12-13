<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Set the timestamp of the user's last logout in the session
$_SESSION['last_logout'] = time();

header("Location: ../authentication/login.php");
exit;
<<<<<<< HEAD
?>
=======
?>
>>>>>>> d96aab6dda356da9e27216d4541f603d4802e73f
