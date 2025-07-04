<?php
header('Content-Type: application/json'); // Crucial: Tell the browser to expect JSON
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (for development)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allow POST, GET, OPTIONS methods
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allow these headers

// Set PHP error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FIX START ---
// 1. FIRST, include config.php to define the database constants (DB_HOST, DB_USER, etc.)
require_once __DIR__ . '/../config.php';

// 2. THEN, include db_connect.php, which will now have access to those constants
require_once __DIR__ . '/../includes/db_connect.php'; // Adjust path if necessary
// --- FIX END ---


// Function to send a JSON response
function sendJsonResponse($success, $message = '', $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit(); // Stop script execution after sending response
}

// Handle pre-flight OPTIONS request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    $request_data = json_decode($json_data, true); // Decode as associative array

    // Basic validation of JSON input
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Invalid JSON input. Error: ' . json_last_error_msg());
    }

    if (!isset($request_data['attendeeDetails'], $request_data['ticketHolders'], $request_data['paymentInfo'], $request_data['cartItems'])) {
        sendJsonResponse(false, 'Missing required data for booking (attendeeDetails, ticketHolders, paymentInfo, or cartItems).');
    }

    $attendeeDetails = $request_data['attendeeDetails'];
    $ticketHolders = $request_data['ticketHolders'];
    $paymentInfo = $request_data['paymentInfo']; // You might process this for payment gateway
    $cartItems = $request_data['cartItems'];

    // Validate essential attendee details
    if (empty($attendeeDetails['firstName']) || empty($attendeeDetails['lastName']) || empty($attendeeDetails['email'])) {
        sendJsonResponse(false, 'Attendee details (first name, last name, email) are required.');
    }

    if (empty($ticketHolders)) {
        sendJsonResponse(false, 'No ticket holders provided.');
    }

    // Start a database transaction
    if (!isset($conn) || $conn->connect_error) {
        error_log("Database connection not established or failed before transaction.");
        sendJsonResponse(false, "Database connection error.");
    }
    $conn->begin_transaction();

    try {
        // Prepare data for bookings table
        // Concatenate first and last name for 'full_name' column
        $full_name = $attendeeDetails['firstName'] . ' ' . $attendeeDetails['lastName'];
        $email = $attendeeDetails['email'];
        $phone = $attendeeDetails['phone'];
        $address = $attendeeDetails['address']; // Using direct name from schema

        $booking_date = date('Y-m-d H:i:s');
        $total_amount = 0; // Calculate total from cart items for backend verification

        foreach ($cartItems as $item) {
            $price = isset($item['price']) && is_numeric($item['price']) ? (float)$item['price'] : 0;
            $quantity = isset($item['quantity']) && is_numeric($item['quantity']) ? (int)$item['quantity'] : 0;
            $total_amount += ($price * $quantity);
        }

        // Hardcoded user ID for now - replace with actual session/user management later
        // Assuming 'user_id' in bookings table is INT and can be NULL or an actual ID
        $user_id = null; // Default to null for guest bookings, or get from session

        // 1. Insert into 'bookings' table
        // IMPORTANT: Removed 'booking_id' from INSERT as it's AUTO_INCREMENT
        // Corrected column names to match your 'bookings' table schema
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, full_name, email, phone, address, total_amount, booking_date, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare booking statement: " . $conn->error);
        }

        // Corrected bind_param string based on the actual 'bookings' table columns:
        // i: user_id (integer, assuming DB column is INT. If VARCHAR, use 's')
        // s: full_name (string)
        // s: email (string)
        // s: phone (string)
        // s: address (string)
        // d: total_amount (double)
        // s: booking_date (string)
        $stmt->bind_param(
            "isssssd", // The corrected type string for 7 parameters
            $user_id, 
            $full_name, 
            $email, 
            $phone, 
            $address, 
            $total_amount, 
            $booking_date
        );
        $stmt->execute();
        $booking_db_id = $conn->insert_id; // Get the auto-increment ID generated by the DB

        // If you need a string-based booking_id like 'BOOK_XXXX' for display, you can construct it here:
        $display_booking_id = 'BOOK_' . $booking_db_id;


        // 2. Insert into 'booking_items' table (for each event in the cart)
        $stmt_item = $conn->prepare("INSERT INTO booking_items (booking_id, event_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
        if ($stmt_item === false) {
            throw new Exception("Failed to prepare booking_items statement: " . $conn->error);
        }
        foreach ($cartItems as $item) {
            // Corrected bind_param from "siid" to "iiid" as booking_id and event_id are likely integers
            $stmt_item->bind_param("iiid", $booking_db_id, $item['id'], $item['quantity'], $item['price']);
            $stmt_item->execute();
        }

        // 3. Insert into 'ticket_holders' table (for each individual ticket)
        // Assuming ticket_holders table has columns: booking_id, event_id, first_name, last_name, email, phone
        // And event_id is an INT, the rest are strings.
        $stmt_ticket_holder_refined = $conn->prepare("INSERT INTO ticket_holders (booking_id, event_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_ticket_holder_refined === false) {
            throw new Exception("Failed to prepare ticket_holders statement: " . $conn->error);
        }
        foreach ($ticketHolders as $holder) {
            // Ensure necessary fields exist in $holder
            if (!isset($holder['event_id'], $holder['first_name'], $holder['last_name'], $holder['email'], $holder['phone'])) {
                error_log("Missing data for a ticket holder: " . print_r($holder, true));
                throw new Exception("Incomplete ticket holder data provided.");
            }

            $stmt_ticket_holder_refined->bind_param(
                "iissss", // Corrected: i for booking_id, i for event_id, ssss for names/email/phone
                $booking_db_id,
                $holder['event_id'], 
                $holder['first_name'],
                $holder['last_name'],
                $holder['email'],
                $holder['phone']
            );
            $stmt_ticket_holder_refined->execute();
        }

        // 4. Update event capacities (decrease available tickets)
        $stmt_capacity = $conn->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ? AND available_tickets >= ?");
        if ($stmt_capacity === false) {
            // --- FIX LINE 162 HERE --- Removed the extra double quote
            throw new Exception("Failed to prepare capacity update statement: " . $conn->error); 
        }
        foreach ($cartItems as $item) {
            $stmt_capacity->bind_param("iii", $item['quantity'], $item['id'], $item['quantity']);
            $stmt_capacity->execute();
            if ($conn->affected_rows === 0) {
                // This means capacity check failed (e.g., race condition, not enough tickets remaining)
                throw new Exception('Not enough tickets available for event ID: ' . $item['id'] . '. This booking was rolled back.');
            }
        }
        
        // 5. Clear the user's cart after successful booking
        // Assuming cart items are linked to a user_id or session ID
        $stmt_clear_cart = $conn->prepare("DELETE FROM cart_item WHERE user_id = ?");
        if ($stmt_clear_cart === false) {
             throw new Exception("Failed to prepare clear cart statement: " . $conn->error);
        }
        $stmt_clear_cart->bind_param("i", $user_id); // Use the same $user_id as for bookings
        $stmt_clear_cart->execute();
        // No need to close here if it's going to be closed in finally, unless it's the only stmt
        // $stmt_clear_cart->close();


        // If all successful, commit the transaction
        $conn->commit();

        // Send success response using the display_booking_id
        sendJsonResponse(true, 'Booking placed successfully!', ['bookingId' => $display_booking_id]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Booking Transaction Error: " . $e->getMessage()); // Log the actual error for debugging
        sendJsonResponse(false, 'Booking failed: ' . $e->getMessage());
    } finally {
        // Close statements (ensure all are explicitly closed even if some are commented out for now)
        if (isset($stmt) && $stmt !== false) $stmt->close();
        if (isset($stmt_item) && $stmt_item !== false) $stmt_item->close();
        if (isset($stmt_ticket_holder_refined) && $stmt_ticket_holder_refined !== false) $stmt_ticket_holder_refined->close();
        if (isset($stmt_capacity) && $stmt_capacity !== false) $stmt_capacity->close();
        if (isset($stmt_clear_cart) && $stmt_clear_cart !== false) $stmt_clear_cart->close();
        
        // It's generally good practice to explicitly close the connection when done with all database operations.
        // Or if db_connect.php creates a persistent connection, it might be left open for subsequent scripts in the same request.
        // For an API endpoint that finishes after its task, closing is safer.
        if (isset($conn) && $conn !== false) {
            $conn->close();
        }
    }

} else {
    // Not a POST request
    sendJsonResponse(false, 'Invalid request method. Only POST is allowed.');
}
?>