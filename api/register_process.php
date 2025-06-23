<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- CRITICAL FIX START ---
// 1. Include config.php FIRST to define DB constants (DB_HOST, DB_USER, etc.)
require_once __DIR__ . '/../config.php';

// 2. Then, include db_connect.php, which can now use those defined constants
require_once __DIR__ . '/../includes/db_connect.php';
// --- CRITICAL FIX END ---

$response = ["success" => false, "message" => ""];

// Get the POST data sent from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

$full_name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$confirm_password = $data['confirmPassword'] ?? '';

// --- Handle 'username' for the database ---
// It's good practice to ensure username is unique, often derived from email or a separate field.
$username = $email; // Using email as username for now

if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
    $response["message"] = "All fields are required.";
    echo json_encode($response);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response["message"] = "Invalid email format.";
    echo json_encode($response);
    exit();
}

if ($password !== $confirm_password) {
    $response["message"] = "Passwords do not match.";
    echo json_encode($response);
    exit();
}

// Check if email already exists
$sql_check_email = "SELECT user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql_check_email);

if ($stmt === false) {
    $response["message"] = "Prepare failed for email check: " . $conn->error;
    echo json_encode($response);
    $conn->close();
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $response["message"] = "Email already registered.";
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$sql_insert_user = "INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert_user);

if ($stmt === false) {
    // This check is CRUCIAL for debugging prepare() failures
    $response["message"] = "Prepare failed for user insert: " . $conn->error;
    echo json_encode($response);
    $conn->close();
    exit();
}

$stmt->bind_param("ssss", $full_name, $username, $email, $hashed_password);

if ($stmt->execute()) {
    $response["success"] = true;
    $response["message"] = "Registration successful! You can now log in.";
} else {
    $response["message"] = "Registration failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>