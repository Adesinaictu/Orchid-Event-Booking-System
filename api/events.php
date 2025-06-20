<?php
header('Content-Type: application/json'); // Tell the client we're sending JSON
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (for development)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Display PHP errors (for debugging only, REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
require_once '../includes/db_connect.php';

$events = [];

// Build the SQL query dynamically based on GET parameters
// CORRECTED: Changed 'image' to 'image_url'
$sql = "SELECT id, name, description, date, time, location, price, image_url, category FROM events";
$conditions = [];
$params = [];
$types = '';

// Search by name or description
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}

// Filter by location
if (isset($_GET['location']) && !empty($_GET['location']) && $_GET['location'] !== 'all' && $_GET['location'] !== 'Filter by Location') {
    $conditions[] = "location = ?";
    $params[] = $_GET['location'];
    $types .= 's';
}

// Filter by date
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $conditions[] = "date = ?";
    $params[] = $_GET['date'];
    $types .= 's';
}

// Filter by category
if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'all' && $_GET['category'] !== 'Filter by Category') {
    $conditions[] = "category = ?";
    $params[] = $_GET['category'];
    $types .= 's';
}

// Add conditions to the SQL query
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// ORDER BY date ASC should be the default ordering
$sql .= " ORDER BY date ASC";

// Use prepared statements for security
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed.', 'error_details' => $conn->error]);
    $conn->close();
    exit();
}

// Bind parameters if there are any
if (!empty($params)) {
    // Dynamically bind parameters
    // The call_user_func_array requires parameters to be references for bind_param
    $bind_names = [];
    for ($i = 0; $i < count($params); $i++) {
        $bind_name = 'param' . $i;
        $$bind_name = $params[$i]; // Create a variable variable for each parameter
        $bind_names[] = &$$bind_name;
    }
    array_unshift($bind_names, $types);
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$result = $stmt->get_result(); // Get the result set

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format date and time for better display
            $row['formatted_date'] = date('M d, Y', strtotime($row['date']));
            $row['formatted_time'] = date('h:i A', strtotime($row['time']));
            $events[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $events]);
    } else {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No events found matching your criteria.']);
    }
} else {
    // Log the error for debugging purposes (never display directly to users in production)
    error_log("Database query error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve events.', 'error_details' => $stmt->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>