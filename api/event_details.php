<?php
// ... (keep display_errors and error_reporting for now if you added them, but remove them for production)

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'data' => null];

$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    $response['message'] = 'Event ID is required.';
    echo json_encode($response);
    exit();
}

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection not established.");
    }

    // UPDATED SQL query: Removed lines related to 'categories' table
    $sql = "SELECT
                e.id,
                e.name,
                e.description,
                e.date,
                e.time,
                e.location,
                e.price,
                e.total_tickets,
                e.tickets_sold,
                (e.total_tickets - e.tickets_sold) AS available_tickets,
                e.image_url
                -- c.name AS category_name -- REMOVED THIS LINE
            FROM
                events e
            -- LEFT JOIN
            --     categories c ON e.category_id = c.category_id -- REMOVED THIS LINE
            WHERE
                e.id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $eventDetails = $result->fetch_assoc();

    if ($eventDetails) {
        $response['success'] = true;
        $response['message'] = 'Event details fetched successfully.';
        $response['data'] = $eventDetails;
    } else {
        $response['message'] = 'Event not found.';
        http_response_code(404);
    }

} catch (Exception $e) {
    error_log("Error fetching event details for ID {$eventId}: " . $e->getMessage());
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>