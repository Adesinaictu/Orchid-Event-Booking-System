<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../includes/db_connect.php';
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON data: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

$eventId = $data['eventId'] ?? null;

if (!is_numeric($eventId)) {
    $response['message'] = 'Invalid eventId.';
    echo json_encode($response);
    exit;
}

$eventId = (int)$eventId;
// Hardcoded user ID - replace with actual session/user management later
$user_id = 1;

try {
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id = ? AND event_id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("ii", $user_id, $eventId);
    $stmt->execute();
    $stmt->close();

    if ($conn->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Item removed from cart.';
    } else {
        $response['message'] = 'Item not found in cart for removal.';
    }

} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Remove from Cart Error: " . $e->getMessage());
}

echo json_encode($response);
?>