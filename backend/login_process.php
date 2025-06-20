<?php
// backend/login.php

ini_set('display_errors', 1); // Keep for development, but remove in production
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Allow OPTIONS for preflight requests
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Handle preflight requests
}

session_start(); // Start the session *after* headers

// Include database connection
require_once 'db_connect.php'; // Correct path assumed

$response = ["success" => false, "message" => "An unknown error occurred."]; // Default message

// Get the POST data sent from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON data received: ' . json_last_error_msg();
    echo json_encode($response);
    exit();
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Server-side validation
if (empty($email) || empty($password)) {
    $response["message"] = "Email and password are required.";
    echo json_encode($response);
    exit();
}

try {
    $pdo = db_connect(); // Get PDO connection from db_connect.php

    // Use PDO prepared statements
    $stmt = $pdo->prepare("SELECT user_id, username, full_name, email, password_hash FROM users WHERE email = ?");

    // Execute with parameters
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

    if ($user) {
        // Verify the password against the password_hash from the database
        if (password_verify($password, $user['password_hash'])) {
            $response["success"] = true;
            $response["message"] = "Login successful!";

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username']; // Use 'username' for consistency
            $_SESSION['full_name'] = $user['full_name']; // If you have a full_name column
            $_SESSION['email'] = $user['email']; // Store email in session too
            $_SESSION['logged_in'] = true;

            // Log successful login (optional, for debugging/auditing)
            error_log("User logged in: " . $user['email'] . " (ID: " . $user['user_id'] . ")");

        } else {
            $response["message"] = "Invalid email or password."; // Keep generic for security
        }
    } else {
        $response["message"] = "Invalid email or password."; // Keep generic for security
    }

} catch (Exception $e) { // Catch general exceptions thrown by db_connect or PDO
    $response["message"] = "Server error during login: " . $e->getMessage();
    error_log("Login error: " . $e->getMessage());
}

// Always echo the JSON response and exit
echo json_encode($response);
exit();

 ?> 