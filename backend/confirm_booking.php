<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type, Authorization'); 

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Remove or comment out these lines in a production environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Include Database Connection ---
// Ensure db_connect.php is in the same directory or adjust path.
// It should return a PDO object.
require_once 'db_connect.php'; 

// --- Initialize Response Array ---
$response = [
    'success' => false,
    'bookingId' => null,
    'message' => 'An unknown error occurred.' // Default message
];

// --- 1. Validate Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
    echo json_encode($response);
    exit;
}

// --- 2. Get and Decode JSON Input ---
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON data received: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

// --- 3. Basic Input Validation ---
if (!isset($data['attendeeDetails'], $data['cartItems'], $data['totalAmount']) ||
    !is_array($data['attendeeDetails']) || //  attendeeDetails is an array
    !is_array($data['cartItems']) ||     //  cartItems is an array
    empty($data['cartItems']) ||         //  cartItems is not empty
    !is_numeric($data['totalAmount'])) { //  totalAmount is a number
    $response['message'] = 'Invalid or incomplete booking data provided. Required: attendeeDetails, cartItems (non-empty array), totalAmount.';
    // Log the received data for debugging
    error_log("Invalid input data received: " . print_r($data, true));
    echo json_encode($response);
    exit;
}

// Extract attendee details 
$firstName = $data['attendeeDetails']['firstName'] ?? '';
$lastName = $data['attendeeDetails']['lastName'] ?? '';
$email = $data['attendeeDetails']['email'] ?? '';
$phone = $data['attendeeDetails']['phone'] ?? '';
$address = $data['attendeeDetails']['address'] ?? '';

if (empty($firstName) || empty($lastName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Attendee details are incomplete or invalid (First Name, Last Name, Email are required).';
    echo json_encode($response);
    exit;
}

// --- Start Database Transaction ---
try {
    $pdo = db_connect(); 
    $pdo->beginTransaction();

    // --- 4. User Handling (Retrieve or Create) ---
    $user_id = null; // Initialize as null

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

    if ($existingUser) {
        $user_id = $existingUser['id'];
        error_log("Existing user found: User ID " . $user_id);
    } else {
        
        $username = trim($firstName . ' ' . $lastName);
        if (empty($username)) { // Fallback if name is empty
            $username = 'Guest_' . uniqid();
        }
        $dummyPasswordHash = password_hash(uniqid(), PASSWORD_DEFAULT); // Dummy hash
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())"); // Added created_at
        $stmt->execute([$username, $email, $dummyPasswordHash]);
        $user_id = $pdo->lastInsertId();
        error_log("New user created: User ID " . $user_id);
    }

    if ($user_id === null || $user_id === 0) {
        throw new Exception("Failed to determine or create user ID.");
    }

    // --- 5. Insert into `bookings` table ---
    $totalAmount = $data['totalAmount'];
    $bookingDate = date('Y-m-d H:i:s');
    $status = 'confirmed'; 

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, total_amount, booking_date, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $totalAmount, $bookingDate, $status]);
    $bookingId = $pdo->lastInsertId();

    if ($bookingId === 0) {
        throw new Exception("Failed to insert booking into database.");
    }
    error_log("Booking created with ID: " . $bookingId);

    // --- 6. Insert into `booking_items` table ---
    $stmt_items = $pdo->prepare("INSERT INTO booking_items (booking_id, event_id, quantity, price_at_booking) VALUES (?, ?, ?, ?)");

    foreach ($data['cartItems'] as $item) {
        if (!isset($item['id'], $item['quantity'], $item['price']) ||
            !is_numeric($item['id']) ||
            !is_numeric($item['quantity']) ||
            !is_numeric($item['price'])) {
            throw new Exception("Invalid item data in cartItems array.");
        }

        $eventId = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        $priceAtBooking = (float)$item['price'];

      
        $stmt_update_event = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
        if (!$stmt_update_event->execute([$quantity, $eventId])) {
            throw new Exception("Failed to update available tickets for event ID: " . $eventId);
        }
       

        if (!$stmt_items->execute([$bookingId, $eventId, $quantity, $priceAtBooking])) {
            throw new Exception("Failed to insert booking item for event ID: " . $eventId);
        }
    }

    $pdo->commit(); // Commit the transaction if all operations succeed

    $response['success'] = true;
    $response['bookingId'] = $bookingId;
    $response['message'] = 'Booking successfully placed!';
    error_log("Booking successful! Booking ID: " . $bookingId);

} catch (PDOException $e) {
    $pdo->rollBack(); 
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Booking PDO Error: " . $e->getMessage() . " Data: " . json_encode($data));
} catch (Exception $e) {
    $pdo->rollBack(); 
    $response['message'] = 'Booking failed: ' . $e->getMessage();
    error_log("Booking General Error: " . $e->getMessage() . " Data: " . json_encode($data));
}

echo json_encode($response);
?>