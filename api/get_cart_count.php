<?php
session_start(); // Start the session to access $_SESSION
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');


ini_set('display_errors', 1);
error_reporting(E_ALL);
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    // Assuming cart items are stored as {id: ..., quantity: ...}
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

echo json_encode(['success' => true, 'count' => $cartCount]);
?>