<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// --- USER AUTHENTICATION ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // FOR TESTING ONLY: Assume user 1 is logged in. Remove for production.
}
$userId = $_SESSION['user_id'];
// --- END USER AUTHENTICATION ---

$bookingItemId = $_GET['booking_item_id'] ?? null;

// --- DEBUGGING START ---
error_log("DEBUG: Attempting ticket download for booking_item_id: " . var_export($bookingItemId, true) . " and user_id: " . var_export($userId, true));
// --- DEBUGGING END ---

if (!$bookingItemId) {
    http_response_code(400);
    die('Error: Booking Item ID is required for ticket download.');
}

$ticketDetails = null;

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection not established.");
    }

    $sql = "SELECT
                bi.booking_item_id,
                bi.booking_id,
                bi.quantity AS tickets_bought,
                bi.price_per_item,
                b.status AS booking_status,
                b.full_name AS customer_full_name,
                b.email AS customer_email,
                b.phone AS customer_phone,
                b.address AS customer_address,
                b.created_at AS booking_date,
                u.username,
                e.name AS event_name,
                e.description AS event_description,
                e.date AS event_date,
                e.time AS event_time,
                e.location AS event_location
            FROM
                booking_items bi
            JOIN
                bookings b ON bi.booking_id = b.booking_id
            JOIN
                users u ON b.user_id = u.user_id
            JOIN
                events e ON bi.event_id = e.id
            WHERE
                bi.booking_item_id = ? AND b.user_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('ii', $bookingItemId, $userId);

    // --- DEBUGGING START ---
    error_log("DEBUG: Executing SQL with bookingItemId={$bookingItemId} and userId={$userId}");
    // --- DEBUGGING END ---

    $stmt->execute();
    $result = $stmt->get_result();
    $ticketDetails = $result->fetch_assoc();

    // --- DEBUGGING START ---
    error_log("DEBUG: Query result (ticketDetails): " . var_export($ticketDetails, true));
    if ($ticketDetails === null) {
        error_log("DEBUG: No rows fetched. Check data and join conditions.");
    }
    // --- DEBUGGING END ---

    if (!$ticketDetails) {
        http_response_code(404);
        die('Error: Ticket not found or you do not have permission to access this ticket.');
    }

    // --- FPDF INCLUSION AND TICKET GENERATION CODE STARTS HERE ---

    // Adjust this path if your fpdf.php is not directly in C:\xampp\htdocs\orchid\lib\fpdf\fpdf.php
    require_once __DIR__ . '/../lib/fpdf/fpdf.php';

    class PDF extends FPDF
    {
        // Page header
        function Header()
        {
            // You can add a logo or header text here
            // $this->Image('logo.png',10,8,33);
            $this->SetFont('Arial','B',15);
            $this->Cell(0,10,'Event Ticket',0,1,'C');
            $this->Ln(10);
        }

        // Page footer
        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    // Instanciation of FPDF class
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',12);

    // Add ticket details
    $pdf->Cell(0,10,'Booking ID: ' . $ticketDetails['booking_id'],0,1);
    $pdf->Cell(0,10,'Ticket ID: ' . $ticketDetails['booking_item_id'],0,1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Event: ' . $ticketDetails['event_name'],0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Description: ' . $ticketDetails['event_description'],0,1);
    $pdf->Cell(0,10,'Date: ' . $ticketDetails['event_date'] . ' at ' . $ticketDetails['event_time'],0,1);
    $pdf->Cell(0,10,'Location: ' . $ticketDetails['event_location'],0,1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Customer: ' . $ticketDetails['customer_full_name'],0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Email: ' . $ticketDetails['customer_email'],0,1);
    $pdf->Cell(0,10,'Phone: ' . $ticketDetails['customer_phone'],0,1);
    $pdf->Cell(0,10,'Tickets Bought: ' . $ticketDetails['tickets_bought'],0,1);
    $pdf->Cell(0,10,'Price Per Ticket: ' . $ticketDetails['price_per_item'],0,1);
    $pdf->Cell(0,10,'Booking Status: ' . $ticketDetails['booking_status'],0,1);
    $pdf->Cell(0,10,'Booking Date: ' . $ticketDetails['booking_date'],0,1);
    $pdf->Ln(10);

    $pdf->SetFont('Arial','I',10);
    $pdf->Cell(0,10,'Thank you for your booking!',0,1,'C');

    // Output the PDF to the browser for download
    $filename = "ticket_" . $ticketDetails['booking_item_id'] . ".pdf";
    $pdf->Output('D', $filename); // 'D' forces download

    // --- FPDF TICKET GENERATION CODE ENDS HERE ---

} catch (Exception $e) {
    error_log("Error generating ticket for booking item ID {$bookingItemId}: " . $e->getMessage());
    http_response_code(500);
    die('An error occurred while generating your ticket: ' . $e->getMessage());
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    // Make sure to close the database connection if it's open, if db_connect.php doesn't handle it
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>