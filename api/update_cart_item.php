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

require_once '../includes/db_connect.php'; // Adjust path if needed

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
$quantity = $data['quantity'] ?? null;

if (!is_numeric($eventId) || !is_numeric($quantity) || $quantity < 0) {
    $response['message'] = 'Invalid eventId or quantity.';
    echo json_encode($response);
    exit;
}

$eventId = (int)$eventId;
$quantity = (int)$quantity;

// Hardcoded user ID - replace with actual session/user management later
$user_id = 1;

try {
    $conn->begin_transaction();

    // Check if event exists (optional but good practice)
    $stmt = $conn->prepare("SELECT available_tickets FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();

    if (!$event) {
        throw new Exception("Event not found.");
    }
    $availableTickets = (int)$event['available_tickets'];

    if ($quantity === 0) {
        // If quantity is 0, remove the item
        $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $user_id, $eventId);
        $stmt->execute();
        $stmt->close();
        $response['success'] = true;
        $response['message'] = 'Item removed from cart.';
    } else {
        // Check current quantity in cart for this event and user
        $stmt_check_cart = $conn->prepare("SELECT quantity FROM cart_item WHERE user_id = ? AND event_id = ?");
        $stmt_check_cart->bind_param("ii", $user_id, $eventId);
        $stmt_check_cart->execute();
        $result_check_cart = $stmt_check_cart->get_result();
        $currentCartItem = $result_check_cart->fetch_assoc();
        $stmt_check_cart->close();

        // Check if new quantity exceeds available tickets
        if ($quantity > $availableTickets) {
            throw new Exception("Not enough tickets available. Only " . $availableTickets . " remaining.");
        }

        if ($currentCartItem) {
            // Item exists, update quantity
            $stmt = $conn->prepare("UPDATE cart_item SET quantity = ?, created_at = NOW() WHERE user_id = ? AND event_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $eventId);
            $stmt->execute();
            $stmt->close();
            $response['message'] = 'Cart item quantity updated.';
        } else {
            // Item does not exist, insert new item
            $stmt = $conn->prepare("INSERT INTO cart_item (user_id, event_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iii", $user_id, $eventId, $quantity);
            $stmt->execute();
            $stmt->close();
            $response['message'] = 'New item added to cart.';
        }
        $response['success'] = true;
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Update Cart Item Error: " . $e->getMessage());
}

echo json_encode($response);
?>