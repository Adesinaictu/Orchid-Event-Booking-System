<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'orchidfy_db'); 
define('DB_USER', 'root');    
define('DB_PASS', '');        

// Base URL for QR code generation (using an external API for simplicity)
define('QR_API_BASE_URL', 'https://api.qrserver.com/v1/create-qr-code/');

// FPDF library path (adjust if your fpdf.php is in a different location)
define('FPDF_PATH', __DIR__ . '/lib/fpdf/fpdf.php');
?>