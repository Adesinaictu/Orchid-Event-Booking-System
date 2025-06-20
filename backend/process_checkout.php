<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'booking_id' => null];

// --- Database Configuration ---
$dbHost = 'localhost';
$dbName = 'orchidfy_db';
$dbUser = 'root';
$dbPass = '';

// Check if user is logged in (optional, if you have a user system)
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON received.';
        echo json_encode($response);
        exit;
    }

    // Extract Attendee Details
    $firstName = trim($data['attendee']['firstName'] ?? '');
    $lastName = trim($data['attendee']['lastName'] ?? '');
    $email = trim($data['attendee']['email'] ?? '');
    $phone = trim($data['attendee']['phone'] ?? '');
    $address = trim($data['attendee']['address'] ?? '');

  
    $cardName = trim($data['payment']['cardName'] ?? '');
    
    $cartItems = $data['cartItems'] ?? [];
    $totalAmount = (float)($data['totalAmount'] ?? 0);

    // Basic Server-Side Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($cartItems) || $totalAmount <= 0) {
        $response['message'] = 'Missing required attendee or cart information.';
        echo json_encode($response);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format provided.';
        echo json_encode($response);
        exit;
    }

    // Start Database Transaction
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction(); // Start the transaction

        // 1. Insert into bookings table
        $stmtBooking = $pdo->prepare(
            "INSERT INTO bookings (user_id, full_name, email, phone, address, total_amount, status, payment_status)
             VALUES (?, ?, ?, ?, ?, ?, 'confirmed', 'paid')"
        );
        $stmtBooking->execute([
            $userId, // will be NULL if guest, or $_SESSION['user_id'] if logged in
            "$firstName $lastName",
            $email,
            $phone,
            $address,
            $totalAmount
        ]);
        $bookingId = $pdo->lastInsertId(); // Get the ID of the newly inserted booking

        // 2. Insert into booking_items table for each cart item
        $stmtBookingItem = $pdo->prepare(
            "INSERT INTO booking_items (booking_id, event_id, ticket_quantity, price_at_booking)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($cartItems as $item) {
            // Ensure necessary fields exist and are of correct type
            $eventId = $item['event_id'] ?? null;
            $quantity = $item['quantity'] ?? null;
            $priceAtBooking = $item['price'] ?? null;

            if ($eventId === null || $quantity === null || $priceAtBooking === null) {
                throw new Exception("Invalid item data in cart.");
            }

            $stmtBookingItem->execute([
                $bookingId,
                $eventId,
                $quantity,
                $priceAtBooking
            ]);
            //  Decrease event capacity/availability here if  events table tracks it
            // $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE event_id = ?")
            //     ->execute([$quantity, $eventId]);
        }

        unset($_SESSION['cart']);

        $pdo->commit(); // Commit the transaction if all operations were successful

        $response['success'] = true;
        $response['message'] = 'Booking confirmed successfully!';
        $response['booking_id'] = $bookingId;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on database error
        error_log("PDOException in process_checkout.php: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        $response['message'] = 'Database error during booking: ' . $e->getMessage();
        // For production, you might return a generic message: 'An error occurred during booking. Please try again.'
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback on general error
        error_log("General Exception in process_checkout.php: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred during booking. ' . $e->getMessage();
    }

} else {
    $response['message'] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
}

echo json_encode($response);
?>