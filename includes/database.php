<?php
// database.php

// Database configuration
define('DB_SERVER', 'localhost'); // Your database server, usually 'localhost'
define('DB_USERNAME', 'root');   // Your database username (e.g., 'root' for XAMPP/WAMP)
define('DB_PASSWORD', '');       // Your database password (empty for XAMPP/WAMP by default)
define('DB_NAME', 'cms_db');     // The name of the database you created

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for proper emoji and special character support
$conn->set_charset("utf8mb4");
?>