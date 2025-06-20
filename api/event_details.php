<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins for development
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Display PHP errors (for debugging only, REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php'; // Adjust path if necessary

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($event_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Event ID not provided or invalid.']);
    $conn->close();
    exit();
}

// Ensure 'image_url' is selected, not 'image'
$sql = "SELECT id, name, description, date, time, location, price, image_url, category FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed.', 'error_details' => $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $event = $result->fetch_assoc();
    $event['formatted_date'] = date('M d, Y', strtotime($event['date']));
    $event['formatted_time'] = date('h:i A', strtotime($event['time']));

    echo json_encode(['success' => true, 'data' => $event]);
} else {
    echo json_encode(['success' => false, 'message' => 'Event not found.']);
}

$stmt->close();
$conn->close();
?>