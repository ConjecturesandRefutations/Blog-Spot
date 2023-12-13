<?php
if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

$mysqli = require __DIR__ . "/config/db_connect.php";

?>

<?php include('templates/header.php'); ?>

<h3 class="center red-text">The Requested User or Blog is Not Found</h3>
<h5 class="center red-text">This is Normally Because they have been Deleted</h5>

<?php include('templates/footer.php'); ?>
