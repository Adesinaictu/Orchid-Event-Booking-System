<?php
// C:\xampp\htdocs\orchid\api\admin_dashboard_api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Display PHP errors (for debugging only, REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Always include config.php FIRST to define DB_HOST, DB_USER, etc.
require_once __DIR__ . '/../config.php';
// Then include db_connect.php which establishes the global $conn mysqli connection
require_once __DIR__ . '/../includes/db_connect.php';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    // Use the global $conn mysqli object established by db_connect.php
    global $conn; // Declare $conn as global to use the connection from db_connect.php

    // 1. Get Total Events
    $stmt = $conn->query("SELECT COUNT(*) AS total_events FROM events");
    if ($stmt) {
        $totalEvents = $stmt->fetch_assoc()['total_events'];
        $stmt->free_result();
    } else {
        throw new Exception("Failed to retrieve total events: " . $conn->error);
    }


    // 2. Get Total Bookings
    $stmt = $conn->query("SELECT COUNT(*) AS total_bookings FROM bookings");
    if ($stmt) {
        $totalBookings = $stmt->fetch_assoc()['total_bookings'];
        $stmt->free_result();
    } else {
        throw new Exception("Failed to retrieve total bookings: " . $conn->error);
    }


    // 3. Get Total Revenue (using the SUM of total_amount from the bookings table, confirmed only)
    $stmt = $conn->query("SELECT SUM(total_amount) AS total_revenue FROM bookings WHERE status = 'confirmed'");
    if ($stmt) {
        $row = $stmt->fetch_assoc();
        $totalRevenue = $row['total_revenue'];
        $stmt->free_result();
    } else {
        throw new Exception("Failed to retrieve total revenue: " . $conn->error);
    }
    $totalRevenue = $totalRevenue ?? 0; // Handle NULL if no confirmed bookings (SUM of empty set is NULL)


    // Get Recent Bookings
    // This query now correctly joins bookings with booking_items, events, and users
    $stmt = $conn->prepare("SELECT
                                b.booking_id AS booking_id,
                                e.name AS event_name,
                                u.username AS user_name,
                                bi.quantity, /* Getting quantity from booking_items */
                                b.total_amount,
                                b.booking_date,
                                b.status,
                                b.payment_status
                             FROM bookings b
                             JOIN booking_items bi ON b.booking_id = bi.booking_id
                             JOIN events e ON bi.event_id = e.id
                             JOIN users u ON b.user_id = u.user_id /* FIX: Changed u.id to u.user_id */
                             GROUP BY b.booking_id, u.username, e.name, bi.quantity, b.total_amount, b.booking_date, b.status, b.payment_status /* Ensure all non-aggregated SELECTed columns are in GROUP BY */
                             ORDER BY b.booking_date DESC, b.booking_id DESC
                             LIMIT 5");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement for recent bookings: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $recentBookings = [];
    while ($row = $result->fetch_assoc()) {
        // Format date if needed, though this might be better on the frontend
        $row['booking_date'] = date('Y-m-d H:i:s', strtotime($row['booking_date']));
        $recentBookings[] = $row;
    }
    $stmt->close();


    // Get Upcoming Events
    $stmt = $conn->prepare("SELECT name, date FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement for upcoming events: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $upcomingEvents = [];
    while ($row = $result->fetch_assoc()) {
        $upcomingEvents[] = $row;
    }
    $stmt->close();


    $response['success'] = true;
    $response['data'] = [
        'totalEvents' => $totalEvents,
        'totalBookings' => $totalBookings,
        'totalRevenue' => $totalRevenue,
        'recentBookings' => $recentBookings,
        'upcomingEvents' => $upcomingEvents
    ];

} catch (Exception $e) { // Catch general Exceptions for both mysqli errors and custom throws
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Admin Dashboard API Error: ' . $e->getMessage());
} finally {
    // Close the connection if it was opened and not already closed by die()
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $conn->close();
    }
}

echo json_encode($response);
?>