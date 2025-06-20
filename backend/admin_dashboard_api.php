<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
require_once 'db_connect.php';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    $pdo = db_connect(); 

    // 1. Get Total Events
    $stmt = $pdo->query("SELECT COUNT(*) AS total_events FROM events");
    $totalEvents = $stmt->fetchColumn();

    // 2. Get Total Bookings
    $stmt = $pdo->query("SELECT COUNT(*) AS total_bookings FROM bookings");
    $totalBookings = $stmt->fetchColumn();

    // 3. Get Total Revenue 
    $stmt = $pdo->query("SELECT SUM(total_amount) AS total_revenue FROM bookings WHERE status = 'confirmed'"); // Only sum confirmed bookings
    $totalRevenue = $stmt->fetchColumn();
    $totalRevenue = $totalRevenue ?? 0; // Handle NULL if no bookings

    //  bookings has 'event_id' and 'quantity', and events has 'price'
  
    $stmt = $pdo->query("SELECT SUM(b.quantity * e.price) AS total_revenue
                         FROM bookings b
                         JOIN events e ON b.event_id = e.id
                         WHERE b.status = 'confirmed'");
    $totalRevenue = $stmt->fetchColumn();
    $totalRevenue = $totalRevenue ?? 0;
   


    //  Get Recent Bookings 
    $stmt = $pdo->prepare("SELECT
                                b.id AS booking_id,
                                e.name AS event_name,
                                u.username AS user_name, 
                                b.quantity,
                                b.total_amount,
                                b.booking_date
                           FROM bookings b
                           JOIN events e ON b.event_id = e.id
                           JOIN users u ON b.user_id = u.id 
                           ORDER BY b.booking_date DESC, b.id DESC
                           LIMIT 5"); 
    $stmt->execute();
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //  Get Upcoming Events 
    $stmt = $pdo->prepare("SELECT name, date FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3");
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $response['success'] = true;
    $response['data'] = [
        'totalEvents' => $totalEvents,
        'totalBookings' => $totalBookings,
        'totalRevenue' => $totalRevenue,
        'recentBookings' => $recentBookings,
        'upcomingEvents' => $upcomingEvents
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
    error_log('Admin Dashboard API Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'General Error: ' . $e->getMessage();
    error_log('Admin Dashboard API Error: ' . $e->getMessage());
}

echo json_encode($response);
?>