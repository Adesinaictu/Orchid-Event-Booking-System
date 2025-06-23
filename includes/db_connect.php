<?php
// C:\xampp\htdocs\orchid\includes\db_connect.php

// These constants (DB_HOST, DB_USER, DB_PASS, DB_NAME)
// are assumed to be defined by config.php, which is included
// before this file in scripts like download_ticket.php.
// DO NOT DEFINE THEM HERE.

// Establish a database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // For development, display error; in production, log it and show a generic message
    error_log("Database connection failed: " . $conn->connect_error);
    die("Error connecting to the database. Please try again later.");
}

// Set charset to utf8mb4 for proper emoji and special character support
$conn->set_charset("utf8mb4");

// The global $conn variable is now available after including this file.
?>