<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php';

$response = []; 


$user_id = 1; 

try {
    $pdo = db_connect();

    // Join cart_items with events to get event name and price
    $stmt = $pdo->prepare("
        SELECT 
            ci.event_id as id, 
            e.name, 
            e.price, 
            ci.quantity
        FROM 
            cart_items ci
        JOIN 
            events e ON ci.event_id = e.id
        WHERE 
            ci.user_id = ?
        ORDER BY 
            ci.added_at ASC
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cartItems); // Return the array directly

} catch (PDOException $e) {
    error_log("get_cart PDO Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error retrieving cart: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("get_cart General Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'An error occurred retrieving cart: ' . $e->getMessage()]);
}
?>