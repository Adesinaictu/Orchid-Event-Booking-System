<?php
session_start(); // Start the session
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit();
}

$itemId = filter_var($data['id'], FILTER_VALIDATE_INT);

if ($itemId === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit();
}

if (isset($_SESSION['cart'][$itemId])) {
    unset($_SESSION['cart'][$itemId]); // Remove the item
    echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
}
exit();
?>