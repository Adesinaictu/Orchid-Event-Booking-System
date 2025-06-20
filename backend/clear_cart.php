<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Clear the cart array
    echo json_encode(['success' => true, 'message' => 'Cart cleared successfully.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Cart was already empty.']); // Or false if you prefer an explicit "was empty" message
}
exit();
?>