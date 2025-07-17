<?php
// A simple, secure, and modern way to send email with PHPMailer
// Step 1: Include PHPMailer classes
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Step 2: Now include your configuration files that USE the classes
// Make sure these files are defined after PHPMailer classes are available
require_once 'includes/database.php'; // Assumes $public_conn is available here
require_once 'includes/config.php'; // Assumes SMTP_HOST, SMTP_USERNAME, etc. are defined here

// Step 3: Now you can use the 'use' statements for the rest of the script
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;      // Set to 2 for verbose debug output
    $mail->isSMTP();                                // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';       // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                   // Enable SMTP authentication
    $mail->Username   = 'noreplysbbs@gmail.com';// Your Gmail address (the sending account)
    $mail->Password   = 'ywlo bmup lryl jobz'; // YOUR GMAIL APP PASSWORD
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port       = 587;                    // TCP port to connect to

    // Recipients
    $mail->setFrom('noreplysbbs@gmail.com', 'Your Name or App Name');
    $mail->addAddress('developer@sbbs.co.in', 'Recipient Name'); // Use a different recipient!
    $mail->addReplyTo('noreplysbbs@gmail.com', 'Information');

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'Test Mail Godwin';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

?>