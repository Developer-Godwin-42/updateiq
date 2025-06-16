<?php
// public_database.php

// Database configuration (can be the same as admin for now, or different for production)
define('PUBLIC_DB_SERVER', 'localhost');
define('PUBLIC_DB_USERNAME', 'root');
define('PUBLIC_DB_PASSWORD', '');
define('PUBLIC_DB_NAME', 'cms_db');

// Attempt to connect to MySQL database
$public_conn = new mysqli(PUBLIC_DB_SERVER, PUBLIC_DB_USERNAME, PUBLIC_DB_PASSWORD, PUBLIC_DB_NAME);

// Check connection
if ($public_conn->connect_error) {
    die("Public site connection failed: " . $public_conn->connect_error);
}

// Set character set
$public_conn->set_charset("utf8mb4");
?>