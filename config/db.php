<?php
// config/db.php

$servername = "flow-forge-mysql";
$username = "root";
$password = "flowforge_root_pass";
$dbname = "ecoquest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and die if unsuccessful
if ($conn->connect_error) {
    // We do not show the error in production, but for debugging we die() here
    die("Connection failed: " . $conn->connect_error);
    // If you remove the die(), $conn will be null, which is why the error handling works!
}

// Optional: Set character set
$conn->set_charset("utf8mb4");

// NOTE: If the connection is successful, the variable $conn is now available for use.
