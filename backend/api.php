<?php
// Remove these lines once everything is working perfectly, they are for debugging only!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// End of debugging lines

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// Include the database connection file
require_once 'db_connect.php';

if (isset($_GET['id']) && $_GET['id'] !== '') {
    $eventId = $_GET['id'];
    $sql = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(["error" => "Failed to prepare single event statement: " . $conn->error]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("i", $eventId); // 'i' for integer ID
    $stmt->execute();
    $result = $stmt->get_result();
    $event = []; // Initialize as an empty array

    if ($result->num_rows > 0) {
        $event[] = $result->fetch_assoc(); // Fetch the single event into an array
    }

    echo json_encode($event); // Output as JSON
    $stmt->close();
    $conn->close();
    exit(); // Exit to prevent further execution of filter/search logic
}


// SQL query base - selects all events initially (This part runs only if no 'id' is in the URL)
$sql = "SELECT * FROM events WHERE 1=1"; // 'WHERE 1=1' is a trick to easily append conditions

$params = []; // Array to hold parameters for prepared statement
$types = "";   // String to hold parameter types ('s' for string, 'i' for integer, 'd' for double)

// --- Handle filters from GET request parameters ---

// Search filter: by event name, description, or location
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = '%' . $_GET['search'] . '%'; // Add wildcards for LIKE operator
    $sql .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss"; // Three string parameters
}

// Location filter
if (isset($_GET['location']) && $_GET['location'] !== '' && $_GET['location'] !== 'all') {
    $sql .= " AND location = ?";
    $params[] = $_GET['location'];
    $types .= "s"; // One string parameter
}

// Date filter
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $sql .= " AND date = ?";
    $params[] = $_GET['date'];
    $types .= "s"; // One string parameter
}

// Category filter
if (isset($_GET['category']) && $_GET['category'] !== '' && $_GET['category'] !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $_GET['category'];
    $types .= "s"; // One string parameter
}

// --- Prepare and execute the SQL statement ---

// Prepare the SQL query to prevent SQL injection
$stmt = $conn->prepare($sql);

// Check if statement preparation failed
if ($stmt === false) {
    echo json_encode(["error" => "Failed to prepare statement: " . $conn->error]);
    $conn->close();
    exit();
}

// Bind parameters if there are any filters
if (!empty($params)) {
    // THIS IS THE CORRECTED PART for filters:
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key]; // Create a reference to each parameter
    }
    array_unshift($refs, $types); // Prepend the types string as the first argument

    // Use call_user_func_array with the references
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Execute the prepared statement
$stmt->execute();

// Get the result set from the executed statement
$result = $stmt->get_result();

// Fetch all results into an array
$events = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Output the events array as JSON
echo json_encode($events);

// Close the statement and database connection
$stmt->close();
$conn->close();
?>