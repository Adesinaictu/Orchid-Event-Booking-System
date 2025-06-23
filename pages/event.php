<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$eventId = $_GET['id'] ?? null;
$eventDetails = null;

if ($eventId) {
    try {
        $sql = "SELECT * FROM events WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        $eventDetails = $result->fetch_assoc();

        if (!$eventDetails) {
            echo "Event not found.";
        }

    } catch (Exception $e) {
        error_log("Error loading event details: " . $e->getMessage());
        echo "An error occurred while loading event details. Please try again.";
    } finally {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }
} else {
    echo "No event ID provided.";
}

// You would add HTML here to display $eventDetails
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    </head>
<body>
    <h1>Event Details</h1>
    <?php if ($eventDetails): ?>
        <h2><?php echo htmlspecialchars($eventDetails['name']); ?></h2>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($eventDetails['description']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($eventDetails['date']); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($eventDetails['time']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($eventDetails['location']); ?></p>
        <p><strong>Price:</strong> <?php echo htmlspecialchars($eventDetails['price']); ?></p>
        <?php elseif ($eventId): ?>
        <p>Event not found for ID: <?php echo htmlspecialchars($eventId); ?></p>
    <?php else: ?>
        <p>Please provide an event ID.</p>
    <?php endif; ?>
</body>
</html>