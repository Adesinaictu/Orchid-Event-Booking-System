<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins for now
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'data' => null];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $eventId = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("SELECT
            e.id,
            e.name,
            e.description,
            e.date,
            e.time,
            e.location,
            e.price,
            e.image_url,
            e.available_tickets,
            c.name AS category_name
        FROM
            events e
        LEFT JOIN
            categories c ON e.category_id = c.id
        WHERE
            e.id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Event found.';
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Event not found.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Get Event Error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid event ID provided.';
}

echo json_encode($response);
?>