<?php
// Database Credentials are in database.php
require_once 'database.php'; // Assumes $public_conn is available here

// Email Credentials
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'noreplysbbs@gmail.com');
define('SMTP_PASSWORD', 'ywlo bmup lryl jobz'); // Your App Password
define('SMTP_PORT', 587);
define('SMTP_SECURE', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS);

?>