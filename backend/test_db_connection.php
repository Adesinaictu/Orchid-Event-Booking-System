 <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dbHost = 'localhost';
$dbName = 'orchidfy_db'; 
$dbUser = 'root';       
$dbPass = '';            

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Database Connection Successful!</h1>";
    echo "<p>Connected to database: <strong>$dbName</strong></p>";

    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
    $count = $stmt->fetchColumn();
    echo "<p>Table 'contact_messages' exists. Current rows: <strong>$count</strong></p>";

} catch (PDOException $e) {
    echo "<h1>Database Connection Failed!</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Check your credentials in test_db_connection.php and ensure MySQL server is running.</strong></p>";
}
?> 