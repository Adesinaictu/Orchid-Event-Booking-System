<?php
// Set PHP error reporting for debugging (CRUCIAL during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle pre-flight OPTIONS request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

session_start();

// --- IMPORTANT FIX HERE ---
// FIRST: Include config.php to define database constants (DB_HOST, DB_USER, etc.)
require_once __DIR__ . '/../config.php';

// SECOND: Include db_connect.php, which can now use those defined constants
require_once __DIR__ . '/../includes/db_connect.php';

// Function to send a JSON response and terminate script
function sendJsonResponse($success, $message = '', $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

// --- User ID Handling ---
// In a real application, you would get this from the session after user login.
// For now, it's hardcoded as per your existing logic.
$user_id = 1;

try {
    $stmt = $conn->prepare("
        SELECT
            ci.event_id as id,
            e.name,
            e.price,
            ci.quantity
        FROM
            cart_item ci
        JOIN
            events e ON ci.event_id = e.id
        WHERE
            ci.user_id = ?
        ORDER BY
            ci.created_at ASC
    ");

    if ($stmt === false) {
        error_log("mysqli prepare failed in get_cart.php: (" . $conn->errno . ") " . $conn->error);
        throw new Exception("Failed to prepare database statement for cart retrieval.");
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartItems = $result->fetch_all(MYSQLI_ASSOC);

    sendJsonResponse(true, 'Cart items retrieved successfully.', ['cart' => $cartItems]);

} catch (Exception $e) {
    error_log("get_cart.php Error: " . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, 'An error occurred retrieving cart: ' . $e->getMessage());
} finally {
    // It's generally good practice to close statements, but keep the connection open
    // if other parts of the script might use it, or if it's managed by db_connect.php
    if (isset($stmt) && $stmt !== false) {
        $stmt->close();
    }
    // Do NOT close the $conn here if db_connect.php keeps it global for other scripts.
    // If db_connect.php auto-closes, that's fine, but explicitly closing here
    // can cause issues if other parts of the same request need the connection.
    // Generally, let the script end to close the connection automatically,
    // or manage it more carefully with a persistent connection pool if performance demands it.
}
?>