


   <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle pre-flight OPTIONS request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => false, 'cart_item' => [], 'total_amount' => 0, 'message' => ''];

$dbHost = 'localhost';
$dbName = '';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

    if (empty($cart)) {
        $response['success'] = true; // Still success, just an empty cart
        $response['message'] = 'Cart is empty.';
        echo json_encode($response);
        exit;
    }

    $eventIds = array_keys($cart);
    $inClause = implode(',', array_fill(0, count($eventIds), '?'));

    $stmt = $pdo->prepare("SELECT event_id, name, price, date FROM events WHERE event_id IN ($inClause)");
    $stmt->execute($eventIds);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $detailedCart = [];
    $totalAmount = 0;

    foreach ($events as $event) {
        $eventId = $event['event_id'];
        $quantity = $cart[$eventId]['quantity'];
        $itemTotalPrice = $event['price'] * $quantity;

        $detailedCart[] = [
            'event_id' => $eventId,
            'name' => $event['name'],
            'price' => (float)$event['price'],
            'date' => $event['date'],  
            'quantity' => $quantity,
            'item_total' => $itemTotalPrice
        ];
        $totalAmount += $itemTotalPrice;
    }

    $response['success'] = true;
    $response['cart_item'] = $detailedCart;
    $response['total_amount'] = $totalAmount;

} catch (PDOException $e) {
    error_log("Database error in get_detailed_cart.php: " . $e->getMessage());
    $response['message'] = 'Database error fetching cart details.';
} catch (Exception $e) {
    error_log("General error in get_detailed_cart.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>