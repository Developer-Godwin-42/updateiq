<?php
// public_database.php

// Database configuration (can be the same as admin for now, or different for production)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'cms_db');

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Public site connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>