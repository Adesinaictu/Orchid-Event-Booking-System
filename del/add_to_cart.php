<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . ''; 


$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
    echo json_encode($response);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON data received: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

// Basic validation for incoming data
if (!isset($data['eventId']) || !isset($data['quantity']) ||
    !is_numeric($data['eventId']) || !is_numeric($data['quantity'])) {
    $response['message'] = 'Missing or invalid eventId or quantity.';
    echo json_encode($response);
    exit;
}

$eventId = (int)$data['eventId'];
$quantity = (int)$data['quantity'];

if ($quantity <= 0) {
    $response['message'] = 'Quantity must be at least 1.';
    echo json_encode($response);
    exit;
}

// Hardcoded user ID for now. Implement session-based user_id later.
$user_id = 1;

try {
    // Start transaction using mysqli
    $conn->begin_transaction();

    // 1. Get event details (price, available_tickets) from the 'events' table
    $stmt = $conn->prepare("SELECT name, price, available_tickets FROM events WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare event details statement: " . $conn->error);
    }
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();



    // For insert:
$stmt_insert->execute([$user_id, $eventId, $quantity]);
error_log("Attempted to insert cart item: UserID=$user_id, EventID=$eventId, Quantity=$quantity. Rows affected: " . $stmt_insert->affected_rows);
$stmt_insert->close();

// For update:
$stmt_update->execute([$quantity, $cartItem['id']]);
error_log("Attempted to update cart item: ItemID=" . $cartItem['id'] . ", Quantity=$quantity. Rows affected: " . $stmt_update->affected_rows);
$stmt_update->close();

    if 
    
    
    
    (!$event) {
        throw new Exception("Event not found.");
    }

    $eventName = $event['name'];
    $eventPrice = (float)$event['price'];
    $availableTickets = (int)$event['available_tickets'];

    // 2. Check current quantity in cart for this event and user
    $stmt_check_cart = $conn->prepare("SELECT quantity FROM cart_item WHERE user_id = ? AND event_id = ?"); // Use cart_item
    if ($stmt_check_cart === false) {
        throw new Exception("Failed to prepare cart check statement: " . $conn->error);
    }
    $stmt_check_cart->bind_param("ii", $user_id, $eventId);
    $stmt_check_cart->execute();
    $result_check_cart = $stmt_check_cart->get_result();
    $currentCartQuantity = $result_check_cart->fetch_row()[0] ?? 0;
    $stmt_check_cart->close();

    // Check if adding quantity exceeds available tickets
    if (($currentCartQuantity + $quantity) > $availableTickets) {
        throw new Exception("Not enough tickets available for " . $eventName . ". Only " . ($availableTickets - $currentCartQuantity) . " remaining. You currently have " . $currentCartQuantity . " in your cart.");
    }

    // 3. Check if the item already exists in the cart for this user
    $stmt_find_item = $conn->prepare("SELECT id FROM cart_item WHERE user_id = ? AND event_id = ?"); // Use cart_item
    if ($stmt_find_item === false) {
        throw new Exception("Failed to prepare find item statement: " . $conn->error);
    }
    $stmt_find_item->bind_param("ii", $user_id, $eventId);
    $stmt_find_item->execute();
    $result_find_item = $stmt_find_item->get_result();
    $cartItem = $result_find_item->fetch_assoc();
    $stmt_find_item->close();

    if ($cartItem) {
        // Item exists, update quantity
        $stmt_update = $conn->prepare("UPDATE cart_item SET quantity = quantity + ?, created_at = NOW() WHERE id = ?"); // Use cart_item, created_at
        if ($stmt_update === false) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        $stmt_update->bind_param("ii", $quantity, $cartItem['id']);
        $stmt_update->execute();
        $stmt_update->close();
        $response['message'] = 'Quantity updated in cart.';
    } else {
        // Item does not exist, insert new item
        $stmt_insert = $conn->prepare("INSERT INTO cart_item (user_id, event_id, quantity, created_at) VALUES (?, ?, ?, NOW())"); // Use cart_item, created_at
        if ($stmt_insert === false) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }
        $stmt_insert->bind_param("iii", $user_id, $eventId, $quantity);
        $stmt_insert->execute();
        $stmt_insert->close();
        $response['message'] = 'Item added to cart.';
    }

    $conn->commit(); // Commit the transaction
    $response['success'] = true;

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    $response['message'] = 'Error adding to cart: ' . $e->getMessage();
    error_log("add_to_cart Error: " . $e->getMessage());
}

echo json_encode($response);
?>