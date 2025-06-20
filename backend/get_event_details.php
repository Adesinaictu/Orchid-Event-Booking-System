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

$response = null; // Will be the event object or null

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Event ID is required and must be a number.']);
    exit;
}

$eventId = (int)$_GET['id'];

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            description, 
            date, 
            time, 
            location, 
            price, 
            category,
            image_url as image, -- Assuming your image column is named image_url
            available_tickets    -- Assuming you have this column
        FROM 
            events 
        WHERE 
            id = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        echo json_encode($event);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
    }

} catch (PDOException $e) {
    error_log("get_event_details PDO Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error retrieving event details: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("get_event_details General Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'An error occurred retrieving event details: ' . $e->getMessage()]);
}
?>