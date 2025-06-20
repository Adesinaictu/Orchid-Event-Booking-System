<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle pre-flight OPTIONS request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// --- Database Configuration ---
$dbHost = 'localhost';
$dbName = 'orchidfy_db'; 
$dbUser = 'root';        
$dbPass = '';           

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON received.';
        echo json_encode($response);
        exit;
    }

    // Basic data extraction and validation
    $fullName = isset($data['fullName']) ? trim($data['fullName']) : '';
    $emailAddress = isset($data['emailAddress']) ? trim($data['emailAddress']) : '';
    $subject = isset($data['subject']) ? trim($data['subject']) : 'No Subject Provided'; // Default if empty
    $message = isset($data['message']) ? trim($data['message']) : '';

    if (empty($fullName) || empty($emailAddress) || empty($message)) {
        $response['message'] = 'Full Name, Email, and Message are required fields.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address format.';
        echo json_encode($response);
        exit;
    }

    // --- Database Insertion ---
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception

        $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email_address, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullName, $emailAddress, $subject, $message]);

        

        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully! We will get back to you shortly.';

    } catch (PDOException $e) {
        // Log the error for debugging (e.g., to a file, not directly to user)
        error_log("Database error in send_contact.php: " . $e->getMessage());
        $response['message'] = 'Could not save your message to the database. Please try again later.';
    } catch (Exception $e) {
        error_log("General error in send_contact.php: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please try again later.';
    }

} else {
    $response['message'] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
}

echo json_encode($response);
?>