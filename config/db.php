<?php
// config/db.php
// This file establishes a connection to the MySQL database using MySQLi (Object Oriented style).

// --- 1. Database Configuration Constants ---
// CRITICAL: Change these values to match your actual database settings.
define('DB_SERVER', 'localhost'); // Host name (e.g., 'localhost')
define('DB_USERNAME', 'root');    // Your MySQL username
define('DB_PASSWORD', '');        // Your MySQL password (often empty for local WAMP/XAMPP)
define('DB_NAME', 'ecoquest');    // The database name you created

// Initialize connection variable to null
$conn = null;

// --- 2. Establish Connection ---
// Attempt to create connection object
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection status
if ($conn->connect_error) {
    // If connection fails, set $conn to null and stop execution.
    // This prevents later scripts from trying to call methods on a non-existent object.
    $conn = null; 
    
    // Critical failure message for development
    die("Aiyo, database connection failed liao: " . $conn->connect_error);
}

// Set character set to UTF-8 for better international character support
$conn->set_charset("utf8mb4");

// Now the variable $conn holds the active MySQLi connection object for use in other scripts.
?>
