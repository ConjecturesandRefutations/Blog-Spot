<?php

//connect to database
$conn = mysqli_connect('localhost', 'alfie', 'test1234', 'blog spot');

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error");
}

return $conn;
?>