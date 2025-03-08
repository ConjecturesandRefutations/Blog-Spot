<?php

//connect to database
$conn = mysqli_connect('localhost', 'alfie', 'Ic7xjc]hu8A!Xz[h', 'blog_spot');

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error");
}

return $conn;
?>