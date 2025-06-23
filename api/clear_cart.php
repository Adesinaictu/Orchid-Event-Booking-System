<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../includes/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Hardcoded user ID - replace with actual session/user management later
$user_id = 1;

try {
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $response['success'] = true;
    $response['message'] = 'Cart cleared successfully.';

} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Clear Cart Error: " . $e->getMessage()); // Log detailed error
}

echo json_encode($response);
?>