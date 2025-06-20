<?php


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/backend/db_connect.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$response = array();
$events = array();


$sql = "SELECT id, title, description, event_date, event_time, location, image_url, category FROM events WHERE 1=1";
$params = []; 
$param_types = "";  


if (isset($_GET['category']) && $_GET['category'] !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $_GET['category'];
    $param_types .= "s";
}
if (isset($_GET['location']) && $_GET['location'] !== 'all') {
    $sql .= " AND location = ?";
    $params[] = $_GET['location'];
    $param_types .= "s";
}
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $sql .= " AND event_date = ?"; 
    $params[] = $_GET['date'];
    $param_types .= "s";
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%'; 
    $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

$sql .= " ORDER BY event_date ASC, event_time ASC";  

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!empty($params)) {

    call_user_func_array([$stmt, 'bind_param'], array_merge([$param_types], refValues($params)));
}

function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) 
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $row['name'] = $row['title']; 
            $row['date'] = $row['event_date'];
            $row['time'] = $row['event_time'];
            $row['image'] = $row['image_url'];
            $row['venue'] = $row['location']; 
            unset($row['title'], $row['event_date'], $row['event_time'], $row['image_url'], $row['location']); // Remove original DB columns if transformed
            $events[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $events;
    } else {
        $response['success'] = true; 
        $response['data'] = [];
        $response['message'] = "No events found.";
    }
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = "Query execution error: " . $stmt->error;
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>