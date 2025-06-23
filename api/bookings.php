<?php
header('Content-Type: application/json');
session_start();

// --- IMPORTANT: INCLUDE CONFIG.PHP FIRST TO DEFINE DB CONSTANTS ---
require_once __DIR__ . '/../config.php'; // This defines DB_HOST, DB_USER, etc.
require_once __DIR__ . '/../includes/db_connect.php'; // This then uses those defined constants.

// --- USER AUTHENTICATION ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // FOR TESTING ONLY: Assume user 1 is logged in
    // In production, you would return an unauthorized error or redirect:
    // http_response_code(401);
    // echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    // exit;
}
$userId = $_SESSION['user_id'];
// --- END USER AUTHENTICATION ---

$requestedType = $_GET['type'] ?? 'upcoming'; // 'upcoming' or 'past'
$currentDateTime = new DateTime(); // Current date and time

$bookings = [];

try {
    // Ensure the connection is available; it's $conn now, not $pdo
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection not established.");
    }

    // SQL query to fetch booking items and related event details for the logged-in user
    $sql = "SELECT
                bi.booking_item_id,
                bi.booking_id,           -- The overall booking transaction ID
                bi.quantity AS tickets_bought,
                bi.price_per_item,
                b.status,                -- Status from the main booking transaction
                b.full_name,             -- Full name from the main booking
                b.created_at,            -- Ensure created_at is selected for potential use.
                e.id AS event_id,
                e.name AS event_name,
                e.description AS event_description,
                e.date AS event_date,
                e.time AS event_time,
                e.location AS event_location,
                e.price AS event_ticket_original_price, -- Original event price
                e.image_url
            FROM
                booking_items bi
            JOIN
                bookings b ON bi.booking_id = b.booking_id
            JOIN
                users u ON b.user_id = u.user_id     -- <--- CORRECTED THIS LINE (using u.user_id)
            JOIN
                events e ON bi.event_id = e.id
            WHERE
                b.user_id = ?           -- Use ? for prepared statements with mysqli
            ORDER BY
                e.date DESC, e.time DESC";

    // Use mysqli prepared statements
    $stmt = $conn->prepare($sql); // Prepare the statement using $conn
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('i', $userId); // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set
    $rawBookings = $result->fetch_all(MYSQLI_ASSOC); // Fetch all rows as associative array

    foreach ($rawBookings as $bookingItem) {
        // Calculate total price for this specific booking item
        $bookingItem['total_price_for_item'] = $bookingItem['tickets_bought'] * $bookingItem['price_per_item'];

        // Construct DateTime object from event date and time
        $eventDateTime = new DateTime($bookingItem['event_date'] . ' ' . $bookingItem['event_time']);

        $includeBookingItem = false;

        if ($requestedType === 'upcoming') {
            if ($eventDateTime > $currentDateTime) {
                $includeBookingItem = true;
            }
        } elseif ($requestedType === 'past') {
            if ($eventDateTime <= $currentDateTime) {
                $includeBookingItem = true;
            }
        } else {
            $includeBookingItem = true;
        }

        if ($includeBookingItem) {
            $bookings[] = [
                'booking_id' => $bookingItem['booking_item_id'],
                'event_id' => $bookingItem['event_id'],
                'event_name' => $bookingItem['event_name'],
                'event_date' => $bookingItem['event_date'],
                'event_time' => substr($bookingItem['event_time'], 0, 5),
                'event_location' => $bookingItem['event_location'],
                'tickets_bought' => $bookingItem['tickets_bought'],
                'total_price' => $bookingItem['total_price_for_item'],
                'status' => $bookingItem['status'],
                'ticket_type' => 'Standard Pass'
            ];
        }
    }

    echo json_encode(['success' => true, 'bookings' => $bookings]);

} catch (Exception $e) {
    error_log("Error in bookings.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}
?>