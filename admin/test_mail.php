<?php
// test_mail.php

// Adjust these paths if your PHPMailer library is in a different location
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Needed for DEBUG_SERVER constant

// --- IMPORTANT: Replace with your ACTUAL SMTP Details from includes/config.php ---
// For Gmail, remember to use an App Password if you have 2-Step Verification enabled!
define('TEST_SMTP_HOST', 'smtp.gmail.com');
define('TEST_SMTP_USERNAME', 'noreplysbbs@gmail.com'); // Your full Gmail address
define('TEST_SMTP_PASSWORD', 'ywlo bmup lryl jobz'); // Your generated App Password for Gmail
define('TEST_SMTP_PORT', 587); // Usually 587 for TLS

$mail = new PHPMailer(true); // Enable exceptions

try {
    // Server settings for debugging
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->Debugoutput = 'html'; // Output debug info to browser

    $mail->isSMTP();
    $mail->Host       = TEST_SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = TEST_SMTP_USERNAME;
    $mail->Password   = TEST_SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use `PHPMailer::ENCRYPTION_STARTTLS` for 'tls'
    $mail->Port       = TEST_SMTP_PORT;

    // Recipients
    $mail->setFrom(TEST_SMTP_USERNAME, 'Test Mailer'); // 'From' address usually must match your Username
    $mail->addAddress('noreplysbbs@gmail.com', 'Test User'); // Your email to receive the test
    $mail->addReplyTo(TEST_SMTP_USERNAME, 'Test Mailer Reply'); // Optional

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test Email - ' . date('Y-m-d H:i:s');
    $mail->Body    = 'This is a test email sent from PHPMailer with debugging enabled. If you see this, it worked!';
    $mail->AltBody = 'This is a test email sent from PHPMailer with debugging enabled. If you see this, it worked!';

    $mail->send();
    echo '<p style="color: green;">Message has been sent successfully (if no debug errors above).</p>';
} catch (Exception $e) {
    echo "<p style=\"color: red;\">Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
    error_log("PHPMailer test_mail.php Error: {$mail->ErrorInfo}"); // Log the error
}
?>