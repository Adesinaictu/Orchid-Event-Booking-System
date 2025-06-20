<?php
// Database credentials
define('DB_HOST', 'localhost'); // Your database host (often 'localhost' or '127.0.0.1')
define('DB_USER', 'root');     // Your database username (e.g., 'root' for XAMPP/WAMP)
define('DB_PASS', '');         // Your database password (empty for XAMPP/WAMP root by default)
define('DB_NAME', 'orchidfy_db'); // The name of your database

// Establish a database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // For development, display error
    die("Connection failed: " . $conn->connect_error);
    // For production, log error and show a generic message
    // error_log("Database connection failed: " . $conn->connect_error);
    // die("An error occurred. Please try again later.");
}

// Set charset to utf8mb4 for proper emoji and special character support
$conn->set_charset("utf8mb4");

// You can optionally create a function or just leave the connection object global
// function getDbConnection() {
//     global $conn; // Access the global $conn variable
//     return $conn;
// }
?>