<?php

$mysqli = require __DIR__ . "/database.php";

$sql = sprintf("SELECT * FROM user
                WHERE name = '%s'",
                $mysqli->real_escape_string($_GET["name"]));

$result = $mysqli->query($sql);

$is_available  = $result->num_rows === 0;

header("Content-Type: application/json");

echo json_encode(["available" => $is_available]);

?>
