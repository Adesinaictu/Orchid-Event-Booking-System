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

require_once 'db_connect.php';

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


$user_id = 1; 
              // e.g., $user_id = $_SESSION['user_id'] ?? create_guest_user_and_session();

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // 1. Get event details (especially price) from the 'events' table
    $stmt = $pdo->prepare("SELECT name, price, available_tickets FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception("Event not found.");
    }

    $eventName = $event['name'];
    $eventPrice = (float)$event['price'];
    $availableTickets = (int)$event['available_tickets']; // Assuming you have this column

   
    $stmt_check_cart = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND event_id = ?");
    $stmt_check_cart->execute([$user_id, $eventId]);
    $currentCartQuantity = $stmt_check_cart->fetchColumn();
    $currentCartQuantity = $currentCartQuantity ? (int)$currentCartQuantity : 0;

    if (($currentCartQuantity + $quantity) > $availableTickets) {
        throw new Exception("Not enough tickets available for " . $eventName . ". Only " . $availableTickets . " remaining.");
    }


    // 2. Check if the item already exists in the cart for this user
    $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $eventId]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        // Item exists, update quantity
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ?, added_at = NOW() WHERE id = ?");
        $stmt->execute([$quantity, $cartItem['id']]);
        $response['message'] = 'Quantity updated in cart.';
    } else {
        // Item does not exist, insert new item
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, event_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $eventId, $quantity]);
        $response['message'] = 'Item added to cart.';
    }

    $pdo->commit();
    $response['success'] = true;

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("add_to_cart PDO Error: " . $e->getMessage());
} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = $e->getMessage();
    error_log("add_to_cart General Error: " . $e->getMessage());
}

echo json_encode($response);
?>