<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For development, adjust for production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

session_start(); // Start session to store user login status

// Handle pre-flight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- CRITICAL FIX START ---
// 1. Include config.php FIRST to define DB constants (DB_HOST, DB_USER, etc.)
require_once __DIR__ . '/../config.php';

// 2. Then, include db_connect.php, which can now use those defined constants
require_once __DIR__ . '/../includes/db_connect.php';
// --- CRITICAL FIX END ---


// Function to send JSON response
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    $request_data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Invalid JSON input.');
    }

    $email = $request_data['email'] ?? '';
    $password = $request_data['password'] ?? ''; // This is the plain text password from the form

    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Email and password are required.');
    }

    try {
        // SQL query now correctly uses 'user_id' and 'password_hash'
        $stmt = $conn->prepare("SELECT user_id, email, password_hash FROM users WHERE email = ?");

        if ($stmt === false) {
            error_log("Login mysqli prepare failed: (" . $conn->errno . ") " . $conn->error);
            throw new Exception("Database error: Could not prepare statement for user lookup.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Check if user exists AND if the provided password matches the hashed password from the database
        // Use $user['password_hash'] as this is the column name in your database
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];

            sendJsonResponse(true, 'Login successful!', ['user_id' => $user['user_id'], 'user_email' => $user['email']]);
        } else {
            sendJsonResponse(false, 'Invalid email or password.');
        }

    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        sendJsonResponse(false, 'An unexpected error occurred during login. Please try again later.');
    } finally {
        // Optional: $conn->close();
    }

} else {
    sendJsonResponse(false, 'Invalid request method. Only POST is allowed.');
}
?>